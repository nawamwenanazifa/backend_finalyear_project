<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MessageController extends Controller
{
    // =====================================================================
    // CONVERSATIONS
    // =====================================================================

    // Get all conversations for the logged-in user
    public function getConversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver'])
            ->get()
            ->groupBy(function ($message) use ($userId) {
                return $message->sender_id == $userId
                    ? $message->receiver_id
                    : $message->sender_id;
            })
            ->map(function ($messages, $otherUserId) {
                $user = User::find($otherUserId);
                if (!$user) return null;

                $lastMessage  = $messages->sortBy('created_at')->last();
                $unreadCount  = $messages->where('receiver_id', auth()->id())
                                         ->where('is_read', false)
                                         ->count();

                // Get or create conversation for real-time channel
                $conversation = Conversation::where(function ($q) use ($otherUserId) {
                    $q->where('user_one_id', auth()->id())
                      ->where('user_two_id', $otherUserId);
                })->orWhere(function ($q) use ($otherUserId) {
                    $q->where('user_one_id', $otherUserId)
                      ->where('user_two_id', auth()->id());
                })->first();

                return [
                    'conversation_id' => $conversation?->id,
                    'user' => [
                        'id'            => $user->id,
                        'name'          => $user->name,
                        'email'         => $user->email,
                        'profile_image' => $user->profile_image ?? null,
                        'gender'        => $user->gender ?? null,
                    ],
                    'last_message'      => $lastMessage->message,
                    'last_message_time' => $lastMessage->created_at->diffForHumans(),
                    'unread_count'      => $unreadCount,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'success'       => true,
            'conversations' => $conversations,
        ]);
    }

    // Get or create a conversation between auth user and another user
    public function getOrCreateConversation(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $authId  = auth()->id();
        $otherId = $request->user_id;

        $conversation = Conversation::where(function ($q) use ($authId, $otherId) {
            $q->where('user_one_id', $authId)->where('user_two_id', $otherId);
        })->orWhere(function ($q) use ($authId, $otherId) {
            $q->where('user_one_id', $otherId)->where('user_two_id', $authId);
        })->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_one_id' => $authId,
                'user_two_id' => $otherId,
            ]);
        }

        $otherUser = User::find($otherId);

        return response()->json([
            'success'         => true,
            'conversation_id' => $conversation->id,
            'user' => [
                'id'            => $otherUser->id,
                'name'          => $otherUser->name,
                'profile_image' => $otherUser->profile_image ?? null,
            ],
        ]);
    }

    // Get messages inside a conversation (Flutter uses conversation_id)
    public function getConversationMessages(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (!$conversation->hasParticipant(auth()->id())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Mark messages as read
        Message::where('conversation_id', $conversationId)
            ->where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::where('conversation_id', $conversationId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success'  => true,
            'messages' => $messages,
        ]);
    }

    // Mark all messages in a conversation as read
    public function markConversationAsRead(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (!$conversation->hasParticipant(auth()->id())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        Message::where('conversation_id', $conversationId)
            ->where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    // =====================================================================
    // LEGACY: Get messages between current user and another user (by userId)
    // =====================================================================
    public function getMessages(Request $request, $userId)
    {
        $currentUserId = $request->user()->id;

        Message::where('sender_id', $userId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::where(function ($query) use ($currentUserId, $userId) {
            $query->where('sender_id', $currentUserId)
                  ->where('receiver_id', $userId);
        })->orWhere(function ($query) use ($currentUserId, $userId) {
            $query->where('sender_id', $userId)
                  ->where('receiver_id', $currentUserId);
        })
        ->with(['sender', 'receiver'])
        ->orderBy('created_at', 'asc')
        ->get();

        return response()->json([
            'success'  => true,
            'messages' => $messages,
        ]);
    }

    // =====================================================================
    // SEND MESSAGES (all broadcast via Reverb)
    // =====================================================================

    // Send a text message
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message'     => 'nullable|string',
        ]);

        $conversation = $this->findOrCreateConversation(
            auth()->id(),
            $request->receiver_id
        );

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => auth()->id(),
            'receiver_id'     => $request->receiver_id,
            'message'         => $request->message ?? '',
            'is_read'         => false,
        ]);

        $message->load(['sender', 'receiver']);
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 201);
    }

    // Send image message (multipart)
    public function sendImage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'image'       => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $image     = $request->file('image');
        $imageName = time() . '_' . uniqid() . '.' . $image->extension();
        $path      = public_path('uploads/chat_images');

        if (!file_exists($path)) mkdir($path, 0777, true);
        $image->move($path, $imageName);
        $imageUrl = '/uploads/chat_images/' . $imageName;

        $conversation = $this->findOrCreateConversation(
            auth()->id(),
            $request->receiver_id
        );

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => auth()->id(),
            'receiver_id'     => $request->receiver_id,
            'message'         => '📷 Sent an image',
            'image_url'       => $imageUrl,
            'is_read'         => false,
        ]);

        $message->load(['sender', 'receiver']);
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success'   => true,
            'message'   => $message,
            'image_url' => $imageUrl,
        ], 201);
    }

    // Send audio message (multipart)
    public function sendAudio(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'audio'       => 'required|max:10240',
        ]);

        $audio     = $request->file('audio');
        $audioName = time() . '_' . uniqid() . '.' . $audio->getClientOriginalExtension();
        $path      = public_path('uploads/chat_audio');

        if (!file_exists($path)) mkdir($path, 0777, true);
        $audio->move($path, $audioName);
        $audioUrl = '/uploads/chat_audio/' . $audioName;

        $conversation = $this->findOrCreateConversation(
            auth()->id(),
            $request->receiver_id
        );

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => auth()->id(),
            'receiver_id'     => $request->receiver_id,
            'message'         => '🎤 Sent a voice message',
            'audio_url'       => $audioUrl,
            'is_read'         => false,
        ]);

        $message->load(['sender', 'receiver']);
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success'   => true,
            'message'   => $message,
            'audio_url' => $audioUrl,
        ], 201);
    }

    // Send image from Flutter Web (base64)
    public function sendImageWeb(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'image_base64'=> 'required|string',
            'extension'   => 'nullable|string',
        ]);

        $base64   = preg_replace('/^data:image\/\w+;base64,/', '', $request->image_base64);
        $binary   = base64_decode($base64);
        $ext      = $request->extension ?? 'jpg';
        $fileName = time() . '_' . uniqid() . '.' . $ext;
        $path     = public_path('uploads/chat_images');

        if (!file_exists($path)) mkdir($path, 0777, true);
        file_put_contents($path . '/' . $fileName, $binary);
        $imageUrl = '/uploads/chat_images/' . $fileName;

        $conversation = $this->findOrCreateConversation(
            auth()->id(),
            $request->receiver_id
        );

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => auth()->id(),
            'receiver_id'     => $request->receiver_id,
            'message'         => '📷 Sent an image',
            'image_url'       => $imageUrl,
            'is_read'         => false,
        ]);

        $message->load(['sender', 'receiver']);
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success'   => true,
            'message'   => $message,
            'image_url' => $imageUrl,
        ], 201);
    }

    // Send audio from Flutter Web (base64)
    public function sendAudioWeb(Request $request)
    {
        $request->validate([
            'receiver_id'  => 'required|exists:users,id',
            'audio_base64' => 'required|string',
            'extension'    => 'nullable|string',
        ]);

        $binary   = base64_decode($request->audio_base64);
        $ext      = $request->extension ?? 'ogg';
        $fileName = time() . '_' . uniqid() . '.' . $ext;
        $path     = public_path('uploads/chat_audio');

        if (!file_exists($path)) mkdir($path, 0777, true);
        file_put_contents($path . '/' . $fileName, $binary);
        $audioUrl = '/uploads/chat_audio/' . $fileName;

        $conversation = $this->findOrCreateConversation(
            auth()->id(),
            $request->receiver_id
        );

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => auth()->id(),
            'receiver_id'     => $request->receiver_id,
            'message'         => '🎤 Sent a voice message',
            'audio_url'       => $audioUrl,
            'is_read'         => false,
        ]);

        $message->load(['sender', 'receiver']);
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success'   => true,
            'message'   => $message,
            'audio_url' => $audioUrl,
        ], 201);
    }

    // =====================================================================
    // READ RECEIPTS, UNREAD COUNT, TYPING, DELETE
    // =====================================================================

    public function markAsRead(Request $request, $id)
    {
        $message = Message::findOrFail($id);

        if ($message->receiver_id == $request->user()->id) {
            $message->update(['is_read' => true]);
        }

        return response()->json(['success' => true, 'message' => 'Message marked as read']);
    }

    public function getUnreadCount(Request $request)
    {
        $count = Message::where('receiver_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['success' => true, 'unread_count' => $count]);
    }

    public function deleteMessage(Request $request, $id)
    {
        $message = Message::findOrFail($id);

        if ($message->sender_id != $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($message->image_url && file_exists(public_path($message->image_url))) {
            unlink(public_path($message->image_url));
        }

        if ($message->audio_url && file_exists(public_path($message->audio_url))) {
            unlink(public_path($message->audio_url));
        }

        $message->delete();

        return response()->json(['success' => true, 'message' => 'Message deleted']);
    }

    public function sendTypingStatus(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'is_typing'   => 'required|boolean',
        ]);

        $key = 'user_typing_' . $request->receiver_id;
        if ($request->is_typing) {
            Cache::put($key, $request->user()->id, now()->addSeconds(3));
        } else {
            Cache::forget($key);
        }

        return response()->json(['success' => true]);
    }

    public function getTypingStatus(Request $request, $userId)
    {
        $key      = 'user_typing_' . $request->user()->id;
        $isTyping = Cache::get($key) == $userId;

        return response()->json(['success' => true, 'is_typing' => $isTyping]);
    }

    // =====================================================================
    // PRIVATE HELPER
    // =====================================================================

    private function findOrCreateConversation(int $authId, int $otherId): Conversation
    {
        return Conversation::where(function ($q) use ($authId, $otherId) {
            $q->where('user_one_id', $authId)->where('user_two_id', $otherId);
        })->orWhere(function ($q) use ($authId, $otherId) {
            $q->where('user_one_id', $otherId)->where('user_two_id', $authId);
        })->firstOrCreate(
            ['user_one_id' => $authId, 'user_two_id' => $otherId]
        );
    }
}