<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasFiles;
use App\Services\PlanilhaOdsService;
use Filament\Notifications\Notification;

class PlanilhaOds extends Model
{
    use HasFactory, SoftDeletes, HasFiles;

    protected $table = 'planilhas_ods';

    protected $fillable = [
        'servico_id',
        'nome_imovel',
        'municipio',
        'uf',
        'codigo_imovel',
        'tipo_imovel',
        'area_imovel',
        'rt_nome',
        'rt_cpf',
        'rt_crea_cau',
        'rt_telefone',
        'rt_email',
        'proprietario_nome',
        'proprietario_cpf_cnpj',
        'proprietario_endereco',
        'proprietario_percentual',
        'data_medicao',
        'metodo_utilizado',
        'tipo_equipamento',
        'observacoes',
        'arquivo_ods',
    ];

    protected $casts = [
        'area_imovel' => 'decimal:2',
        'proprietario_percentual' => 'decimal:2',
        'data_medicao' => 'date',
    ];

    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }

    public function vertices()
    {
        return $this->hasMany(Vertice::class, 'planilha_ods_id');
    }

    public function segmentos()
    {
        return $this->hasMany(Segmento::class, 'planilha_ods_id');
    }

    public function exportarOds()
    {
        try {
            $service = app(PlanilhaOdsService::class);
            $path = $service->exportar($this);

            Notification::make()
                ->title('Sucesso')
                ->body('Planilha ODS exportada com sucesso!')
                ->success()
                ->send();

            return response()->download($path);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro')
                ->body('Erro ao exportar planilha ODS: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
} 