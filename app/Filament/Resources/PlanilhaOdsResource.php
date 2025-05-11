<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanilhaOdsResource\Pages;
use App\Filament\Resources\PlanilhaOdsResource\RelationManagers;
use App\Models\PlanilhaOds;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanilhaOdsResource extends Resource
{
    protected static ?string $model = PlanilhaOds::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Planilha ODS';
    
    protected static ?string $modelLabel = 'Planilha ODS';
    
    protected static ?string $pluralModelLabel = 'Planilhas ODS';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificação do Imóvel')
                    ->schema([
                        Forms\Components\Select::make('servico_id')
                            ->relationship('servico', 'nome')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('nome_imovel')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('municipio')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('uf')
                            ->required()
                            ->maxLength(2),
                        Forms\Components\TextInput::make('codigo_imovel')
                            ->maxLength(255),
                        Forms\Components\Select::make('tipo_imovel')
                            ->options([
                                'rural' => 'Rural',
                                'urbano' => 'Urbano',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('area_imovel')
                            ->required()
                            ->numeric()
                            ->prefix('ha'),
                    ]),

                Forms\Components\Section::make('Dados do Responsável Técnico')
                    ->schema([
                        Forms\Components\TextInput::make('rt_nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('rt_cpf')
                            ->required()
                            ->maxLength(14)
                            ->placeholder('000.000.000-00')
                            ->rules(['cpf']),
                        Forms\Components\TextInput::make('rt_crea_cau')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('rt_telefone')
                            ->required()
                            ->tel()
                            ->mask('(99) 99999-9999'),
                        Forms\Components\TextInput::make('rt_email')
                            ->required()
                            ->email()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Dados do Proprietário')
                    ->schema([
                        Forms\Components\TextInput::make('proprietario_nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('tipo_documento')
                            ->label('Tipo de Documento')
                            ->options([
                                'cpf' => 'CPF',
                                'cnpj' => 'CNPJ',
                            ])
                            ->default('cpf')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('proprietario_cpf_cnpj', '');
                            }),
                        Forms\Components\TextInput::make('proprietario_cpf_cnpj')
                            ->label(fn (Forms\Get $get) => $get('tipo_documento') === 'cpf' ? 'CPF' : 'CNPJ')
                            ->required()
                            ->maxLength(18)
                            ->placeholder(fn (Forms\Get $get) => $get('tipo_documento') === 'cpf' ? '000.000.000-00' : '00.000.000/0000-00')
                            ->rules(fn (Forms\Get $get) => $get('tipo_documento') === 'cpf' ? ['cpf'] : ['cnpj']),
                        Forms\Components\Textarea::make('proprietario_endereco')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('proprietario_percentual')
                            ->required()
                            ->numeric()
                            ->suffix('%'),
                    ]),

                Forms\Components\Section::make('Dados Adicionais')
                    ->schema([
                        Forms\Components\DatePicker::make('data_medicao')
                            ->required(),
                        Forms\Components\TextInput::make('metodo_utilizado')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tipo_equipamento')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('observacoes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('servico.projeto.cliente.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('servico.projeto.nome')
                    ->label('Projeto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('servico.nome')
                    ->label('Serviço')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nome_imovel')
                    ->label('Nome do Imóvel')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('municipio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('uf')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data de Criação')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('exportar')
                    ->label('Exportar ODS')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (PlanilhaOds $record) => $record->exportarOds()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VerticesRelationManager::class,
            RelationManagers\SegmentosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlanilhaOds::route('/'),
            'create' => Pages\CreatePlanilhaOds::route('/create'),
            'edit' => Pages\EditPlanilhaOds::route('/{record}/edit'),
        ];
    }
} 