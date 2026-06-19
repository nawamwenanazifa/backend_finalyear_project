<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    
    protected static ?string $navigationLabel = 'Orders';
    
    protected static ?string $navigationGroup = 'Shop Management';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('order_status', 'pending')->count() ?: static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Order Information')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Order Number')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(false),
                        
                        TextInput::make('total')
                            ->label('Total Amount')
                            ->prefix('UGX')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(3),
                
                Section::make('Payment Information')
                    ->schema([
                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash_on_delivery' => 'Cash on Delivery',
                                'mobile_money' => 'Mobile Money',
                                'bank_transfer' => 'Bank Transfer',
                            ])
                            ->disabled(),
                        
                        Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required()
                            ->live(),
                    ])->columns(2),
                
                Section::make('Order Status')
                    ->schema([
                        Select::make('order_status')
                            ->label('Order Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $record) {
                                if ($state === 'delivered' && !$record->delivered_at) {
                                    $record->update(['delivered_at' => now()]);
                                    Notification::make()
                                        ->title('Order Delivered')
                                        ->success()
                                        ->send();
                                }
                            }),
                        
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])->columns(1),
                
                Section::make('Shipping Address')
                    ->schema([
                        Textarea::make('shipping_address')
                            ->label('Shipping Address')
                            ->rows(3)
                            ->disabled(),
                    ]),
                
                Section::make('Order Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                TextInput::make('product.name')
                                    ->label('Product')
                                    ->disabled(),
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->disabled(),
                                TextInput::make('price')
                                    ->label('Price')
                                    ->prefix('UGX')
                                    ->disabled(),
                            ])
                            ->columns(3)
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('total')
                    ->label('Total')
                    ->money('UGX')
                    ->sortable(),
                
                BadgeColumn::make('payment_status')
                    ->label('Payment')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'failed',
                        'info' => 'refunded',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                
                BadgeColumn::make('order_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'primary' => 'processing',
                        'success' => 'shipped',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                
                TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('order_status')
                    ->label('Order Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
                
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-truck')
                    ->form([
                        Select::make('order_status')
                            ->label('Order Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        
                        Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                    ])
                    ->action(function ($record, $data) {
                        $record->update([
                            'order_status' => $data['order_status'],
                            'payment_status' => $data['payment_status'],
                            'delivered_at' => $data['order_status'] === 'delivered' ? now() : $record->delivered_at,
                        ]);
                        
                        Notification::make()
                            ->title('Order Updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::getModel()::where('order_status', 'pending')->count();
            return $count > 0 ? (string) $count : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}