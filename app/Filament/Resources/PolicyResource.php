<?php
namespace App\Filament\Resources;

use App\Filament\Resources\PolicyResource\Pages;
use App\Filament\Resources\PolicyResource\RelationManagers;
use App\Models\Policy;
use App\Models\PolicyCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use FilamentTiptapEditor\TiptapEditor;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Policies';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 26;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Policy Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(PolicyCategory::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),

                        Forms\Components\DatePicker::make('effective_date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('status')
                            ->options([
                                'Active' => 'Active',
                                'Inactive' => 'Inactive',
                                'Draft' => 'Draft',
                            ])
                            ->default('Active')
                            ->required(),

                        // Hidden fields for tracking creators/updaters
                        Forms\Components\Hidden::make('created_by')
                            ->dehydrated(fn ($state) => filled($state))
                            ->default(fn () => auth()->id()),

                        Forms\Components\Hidden::make('last_updated_by')
                            ->dehydrated(fn ($state) => true)
                            ->default(fn () => auth()->id()),
                    ]),

                // Add new section for Policy Pages
                Forms\Components\Section::make('Policy Pages')
                    ->schema([
                        Forms\Components\Repeater::make('pages')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('order')
                                    ->numeric()
                                    ->required()
                                    ->default(function ($livewire, $get, $set, $state, $record) {
                                        // First try to use the count of existing items if we can access it
                                        if (isset($livewire) && method_exists($livewire, 'getItemsCount')) {
                                            return $livewire->getItemsCount();
                                        }

                                        // Fallback to 0 or increment based on context
                                        return 0;
                                    })
                                    ->label('Order (pages are displayed in ascending order)'),

                                TiptapEditor::make('content')
                                    ->required()
                                    ->columnSpanFull()
                                    ->profile('custom')
                                    ->tools([
                                        'heading', 'bullet-list', 'ordered-list', 'checked-list', 'blockquote', 'hr',
                                        'bold', 'italic', 'strike', 'underline', 'superscript', 'subscript', 'lead', 'small', 'align-left', 'align-center', 'align-right',
                                        'link', 'media', 'oembed', 'table', 'grid-builder', 'details',
                                        'code', 'code-block',
                                    ])
                                    ->maxContentWidth('full'),

                                // Hidden fields for tracking creators/updaters
                                Forms\Components\Hidden::make('created_by')
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->default(fn () => auth()->id()),

                                Forms\Components\Hidden::make('last_updated_by')
                                    ->dehydrated(fn ($state) => true)
                                    ->default(fn () => auth()->id()),
                            ])
                            ->orderable('order')
                            ->defaultItems(1)  // Start with one empty page
                            ->reorderable()
                            ->columnSpanFull()
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['created_by'] = auth()->id();
                                $data['last_updated_by'] = auth()->id();
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                $data['last_updated_by'] = auth()->id();
                                return $data;
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pages_count')
                    ->label('Pages')
                    ->counts('pages')
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        'Draft' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('createdByUser.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lastUpdatedByUser.name')
                    ->label('Last Updated By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(PolicyCategory::all()->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Inactive' => 'Inactive',
                        'Draft' => 'Draft',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['last_updated_by'] = auth()->id();
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolicies::route('/'),
            'create' => Pages\CreatePolicy::route('/create'),
            'edit' => Pages\EditPolicy::route('/{record}/edit'),
        ];
    }
}
