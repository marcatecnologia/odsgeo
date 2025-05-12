<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SigefDiagnosticService;

class DiagnosticarSigef extends Command
{
    protected $signature = 'sigef:diagnostico';
    protected $description = 'Executa diagnóstico completo do serviço SIGEF';

    public function handle()
    {
        $this->info('Iniciando diagnóstico do serviço SIGEF...');
        $this->newLine();

        $service = new SigefDiagnosticService();
        $result = $service->checkConnection();

        $this->info('Resultado do diagnóstico:');
        $this->info('Timestamp: ' . $result['timestamp']);
        $this->info('Status: ' . ($result['status'] === 'success' ? '✅ Sucesso' : '❌ Erro'));
        $this->newLine();

        $this->info('Verificações:');
        $this->newLine();

        foreach ($result['checks'] as $check => $data) {
            $this->info(strtoupper($check) . ':');
            $this->info('Status: ' . ($data['success'] ? '✅ Sucesso' : '❌ Falha'));
            $this->info('Mensagem: ' . $data['message']);
            
            if (!empty($data['details'])) {
                $this->info('Detalhes:');
                foreach ($data['details'] as $key => $value) {
                    if (is_array($value)) {
                        $this->info("  - {$key}:");
                        foreach ($value as $subKey => $subValue) {
                            $this->info("    - {$subKey}: {$subValue}");
                        }
                    } else {
                        $this->info("  - {$key}: {$value}");
                    }
                }
            }
            
            $this->newLine();
        }

        if ($result['status'] === 'error') {
            $this->warn('Recomendações:');
            $this->line('1. Verifique a conectividade com o domínio acervofundiario.incra.gov.br');
            $this->line('2. Confirme se o certificado SSL está atualizado em: ' . storage_path('certs/sigef.pem'));
            $this->line('3. Verifique os logs do sistema para mais detalhes: storage/logs/laravel.log');
            $this->line('4. Tente novamente em alguns minutos');
            $this->line('5. Se o problema persistir, tente atualizar o certificado:');
            $this->line('   curl -o storage/certs/cacert.pem https://curl.se/ca/cacert.pem');
            
            // Sugestões específicas baseadas nos erros
            if (isset($result['checks']['dns']) && !$result['checks']['dns']['success']) {
                $this->line('6. Verifique se o DNS está resolvendo corretamente para 189.9.36.29');
            }
            
            if (isset($result['checks']['ssl']) && !$result['checks']['ssl']['success']) {
                $this->line('7. Verifique as permissões do arquivo de certificado:');
                $this->line('   chmod 644 storage/certs/sigef.pem');
            }
            
            if (isset($result['checks']['ping']) && !$result['checks']['ping']['success']) {
                $this->line('8. Verifique se o container Docker tem acesso à porta 443');
                $this->line('9. Verifique se não há bloqueio de firewall');
            }
            
            if (isset($result['checks']['wfs']) && !$result['checks']['wfs']['success']) {
                $this->line('10. Verifique se o serviço WFS está respondendo corretamente');
                $this->line('11. Tente acessar diretamente a URL: ' . $service->getBaseUrl());
            }
        }

        return $result['status'] === 'success' ? 0 : 1;
    }
} 