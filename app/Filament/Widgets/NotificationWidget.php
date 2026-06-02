<?php

namespace App\Filament\Widgets;

use App\Models\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class NotificationWidget extends Widget
{
    protected static string $view = 'filament.widgets.notification-widget';
    protected int | string | array $columnSpan = 'full';
    
    public function getNotifications()
    {
        return Notification::where('user_id', Auth::id())
            ->orWhereNull('user_id')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
    
    public function getUnreadCount()
    {
        return Notification::where(function($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereNull('user_id');
            })
            ->where('is_read', false)
            ->count();
    }
    
    public function markAsRead($id)
    {
        $notification = Notification::find($id);
        if ($notification) {
            $notification->markAsRead();
        }
    }
    
    public function markAllAsRead()
    {
        Notification::where(function($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereNull('user_id');
            })
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }
}