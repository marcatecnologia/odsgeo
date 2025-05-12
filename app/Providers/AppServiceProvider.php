<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SigefWfsService;
use GuzzleHttp\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SigefWfsService::class, function ($app) {
            $client = new Client([
                'verify' => storage_path('certs/sigef.pem'),
                'timeout' => 10,
                'connect_timeout' => 5,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_CAINFO => storage_path('certs/sigef.pem'),
                ],
                'headers' => [
                    'User-Agent' => 'ODSGeo/1.0',
                    'Accept' => 'application/json',
                ],
            ]);

            return new SigefWfsService($client);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar componente Livewire para importação de coordenadas
        \Livewire\Livewire::component('importar-coordenadas', \App\Livewire\ImportarCoordenadas::class);

        // Registrar regra de validação para CPF
        \Illuminate\Support\Facades\Validator::extend('cpf', function ($attribute, $value, $parameters, $validator) {
            $value = preg_replace('/[^0-9]/', '', $value);
            
            if (strlen($value) !== 11) {
                return false;
            }

            if (preg_match('/(\d)\1{10}/', $value)) {
                return false;
            }
            
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $value[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($value[$c] != $d) {
                    return false;
                }
            }
            return true;
        });

        \Illuminate\Support\Facades\Validator::replacer('cpf', function ($message, $attribute, $rule, $parameters) {
            return 'O campo :attribute deve ser um CPF válido.';
        });

        // Registrar regra de validação para CNPJ
        \Illuminate\Support\Facades\Validator::extend('cnpj', function ($attribute, $value, $parameters, $validator) {
            $value = preg_replace('/[^0-9]/', '', $value);
            
            if (strlen($value) !== 14) {
                return false;
            }

            if (preg_match('/(\d)\1{13}/', $value)) {
                return false;
            }
            
            for ($i = 0, $j = 5, $sum = 0; $i < 12; $i++) {
                $sum += $value[$i] * $j;
                $j = ($j == 2) ? 9 : $j - 1;
            }
            
            $rest = $sum % 11;
            if ($value[12] != ($rest < 2 ? 0 : 11 - $rest)) {
                return false;
            }
            
            for ($i = 0, $j = 6, $sum = 0; $i < 13; $i++) {
                $sum += $value[$i] * $j;
                $j = ($j == 2) ? 9 : $j - 1;
            }
            
            $rest = $sum % 11;
            return $value[13] == ($rest < 2 ? 0 : 11 - $rest);
        });

        \Illuminate\Support\Facades\Validator::replacer('cnpj', function ($message, $attribute, $rule, $parameters) {
            return 'O campo :attribute deve ser um CNPJ válido.';
        });
    }
}
