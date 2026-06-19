<?php

namespace App\Filament\Widgets;

use App\Models\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class NotificationWidget extends Widget
{
    protected static string $view = 'filament.widgets.notification-widget';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    
    public function getNotifications()
    {
        try {
            if (!Schema::hasTable('notifications')) {
                return collect();
            }
            
            return Notification::where('user_id', Auth::id())
                ->orWhereNull('user_id')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }
    
    public function getUnreadCount()
    {
        try {
            if (!Schema::hasTable('notifications')) {
                return 0;
            }
            
            return Notification::where(function($query) {
                    $query->where('user_id', Auth::id())
                        ->orWhereNull('user_id');
                })
                ->where('is_read', false)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    public function markAsRead($id)
    {
        try {
            $notification = Notification::find($id);
            if ($notification) {
                $notification->markAsRead();
            }
        } catch (\Exception $e) {
            // Table may not exist yet
        }
    }
    
    public function markAllAsRead()
    {
        try {
            Notification::where(function($query) {
                    $query->where('user_id', Auth::id())
                        ->orWhereNull('user_id');
                })
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);
        } catch (\Exception $e) {
            // Table may not exist yet
        }
    }
}