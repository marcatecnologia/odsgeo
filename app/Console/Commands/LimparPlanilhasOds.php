<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LimparPlanilhasOds extends Command
{
    protected $signature = 'planilhas:limpar {--dias=7 : Número de dias para manter os arquivos}';
    protected $description = 'Limpa planilhas ODS antigas do diretório de armazenamento';

    public function handle()
    {
        $dias = $this->option('dias');
        $dataLimite = Carbon::now()->subDays($dias);
        
        $this->info("Limpando planilhas ODS mais antigas que {$dias} dias...");
        
        $diretorio = 'public/planilhas_ods';
        $arquivos = Storage::files($diretorio);
        
        $total = 0;
        foreach ($arquivos as $arquivo) {
            $dataModificacao = Carbon::createFromTimestamp(Storage::lastModified($arquivo));
            
            if ($dataModificacao->lt($dataLimite)) {
                Storage::delete($arquivo);
                $total++;
                $this->line("Arquivo removido: {$arquivo}");
            }
        }
        
        $this->info("Limpeza concluída! {$total} arquivos removidos.");
    }
} 