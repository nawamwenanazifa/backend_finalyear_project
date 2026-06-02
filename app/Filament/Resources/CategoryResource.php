<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?string $navigationGroup = 'Shop Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Category Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Wedding Gowns')
                    ->columnSpanFull(),
                
                Forms\Components\Select::make('icon')
                    ->label('Icon')
                    ->options([
                        'woman' => '👩‍🦰 Woman',
                        'dress' => '👗 Dress',
                        'man' => '👨 Man',
                        'celebration' => '🎉 Celebration',
                        'diamond' => '💎 Diamond',
                        'flower' => '🌸 Flower',
                        'crown' => '👑 Crown',
                        'star' => '⭐ Star',
                        'heart' => '❤️ Heart',
                        'bag' => '🛍️ Bag',
                        'ring' => '💍 Ring',
                    ])
                    ->searchable()
                    ->placeholder('Select an icon')
                    ->helperText('Choose an icon that represents this category'),
                
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->placeholder('Describe what this category contains...')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->width(50),
                
                Tables\Columns\TextColumn::make('icon')
                    ->label('Icon')
                    ->formatStateUsing(fn ($state): string => match($state) {
                        'woman' => '👩‍🦰',
                        'dress' => '👗',
                        'man' => '👨',
                        'celebration' => '🎉',
                        'diamond' => '💎',
                        'flower' => '🌸',
                        'crown' => '👑',
                        'star' => '⭐',
                        'heart' => '❤️',
                        'bag' => '🛍️',
                        'ring' => '💍',
                        default => '📁',
                    })
                    ->size(30)
                    ->width(60)
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M d, Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->sortable()
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('icon')
                    ->label('Icon Type')
                    ->options([
                        'woman' => 'Woman',
                        'dress' => 'Dress',
                        'man' => 'Man',
                        'celebration' => 'Celebration',
                        'diamond' => 'Diamond',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->color('warning'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable();
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}