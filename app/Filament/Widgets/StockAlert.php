<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Schema;

class StockAlert extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 3;
    
    public function table(Table $table): Table
    {
        $query = Schema::hasTable('products')
            ? Product::query()
                ->where(function ($query) {
                    $query->where('stock_quantity', '>', 0)
                        ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
                })
                ->orWhere('stock_quantity', 0)
                ->orderBy('stock_quantity', 'asc')
            : Product::query()->whereRaw('1 = 0');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Current Stock')
                    ->badge()
                    ->color(fn ($state): string => 
                        $state <= 0 ? 'danger' : 'warning'
                    ),
                
                Tables\Columns\TextColumn::make('low_stock_threshold')
                    ->label('Alert Threshold'),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('restock')
                    ->label('Restock')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('quantity')
                            ->label('Quantity to Add')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                    ])
                    ->action(function ($record, $data) {
                        $record->increaseStock($data['quantity']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Stock Updated')
                            ->body("Added {$data['quantity']} units to {$record->name}")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('view_product')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record): string => "/admin/products/{$record->id}/edit")
                    ->openUrlInNewTab(),
            ])
            ->heading('⚠️ Low Stock Alert')
            ->emptyStateHeading('No stock alerts')
            ->emptyStateDescription('All products have sufficient stock levels');
    }
}