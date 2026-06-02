<x-filament::widget>
    <x-filament::card class="bg-gradient-to-r from-green-500 to-green-600 text-white">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.032 2.002c-5.52 0-10 4.48-10 10 0 1.826.498 3.59 1.45 5.106L2 22.002l5.026-1.422c1.447.83 3.106 1.272 4.832 1.272 5.52 0 10-4.48 10-10s-4.48-10-10-10z"/>
                    </svg>
                    <h3 class="text-lg font-semibold">WhatsApp Support</h3>
                </div>
                <p class="text-sm text-green-100 mt-1">Chat with customers instantly</p>
            </div>
            <a href="https://wa.me/{{ $this->getAdminWhatsAppNumber() }}?text={{ urlencode('Hello! I need assistance with my Fyn Bridals account.') }}" 
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white text-green-600 rounded-lg hover:bg-gray-100 transition">
                Open WhatsApp
            </a>
        </div>
    </x-filament::card>
</x-filament::widget>