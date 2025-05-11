<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Writer;

class ExportarModeloVerticesCsv extends Command
{
    protected $signature = 'modelo:vertices-csv';
    protected $description = 'Exporta um modelo de CSV para importação de vértices';

    public function handle()
    {
        $csv = Writer::createFromString('');
        
        // Definir cabeçalhos
        $headers = [
            'nome_ponto',
            'coordenada_x',
            'coordenada_y',
            'altitude',
            'tipo_marco',
            'codigo_sirgas'
        ];
        
        $csv->insertOne($headers);
        
        // Adicionar exemplo
        $exemplo = [
            'P1',
            '-48.123456',
            '-15.123456',
            '850.50',
            'Marco de Concreto',
            'SIRGAS2000'
        ];
        
        $csv->insertOne($exemplo);
        
        // Salvar arquivo
        $path = storage_path('app/public/modelos/vertices.csv');
        
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, $csv->toString());
        
        $this->info('Modelo de CSV para vértices exportado com sucesso!');
        $this->info('Arquivo salvo em: ' . $path);
    }
} 