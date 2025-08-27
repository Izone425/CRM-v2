<?php
namespace App\Filament\Resources;

use App\Filament\Resources\PhoneExtensionResource\Pages;
use App\Models\PhoneExtension;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class PhoneExtensionResource extends Resource
{
    protected static ?string $model = PhoneExtension::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Phone Extensions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('extension')
                    ->required()
                    ->maxLength(10)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\Toggle::make('is_support_staff')
                    ->label('Support Staff')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->inline(false)
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('extension')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User Account')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_support_staff')
                    ->label('Support Staff')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department'),
                Tables\Filters\TernaryFilter::make('is_support_staff')
                    ->label('Support Staff Only'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
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
            'index' => Pages\ListPhoneExtensions::route('/'),
            'create' => Pages\CreatePhoneExtension::route('/create'),
            'edit' => Pages\EditPhoneExtension::route('/{record}/edit'),
        ];
    }
}
