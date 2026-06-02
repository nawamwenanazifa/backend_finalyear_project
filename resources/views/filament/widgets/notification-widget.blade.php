<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <h3 class="text-lg font-semibold">Notifications</h3>
                @if($this->getUnreadCount() > 0)
                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                        {{ $this->getUnreadCount() }} new
                    </span>
                @endif
            </div>
            @if($this->getUnreadCount() > 0)
                <button wire:click="markAllAsRead" class="text-xs text-primary-600 hover:text-primary-800">
                    Mark all as read
                </button>
            @endif
        </div>
        
        <div class="space-y-2 max-h-96 overflow-y-auto">
            @forelse($this->getNotifications() as $notification)
                <div class="p-3 rounded-lg {{ $notification->is_read ? 'bg-gray-50' : 'bg-blue-50 border-l-4 border-blue-500' }}">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <span class="text-xl">{{ $notification->icon ?? 'ℹ️' }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium {{ $notification->is_read ? 'text-gray-700' : 'text-gray-900' }}">
                                    {{ $notification->title }}
                                </p>
                                <span class="text-xs text-gray-400">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ $notification->message }}</p>
                            @if($notification->link)
                                <a href="{{ $notification->link }}" class="text-xs text-primary-600 hover:text-primary-800 mt-1 inline-block">
                                    View Details →
                                </a>
                            @endif
                        </div>
                        @if(!$notification->is_read)
                            <button wire:click="markAsRead({{ $notification->id }})" class="flex-shrink-0 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p>No notifications yet</p>
                </div>
            @endforelse
        </div>
    </x-filament::card>
</x-filament::widget>