<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogResource\Pages;
use App\Models\Log;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Filament\Notifications\Notification;

class LogResource extends Resource
{
    protected static ?string $model = Log::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Logs de Erro';
    protected static ?string $modelLabel = 'Log de Erro';
    protected static ?string $pluralModelLabel = 'Logs de Erro';
    protected static ?int $navigationSort = 100;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('Nível')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ERROR' => 'danger',
                        'WARNING' => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('message')
                    ->label('Mensagem')
                    ->formatStateUsing(fn ($state) => view('filament.resources.log-resource.components.copy-message', ['message' => $state])->render())
                    ->html(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('Nível')
                    ->options([
                        'ERROR' => 'Erro',
                        'WARNING' => 'Aviso',
                        'INFO' => 'Informação',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver Detalhes')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn ($record) => view('filament.resources.log-resource.pages.view-log', [
                        'log' => $record
                    ]))
                    ->modalWidth('4xl'),
                Tables\Actions\Action::make('copy')
                    ->label('Copiar')
                    ->icon('heroicon-o-clipboard')
                    ->color('warning')
                    ->action(fn ($record) => null)
                    ->extraAttributes(fn ($record) => [
                        'data-copy-btn' => 'true',
                        'data-message' => base64_encode($record->message),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('copy')
                    ->label('Copiar Selecionados')
                    ->icon('heroicon-o-clipboard')
                    ->color('warning')
                    ->modalHeading('Copiar Mensagens Selecionadas')
                    ->modalSubmitActionLabel('Fechar')
                    ->modalContent(function ($records) {
                        $messages = $records->pluck('message')->implode("\n\n---------------------\n\n");
                        return view('filament.resources.log-resource.components.bulk-copy-modal', [
                            'messages' => $messages,
                        ]);
                    }),
                Tables\Actions\BulkAction::make('delete')
                    ->label('Excluir Selecionados')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->delete()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogs::route('/'),
        ];
    }
} 