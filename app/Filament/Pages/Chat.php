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
    protected static ?string $navigationGroup = 'Dashboard';
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.pages.chat';
    
    public $conversations;
    public $selectedConversation;
    public $messages = [];
    public $newMessage = '';
    
    // Show unread message count badge
    public static function getNavigationBadge(): ?string
    {
        try {
            $unreadCount = \App\Models\Message::where('receiver_id', auth()->id())
                ->where('is_read', false)
                ->count();
            
            return $unreadCount > 0 ? (string) $unreadCount : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
    
    protected function getListeners(): array
    {
        return [
            'refreshMessages' => 'refreshMessages',
        ];
    }
    
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
            
        // Refresh the badge count
        $this->dispatch('refresh-navigation-badge');
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
        
        // Refresh messages after sending
        $this->refreshMessages();
    }
    
    private function getOtherUserId()
    {
        if ($this->selectedConversation->user_one_id == Auth::id()) {
            return $this->selectedConversation->user_two_id;
        }
        return $this->selectedConversation->user_one_id;
    }
    
    public function refreshMessages()
    {
        if ($this->selectedConversation) {
            $this->messages = Message::where('conversation_id', $this->selectedConversation->id)
                ->orderBy('created_at', 'asc')
                ->get();
        }
    }
}