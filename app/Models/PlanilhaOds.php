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
        'tipo_documento',
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
            // Validar dados obrigatórios
            if (empty($this->nome_imovel)) {
                throw new \Exception('O nome do imóvel é obrigatório.');
            }

            if (empty($this->municipio)) {
                throw new \Exception('O município é obrigatório.');
            }

            if (empty($this->uf)) {
                throw new \Exception('A UF é obrigatória.');
            }

            if (empty($this->tipo_imovel)) {
                throw new \Exception('O tipo de imóvel é obrigatório.');
            }

            if (empty($this->area_imovel)) {
                throw new \Exception('A área do imóvel é obrigatória.');
            }

            if (empty($this->rt_nome)) {
                throw new \Exception('O nome do responsável técnico é obrigatório.');
            }

            if (empty($this->rt_cpf)) {
                throw new \Exception('O CPF do responsável técnico é obrigatório.');
            }

            if (empty($this->rt_crea_cau)) {
                throw new \Exception('O CREA/CAU do responsável técnico é obrigatório.');
            }

            if (empty($this->proprietario_nome)) {
                throw new \Exception('O nome do proprietário é obrigatório.');
            }

            if (empty($this->proprietario_cpf_cnpj)) {
                throw new \Exception('O CPF/CNPJ do proprietário é obrigatório.');
            }

            if (empty($this->data_medicao)) {
                throw new \Exception('A data da medição é obrigatória.');
            }

            if (empty($this->metodo_utilizado)) {
                throw new \Exception('O método utilizado é obrigatório.');
            }

            if (empty($this->tipo_equipamento)) {
                throw new \Exception('O tipo de equipamento é obrigatório.');
            }

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

            throw $e;
        }
    }
} 