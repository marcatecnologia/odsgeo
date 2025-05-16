<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

class LimparCacheDiretorio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tipo;
    protected $id;

    public function __construct(string $tipo, ?int $id = null)
    {
        $this->tipo = $tipo;
        $this->id = $id;
    }

    public function handle(CacheService $cacheService)
    {
        try {
            switch ($this->tipo) {
                case 'servico':
                    if ($this->id) {
                        $cacheService->forgetServico($this->id);
                    }
                    break;
                case 'projeto':
                    if ($this->id) {
                        $cacheService->forgetProjeto($this->id);
                    }
                    break;
                case 'cliente':
                    if ($this->id) {
                        $cacheService->forgetCliente($this->id);
                    }
                    break;
                case 'todos':
                    $cacheService->flushCache();
                    break;
            }

            Log::info('Cache limpo com sucesso', [
                'tipo' => $this->tipo,
                'id' => $this->id
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao limpar cache', [
                'tipo' => $this->tipo,
                'id' => $this->id,
                'erro' => $e->getMessage()
            ]);

            throw $e;
        }
    }
} 