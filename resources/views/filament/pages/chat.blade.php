<x-filament-panels::page>
    <div class="flex h-full gap-4">
        {{-- Conversations List --}}
        <div class="w-1/3 bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b bg-gray-50">
                <h2 class="font-semibold">Conversations</h2>
            </div>
            <div class="divide-y">
                @foreach($conversations as $conv)
                    <div wire:click="selectConversation({{ $conv->id }})" 
                         class="p-4 hover:bg-gray-50 cursor-pointer {{ $selectedConversation?->id == $conv->id ? 'bg-primary-50' : '' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                <span class="text-primary-600 font-bold">
                                    {{ substr($conv->getOtherUser(Auth::id())->name ?? 'U', 0, 1) }}
                                </span>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium">{{ $conv->getOtherUser(Auth::id())->name ?? 'User' }}</p>
                                <p class="text-sm text-gray-500 truncate">
                                    {{ $conv->messages->last()?->message ?? 'No messages yet' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        {{-- Chat Area --}}
        <div class="flex-1 bg-white rounded-lg shadow flex flex-col">
            @if($selectedConversation)
                <div class="p-4 border-b bg-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-primary-600 font-bold">
                                {{ substr($selectedConversation->getOtherUser(Auth::id())->name ?? 'U', 0, 1) }}
                            </span>
                        </div>
                        <div>
                            <p class="font-semibold">{{ $selectedConversation->getOtherUser(Auth::id())->name ?? 'User' }}</p>
                            <p class="text-xs text-green-600">● Online</p>
                        </div>
                    </div>
                </div>
                
                {{-- Messages --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    @foreach($messages as $msg)
                        <div class="flex {{ $msg->sender_id == Auth::id() ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[70%] {{ $msg->sender_id == Auth::id() ? 'bg-primary-500 text-white' : 'bg-gray-100' }} rounded-lg p-3">
                                <p>{{ $msg->message }}</p>
                                <p class="text-xs {{ $msg->sender_id == Auth::id() ? 'text-primary-100' : 'text-gray-500' }} mt-1">
                                    {{ $msg->created_at->format('h:i A') }}
                                    @if($msg->sender_id == Auth::id())
                                        {!! $msg->is_read ? '✓✓' : '✓' !!}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                {{-- Input --}}
                <div class="p-4 border-t">
                    <div class="flex gap-2">
                        <input type="text" 
                               wire:model="newMessage" 
                               wire:keydown.enter="sendMessage"
                               placeholder="Type a message..."
                               class="flex-1 rounded-lg border-gray-300 focus:border-primary-500 focus:ring focus:ring-primary-200">
                        <button wire:click="sendMessage" 
                                class="bg-primary-500 text-white px-6 py-2 rounded-lg hover:bg-primary-600">
                            Send
                        </button>
                    </div>
                </div>
            @else
                <div class="flex-1 flex items-center justify-center text-gray-500">
                    Select a conversation to start chatting
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>