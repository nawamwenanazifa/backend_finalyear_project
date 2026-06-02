<x-filament::widget>
    <x-filament::card>
        @php
            $lowStock = $this->getLowStockProducts();
            $outOfStock = $this->getOutOfStockProducts();
        @endphp
        
        @if($lowStock->count() > 0 || $outOfStock->count() > 0)
            <div class="space-y-4">
                @if($outOfStock->count() > 0)
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Out of Stock Products</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc list-inside">
                                        @foreach($outOfStock as $product)
                                            <li>{{ $product->name }} - Stock: {{ $product->stock_quantity }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if($lowStock->count() > 0)
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Low Stock Alert</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <ul class="list-disc list-inside">
                                        @foreach($lowStock as $product)
                                            <li>{{ $product->name }} - Stock: {{ $product->stock_quantity }} (Threshold: {{ $product->low_stock_threshold }})</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">All products have sufficient stock</p>
                    </div>
                </div>
            </div>
        @endif
    </x-filament::card>
</x-filament::widget>