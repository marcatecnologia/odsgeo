<?php

namespace App\Console\Commands;

use App\Models\WmsCamada;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SincronizarCamadasWmsIncra extends Command
{
    protected $signature = 'sigef:sincronizar-camadas-wms';
    protected $description = 'Sincroniza as camadas WMS do INCRA por estado e tipo';

    public function handle()
    {
        $this->info('Iniciando sincronização das camadas WMS...');

        $ufs = [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS',
            'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC',
            'SP', 'SE', 'TO'
        ];

        $tipos = array_keys(WmsCamada::getTiposDisponiveis());
        $baseUrl = 'http://acervofundiario.incra.gov.br/i3geo/ogc.php?tema=';

        DB::beginTransaction();

        try {
            foreach ($ufs as $uf) {
                foreach ($tipos as $tipo) {
                    $tema = "{$tipo}_{$uf}";
                    $url = $baseUrl . $tema;

                    WmsCamada::updateOrCreate(
                        ['uf' => $uf, 'tipo' => $tipo],
                        [
                            'tema' => $tema,
                            'url' => $url,
                            'ativo' => true,
                            'descricao' => $this->gerarDescricao($tipo, $uf),
                            'data_sync' => now(),
                        ]
                    );

                    $this->info("Camada sincronizada: {$tema}");
                }
            }

            DB::commit();
            $this->info('Sincronização concluída com sucesso!');
            Log::info('Sincronização das camadas WMS concluída com sucesso');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Erro durante a sincronização: ' . $e->getMessage());
            Log::error('Erro na sincronização das camadas WMS: ' . $e->getMessage());
        }
    }

    private function gerarDescricao(string $tipo, string $uf): string
    {
        $tipos = WmsCamada::getTiposDisponiveis();
        $nomeTipo = $tipos[$tipo] ?? $tipo;
        return "Camada {$nomeTipo} - {$uf}";
    }
} 