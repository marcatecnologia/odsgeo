<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Writer;

class ExportarModeloSegmentosCsv extends Command
{
    protected $signature = 'modelo:segmentos-csv';
    protected $description = 'Exporta um modelo de CSV para importação de segmentos';

    public function handle()
    {
        $csv = Writer::createFromString('');
        
        // Definir cabeçalhos
        $headers = [
            'vertice_inicial',
            'vertice_final',
            'azimute',
            'distancia',
            'confrontante',
            'tipo_limite'
        ];
        
        $csv->insertOne($headers);
        
        // Adicionar exemplo
        $exemplo = [
            'P1',
            'P2',
            '45.123456',
            '150.25',
            'Fazenda São João',
            'natural'
        ];
        
        $csv->insertOne($exemplo);
        
        // Salvar arquivo
        $path = storage_path('app/public/modelos/segmentos.csv');
        
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, $csv->toString());
        
        $this->info('Modelo de CSV para segmentos exportado com sucesso!');
        $this->info('Arquivo salvo em: ' . $path);
    }
} 