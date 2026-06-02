<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Users';
    
    protected static ?string $navigationGroup = 'Customer Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel()
                    ->maxLength(20)
                    ->helperText('Used for WhatsApp chat'),
                
                Forms\Components\Select::make('gender')
                    ->label('Gender')
                    ->options([
                        'female' => 'Female',
                        'male' => 'Male',
                        'other' => 'Other',
                    ])
                    ->default(null),
                
                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options([
                        'bride' => 'Bride',
                        'groom' => 'Groom',
                        'guest' => 'Guest',
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                    ])
                    ->required()
                    ->default('bride'),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                
                Forms\Components\Toggle::make('is_admin')
                    ->label('Admin Access')
                    ->default(false),
                
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At'),
                
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width(50),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ?? '—'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'bride' => 'success',
                        default => 'info',
                    }),
                
                Tables\Columns\TextColumn::make('gender')
                    ->label('Gender')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_admin')
                    ->label('User Type')
                    ->options([
                        '1' => 'Admins',
                        '0' => 'Customers',
                    ]),
                
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Gender')
                    ->options([
                        'female' => 'Female',
                        'male' => 'Male',
                    ]),
                
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'bride' => 'Bride',
                        'groom' => 'Groom',
                        'guest' => 'Guest',
                        'admin' => 'Admin',
                    ]),
            ])
            ->actions([
                Action::make('whatsapp')
                    ->label('Chat on WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn ($record) => 'https://wa.me/' . preg_replace('/[^0-9]/', '', $record->phone ?? '0788967418') . '?text=' . urlencode('Hello! I am from Fyn Bridals. How can I help you?'))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->phone)),
                
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}