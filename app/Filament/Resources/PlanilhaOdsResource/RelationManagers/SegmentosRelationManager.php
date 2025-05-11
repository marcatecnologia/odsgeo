<?php

namespace App\Filament\Resources\PlanilhaOdsResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\SegmentoImportService;
use Filament\Notifications\Notification;

class SegmentosRelationManager extends RelationManager
{
    protected static string $relationship = 'segmentos';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vertice_inicial_id')
                    ->relationship('verticeInicial', 'nome_ponto')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('vertice_final_id')
                    ->relationship('verticeFinal', 'nome_ponto')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('azimute')
                    ->required()
                    ->numeric()
                    ->step(0.000001),
                Forms\Components\TextInput::make('distancia')
                    ->required()
                    ->numeric()
                    ->step(0.01),
                Forms\Components\TextInput::make('confrontante')
                    ->maxLength(255),
                Forms\Components\Select::make('tipo_limite')
                    ->options([
                        'natural' => 'Natural',
                        'artificial' => 'Artificial',
                        'misto' => 'Misto',
                    ]),
                Forms\Components\TextInput::make('ordem')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('verticeInicial.nome_ponto')
                    ->label('Vértice Inicial')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verticeFinal.nome_ponto')
                    ->label('Vértice Final')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('azimute')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('distancia')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('confrontante')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_limite')
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
                            $service = app(SegmentoImportService::class);
                            $service->importar($this->getOwnerRecord(), $data['arquivo']);

                            Notification::make()
                                ->title('Sucesso')
                                ->body('Segmentos importados com sucesso!')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erro')
                                ->body('Erro ao importar segmentos: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('download_modelo')
                    ->label('Baixar Modelo CSV')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn () => asset('storage/modelos/segmentos.csv'))
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