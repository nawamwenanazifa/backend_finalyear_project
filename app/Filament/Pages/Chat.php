<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class Chat extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Messages';
    protected static ?string $title = 'Customer Messages';
    protected static ?string $slug = 'chat';
    
    protected static string $view = 'filament.pages.chat';
    
    public $conversations;
    public $selectedConversation;
    public $messages = [];
    public $newMessage = '';
    
    public function mount()
    {
        $this->loadConversations();
    }
    
    public function loadConversations()
    {
        $this->conversations = Conversation::where('user_one_id', Auth::id())
            ->orWhere('user_two_id', Auth::id())
            ->with('userOne', 'userTwo')
            ->latest('updated_at')
            ->get();
    }
    
    public function selectConversation($conversationId)
    {
        $this->selectedConversation = Conversation::find($conversationId);
        $this->messages = Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();
            
        // Mark messages as read
        Message::where('conversation_id', $conversationId)
            ->where('receiver_id', Auth::id())
            ->update(['is_read' => true]);
    }
    
    public function sendMessage()
    {
        if (empty($this->newMessage)) return;
        
        $message = Message::create([
            'conversation_id' => $this->selectedConversation->id,
            'sender_id' => Auth::id(),
            'receiver_id' => $this->getOtherUserId(),
            'message' => $this->newMessage,
            'is_read' => false,
        ]);
        
        $this->selectedConversation->update(['updated_at' => now()]);
        $this->messages[] = $message;
        $this->newMessage = '';
        
        // Broadcast via Reverb
        broadcast(new \App\Events\MessageSent($message))->toOthers();
    }
    
    private function getOtherUserId()
    {
        if ($this->selectedConversation->user_one_id == Auth::id()) {
            return $this->selectedConversation->user_two_id;
        }
        return $this->selectedConversation->user_one_id;
    }
    
    public function getListeners()
    {
        return [
            'echo:private-conversation.{selectedConversation.id},App\\Events\\MessageSent' => 'refreshMessages'
        ];
    }
    
    public function refreshMessages($event)
    {
        $this->messages = Message::where('conversation_id', $this->selectedConversation->id)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}