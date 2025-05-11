<?php

namespace App\Filament\Resources\PlanilhaOdsResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\VerticeImportService;
use Filament\Notifications\Notification;

class VerticesRelationManager extends RelationManager
{
    protected static string $relationship = 'vertices';

    protected static ?string $recordTitleAttribute = 'nome_ponto';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome_ponto')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('coordenada_x')
                    ->required()
                    ->numeric()
                    ->step(0.000001),
                Forms\Components\TextInput::make('coordenada_y')
                    ->required()
                    ->numeric()
                    ->step(0.000001),
                Forms\Components\TextInput::make('altitude')
                    ->numeric()
                    ->step(0.01),
                Forms\Components\TextInput::make('tipo_marco')
                    ->maxLength(255),
                Forms\Components\TextInput::make('codigo_sirgas')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ordem')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome_ponto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('coordenada_x')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('coordenada_y')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('altitude')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_marco')
                    ->searchable(),
                Tables\Columns\TextColumn::make('codigo_sirgas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ordem')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('importar')
                    ->label('Importar CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('arquivo')
                            ->label('Arquivo CSV')
                            ->required()
                            ->acceptedFileTypes(['text/csv'])
                            ->maxSize(1024),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $service = app(VerticeImportService::class);
                            $service->importar($this->getOwnerRecord(), $data['arquivo']);

                            Notification::make()
                                ->title('Sucesso')
                                ->body('Vértices importados com sucesso!')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erro')
                                ->body('Erro ao importar vértices: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('download_modelo')
                    ->label('Baixar Modelo CSV')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn () => asset('storage/modelos/vertices.csv'))
                    ->openUrlInNewTab(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('ordem', 'asc');
    }
} 