<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NoteResource\Pages;
use App\Models\Note;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;

class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Note Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn (?Note $record) => $record && $record->user_id !== auth()->id() && $record->sharedUsers()->where('user_id', auth()->id())->wherePivot('type', 'view')->exists()),
                        Forms\Components\MarkdownEditor::make('content')
                            ->required()
                            ->columnSpanFull()
                            ->disabled(fn (?Note $record) => $record && $record->user_id !== auth()->id() && $record->sharedUsers()->where('user_id', auth()->id())->wherePivot('type', 'view')->exists()),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->badge()
                    ->color(fn ($state) => $state === auth()->user()->name ? 'primary' : 'gray'),
                Tables\Columns\TextColumn::make('sharedUsers.name')
                    ->label('Shared With')
                    ->badge()
                    ->color('warning')
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhereHas('sharedUsers', function ($q) {
                        $q->where('user_id', auth()->id());
                    });
            });
    }

    public static function getRelations(): array
    {
        return [
            NoteResource\RelationManagers\SharedUsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotes::route('/'),
            'create' => Pages\CreateNote::route('/create'),
            'edit' => Pages\EditNote::route('/{record}/edit'),
        ];
    }
}
