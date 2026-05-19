<?php

namespace App\Filament\Resources\NoteResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Actions;

class SharedUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'sharedUsers';

    protected static ?string $title = 'Note Access Control (Share)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('type')
                    ->options([
                        'view' => 'View Only',
                        'edit' => 'Can Edit',
                    ])
                    ->required()
                    ->default('view'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pivot.type')
                    ->label('Permission')
                    ->badge()
                    ->color(fn ($state) => $state === 'edit' ? 'warning' : 'info')
                    ->formatStateUsing(fn ($state) => $state === 'edit' ? 'Can Edit' : 'View Only'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Actions\AttachAction $action) => [
                        $action->getRecordSelect(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'view' => 'View Only',
                                'edit' => 'Can Edit',
                              ])
                            ->required()
                            ->default('view')
                            ->label('Akses'),
                    ])
                    ->after(function () {
                        Notification::make()
                            ->success()
                            ->title('Note shared successfully!')
                            ->send();
                    }),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->modalHeading('Edit Note Permissions'),
                Actions\DetachAction::make()
                    ->label('Revoke Access')
                    ->after(function () {
                        Notification::make()
                            ->success()
                            ->title('Access revoked successfully!')
                            ->send();
                    }),
            ]);
    }

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        // Only the note owner can view and manage shares
        return auth()->id() === $ownerRecord->user_id;
    }
}
