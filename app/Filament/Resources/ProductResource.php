<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationLabel = 'Products';
    
    protected static ?string $navigationGroup = 'Shop Management';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select a category'),
                                
                                Select::make('name')
                                    ->label('Product Name')
                                    ->options([
                                        'Royal Gomesi' => 'Royal Gomesi',
                                        'Modern Gomesi' => 'Modern Gomesi',
                                        'Classic Busuuti' => 'Classic Busuuti',
                                        'Premium Kanzu' => 'Premium Kanzu',
                                        'Luxury Wedding Gown' => 'Luxury Wedding Gown',
                                        'Silk Wedding Gown' => 'Silk Wedding Gown',
                                        'Lace Wedding Dress' => 'Lace Wedding Dress',
                                        'Traditional Kanzu' => 'Traditional Kanzu',
                                        'Embroidered Busuuti' => 'Embroidered Busuuti',
                                        'Beaded Accessories' => 'Beaded Accessories',
                                        'Gold Earrings' => 'Gold Earrings',
                                        'Bridal Veil' => 'Bridal Veil',
                                        'Wedding Shoes' => 'Wedding Shoes',
                                    ])
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select or type product name'),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                TextInput::make('price')
                                    ->label('Price (UGX)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('UGX')
                                    ->reactive()
                                    ->afterStateHydrated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('price', (float) str_replace(',', '', $state));
                                        }
                                    })
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, '.', ',') : ''),
                                
                                Select::make('color')
                                    ->label('Color')
                                    ->options([
                                        'Red' => '🔴 Red',
                                        'Maroon' => '🟤 Maroon',
                                        'Gold' => '🟡 Gold',
                                        'White' => '⚪ White',
                                        'Ivory' => '🟤 Ivory',
                                        'Black' => '⚫ Black',
                                        'Navy Blue' => '🔵 Navy Blue',
                                        'Royal Blue' => '🔵 Royal Blue',
                                        'Emerald Green' => '🟢 Emerald Green',
                                        'Purple' => '🟣 Purple',
                                        'Pink' => '🌸 Pink',
                                        'Orange' => '🟠 Orange',
                                        'Beige' => '🟤 Beige',
                                        'Silver' => '⚪ Silver',
                                        'Champagne' => '🥂 Champagne',
                                        'Rose Gold' => '🟤 Rose Gold',
                                        'Coral' => '🪸 Coral',
                                        'Lavender' => '🟣 Lavender',
                                        'Teal' => '🟦 Teal',
                                        'Burgundy' => '🔴 Burgundy',
                                    ])
                                    ->searchable()
                                    ->placeholder('Select a color'),
                            ]),
                        
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(6)
                            ->placeholder("Example:\n\nThis stunning Royal Gomesi is handcrafted with premium African fabric. Features elegant embroidery, matching sash, and perfect for weddings, introductions, and special ceremonies.\n\nSizes Available: S, M, L, XL, XXL\nMaterial: 100% premium cotton\nCare Instructions: Dry clean only\nDelivery: 3-5 business days")
                            ->columnSpanFull(),
                        
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Product Image')
                                    ->image()
                                    ->directory('products')
                                    ->imageResizeTargetWidth('800')
                                    ->imageResizeTargetHeight('800'),
                                
                                TextInput::make('rating')
                                    ->label('Rating')
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.1),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_featured')
                                    ->label('Featured Product')
                                    ->default(false),
                            ]),
                    ]),
                
                Section::make('Inventory Management')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('stock_quantity')
                                    ->label('Stock Quantity')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('in_stock', $state > 0);
                                    }),
                                
                                TextInput::make('low_stock_threshold')
                                    ->label('Low Stock Threshold')
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(0)
                                    ->required(),
                            ]),
                        
                        Toggle::make('in_stock')
                            ->label('In Stock')
                            ->default(true),
                        
                        Placeholder::make('stock_status')
                            ->label('Current Stock Status')
                            ->content(function ($get) {
                                $quantity = $get('stock_quantity') ?? 0;
                                $threshold = $get('low_stock_threshold') ?? 5;
                                
                                if ($quantity <= 0) {
                                    return '🔴 Out of Stock';
                                }
                                if ($quantity <= $threshold) {
                                    return "🟡 Low Stock (Only $quantity left)";
                                }
                                return "🟢 In Stock ($quantity available)";
                            }),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->circular()
                    ->width(50)
                    ->height(50),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('UGX')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'UGX ' . number_format($state, 0, '.', ',')),
                
                Tables\Columns\TextColumn::make('color')
                    ->label('Color')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state, $record): string => 
                        $state <= 0 ? 'danger' : 
                        ($state <= ($record->low_stock_threshold ?? 5) ? 'warning' : 'success')
                    )
                    ->formatStateUsing(fn ($state, $record) => 
                        $state <= 0 ? 'Out of Stock' : 
                        ($state <= ($record->low_stock_threshold ?? 5) ? "Low Stock ($state left)" : "$state in stock")
                    ),
                
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Category'),
                
                Tables\Filters\SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                    ])
                    ->query(function ($query, $data) {
                        if ($data['value'] === 'in_stock') {
                            $query->where('stock_quantity', '>', 0);
                        } elseif ($data['value'] === 'low_stock') {
                            $query->where('stock_quantity', '>', 0)
                                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
                        } elseif ($data['value'] === 'out_of_stock') {
                            $query->where('stock_quantity', '<=', 0);
                        }
                    }),
                
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured Products')
                    ->placeholder('All Products')
                    ->trueLabel('Featured Only')
                    ->falseLabel('Non-Featured Only'),
                
                Tables\Filters\SelectFilter::make('color')
                    ->label('Color')
                    ->options([
                        'Red' => 'Red',
                        'Maroon' => 'Maroon',
                        'Gold' => 'Gold',
                        'White' => 'White',
                        'Black' => 'Black',
                        'Navy Blue' => 'Navy Blue',
                        'Purple' => 'Purple',
                        'Pink' => 'Pink',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('operation')
                            ->label('Operation')
                            ->options([
                                'add' => 'Add Stock',
                                'remove' => 'Remove Stock',
                                'set' => 'Set Exact Quantity',
                            ])
                            ->default('add')
                            ->required()
                            ->reactive(),
                        
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason (Optional)')
                            ->rows(2)
                            ->placeholder('e.g., New shipment arrived, Damaged items, etc.'),
                    ])
                    ->action(function ($record, $data) {
                        $oldQuantity = $record->stock_quantity;
                        
                        switch ($data['operation']) {
                            case 'add':
                                $newQuantity = $oldQuantity + $data['quantity'];
                                break;
                            case 'remove':
                                $newQuantity = max(0, $oldQuantity - $data['quantity']);
                                break;
                            case 'set':
                                $newQuantity = max(0, $data['quantity']);
                                break;
                        }
                        
                        $record->stock_quantity = $newQuantity;
                        $record->in_stock = $newQuantity > 0;
                        $record->save();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Stock Updated')
                            ->body("Stock changed from " . number_format($oldQuantity) . " to " . number_format($newQuantity))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_adjust_stock')
                        ->label('Adjust Stock')
                        ->icon('heroicon-o-plus-circle')
                        ->form([
                            Forms\Components\Select::make('operation')
                                ->label('Operation')
                                ->options([
                                    'add' => 'Add Stock',
                                    'remove' => 'Remove Stock',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->minValue(1),
                        ])
                        ->action(function ($records, $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($data['operation'] === 'add') {
                                    $record->stock_quantity += $data['quantity'];
                                } else {
                                    $record->stock_quantity = max(0, $record->stock_quantity - $data['quantity']);
                                }
                                $record->in_stock = $record->stock_quantity > 0;
                                $record->save();
                                $count++;
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title("Stock Updated for $count Products")
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}