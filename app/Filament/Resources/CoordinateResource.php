<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoordinateResource\Pages;
use App\Models\Coordinate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CoordinateResource extends Resource
{
    protected static ?string $model = Coordinate::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Coordenadas';

    protected static ?string $navigationGroup = 'Processamentos';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('point')
                    ->required()
                    ->label('Ponto'),
                Forms\Components\TextInput::make('description')
                    ->label('Descrição'),
                Forms\Components\TextInput::make('utm_north')
                    ->numeric()
                    ->label('UTM Norte'),
                Forms\Components\TextInput::make('utm_east')
                    ->numeric()
                    ->label('UTM Leste'),
                Forms\Components\TextInput::make('latitude_decimal')
                    ->numeric()
                    ->label('Latitude Decimal'),
                Forms\Components\TextInput::make('longitude_decimal')
                    ->numeric()
                    ->label('Longitude Decimal'),
                Forms\Components\TextInput::make('latitude_gms')
                    ->label('Latitude GMS'),
                Forms\Components\TextInput::make('longitude_gms')
                    ->label('Longitude GMS'),
                Forms\Components\TextInput::make('elevation')
                    ->numeric()
                    ->label('Cota'),
                Forms\Components\Select::make('datum')
                    ->options([
                        'SIRGAS2000' => 'SIRGAS2000',
                        'SAD69' => 'SAD69',
                    ])
                    ->required()
                    ->label('Datum'),
                Forms\Components\TextInput::make('utm_zone')
                    ->numeric()
                    ->label('Zona UTM'),
                Forms\Components\TextInput::make('central_meridian')
                    ->label('Meridiano Central'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('point')
                    ->searchable()
                    ->sortable()
                    ->label('Ponto'),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->sortable()
                    ->label('Descrição'),
                Tables\Columns\TextColumn::make('utm_north')
                    ->numeric()
                    ->sortable()
                    ->label('UTM Norte'),
                Tables\Columns\TextColumn::make('utm_east')
                    ->numeric()
                    ->sortable()
                    ->label('UTM Leste'),
                Tables\Columns\TextColumn::make('latitude_decimal')
                    ->numeric()
                    ->sortable()
                    ->label('Latitude Decimal'),
                Tables\Columns\TextColumn::make('longitude_decimal')
                    ->numeric()
                    ->sortable()
                    ->label('Longitude Decimal'),
                Tables\Columns\TextColumn::make('elevation')
                    ->numeric()
                    ->sortable()
                    ->label('Cota'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('format')
                    ->options([
                        'utm' => 'UTM',
                        'decimal' => 'Decimal',
                        'gms' => 'GMS',
                    ])
                    ->label('Formato'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListarCoordenadas::route('/'),
            'create' => Pages\CriarCoordenada::route('/create'),
            'edit' => Pages\EditarCoordenada::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('service_id', session()->get('current_service_id'));
    }
} 