<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlanilhaOds;
use App\Models\Vertice;
use App\Models\Segmento;
use Carbon\Carbon;

class DadosFicticiosSeeder extends Seeder
{
    public function run(): void
    {
        // Criar planilha ODS
        $planilha = PlanilhaOds::create([
            'servico_id' => 1, // Certifique-se que este serviço existe
            'nome_imovel' => 'Fazenda Boa Vista',
            'municipio' => 'São Paulo',
            'uf' => 'SP',
            'codigo_imovel' => 'SP-123456',
            'tipo_imovel' => 'rural',
            'area_imovel' => 150.50,
            'rt_nome' => 'João Silva',
            'rt_cpf' => '123.456.789-00',
            'rt_crea_cau' => '123456-F',
            'rt_telefone' => '(11) 98765-4321',
            'rt_email' => 'joao.silva@email.com',
            'proprietario_nome' => 'Maria Oliveira',
            'tipo_documento' => 'cpf',
            'proprietario_cpf_cnpj' => '987.654.321-00',
            'proprietario_endereco' => 'Rua das Flores, 123 - Centro - São Paulo/SP',
            'proprietario_percentual' => 100.00,
            'data_medicao' => Carbon::now(),
            'metodo_utilizado' => 'GPS RTK',
            'tipo_equipamento' => 'Trimble R10',
            'observacoes' => 'Medição realizada em condições climáticas favoráveis.',
        ]);

        // Criar vértices
        $vertices = [
            [
                'nome_ponto' => 'P1',
                'coordenada_x' => -46.123456,
                'coordenada_y' => -23.123456,
                'altitude' => 750.50,
                'tipo_marco' => 'Pilar',
                'codigo_sirgas' => 'SIRGAS2000',
                'ordem' => 1,
            ],
            [
                'nome_ponto' => 'P2',
                'coordenada_x' => -46.123789,
                'coordenada_y' => -23.123789,
                'altitude' => 751.20,
                'tipo_marco' => 'Pilar',
                'codigo_sirgas' => 'SIRGAS2000',
                'ordem' => 2,
            ],
            [
                'nome_ponto' => 'P3',
                'coordenada_x' => -46.124123,
                'coordenada_y' => -23.124123,
                'altitude' => 752.00,
                'tipo_marco' => 'Pilar',
                'codigo_sirgas' => 'SIRGAS2000',
                'ordem' => 3,
            ],
            [
                'nome_ponto' => 'P4',
                'coordenada_x' => -46.124456,
                'coordenada_y' => -23.124456,
                'altitude' => 751.80,
                'tipo_marco' => 'Pilar',
                'codigo_sirgas' => 'SIRGAS2000',
                'ordem' => 4,
            ],
        ];

        foreach ($vertices as $vertice) {
            Vertice::create(array_merge($vertice, ['planilha_ods_id' => $planilha->id]));
        }

        // Criar segmentos
        $segmentos = [
            [
                'vertice_inicial_id' => 1,
                'vertice_final_id' => 2,
                'azimute' => 45.123456,
                'distancia' => 100.50,
                'confrontante' => 'Fazenda São João',
                'tipo_limite' => 'Divisa',
                'ordem' => 1,
            ],
            [
                'vertice_inicial_id' => 2,
                'vertice_final_id' => 3,
                'azimute' => 135.234567,
                'distancia' => 150.75,
                'confrontante' => 'Sítio Boa Esperança',
                'tipo_limite' => 'Divisa',
                'ordem' => 2,
            ],
            [
                'vertice_inicial_id' => 3,
                'vertice_final_id' => 4,
                'azimute' => 225.345678,
                'distancia' => 100.25,
                'confrontante' => 'Rancho Alegre',
                'tipo_limite' => 'Divisa',
                'ordem' => 3,
            ],
            [
                'vertice_inicial_id' => 4,
                'vertice_final_id' => 1,
                'azimute' => 315.456789,
                'distancia' => 150.00,
                'confrontante' => 'Chácara São José',
                'tipo_limite' => 'Divisa',
                'ordem' => 4,
            ],
        ];

        foreach ($segmentos as $segmento) {
            Segmento::create(array_merge($segmento, ['planilha_ods_id' => $planilha->id]));
        }

        // Criar segunda planilha ODS
        $planilha2 = PlanilhaOds::create([
            'servico_id' => 1, // Certifique-se que este serviço existe
            'nome_imovel' => 'Sítio São Francisco',
            'municipio' => 'Campinas',
            'uf' => 'SP',
            'codigo_imovel' => 'SP-789012',
            'tipo_imovel' => 'rural',
            'area_imovel' => 75.25,
            'rt_nome' => 'Pedro Santos',
            'rt_cpf' => '456.789.123-00',
            'rt_crea_cau' => '789012-F',
            'rt_telefone' => '(19) 91234-5678',
            'rt_email' => 'pedro.santos@email.com',
            'proprietario_nome' => 'Empresa ABC Ltda',
            'tipo_documento' => 'cnpj',
            'proprietario_cpf_cnpj' => '12.345.678/0001-90',
            'proprietario_endereco' => 'Av. Principal, 456 - Jardim América - Campinas/SP',
            'proprietario_percentual' => 100.00,
            'data_medicao' => Carbon::now(),
            'metodo_utilizado' => 'GPS RTK',
            'tipo_equipamento' => 'Leica GS18',
            'observacoes' => 'Medição realizada com equipamento de alta precisão.',
        ]);

        // Criar vértices para a segunda planilha
        $vertices2 = [
            [
                'nome_ponto' => 'P1',
                'coordenada_x' => -47.123456,
                'coordenada_y' => -22.123456,
                'altitude' => 650.30,
                'tipo_marco' => 'Pilar',
                'codigo_sirgas' => 'SIRGAS2000',
                'ordem' => 1,
            ],
            [
                'nome_ponto' => 'P2',
                'coordenada_x' => -47.123789,
                'coordenada_y' => -22.123789,
                'altitude' => 651.00,
                'tipo_marco' => 'Pilar',
                'codigo_sirgas' => 'SIRGAS2000',
                'ordem' => 2,
            ],
            [
                'nome_ponto' => 'P3',
                'coordenada_x' => -47.124123,
                'coordenada_y' => -22.124123,
                'altitude' => 651.50,
                'tipo_marco' => 'Pilar',
                'codigo_sirgas' => 'SIRGAS2000',
                'ordem' => 3,
            ],
            [
                'nome_ponto' => 'P4',
                'coordenada_x' => -47.124456,
                'coordenada_y' => -22.124456,
                'altitude' => 650.80,
                'tipo_marco' => 'Pilar',
                'codigo_sirgas' => 'SIRGAS2000',
                'ordem' => 4,
            ],
        ];

        foreach ($vertices2 as $vertice) {
            Vertice::create(array_merge($vertice, ['planilha_ods_id' => $planilha2->id]));
        }

        // Criar segmentos para a segunda planilha
        $segmentos2 = [
            [
                'vertice_inicial_id' => 5,
                'vertice_final_id' => 6,
                'azimute' => 90.123456,
                'distancia' => 75.50,
                'confrontante' => 'Fazenda Santa Clara',
                'tipo_limite' => 'Divisa',
                'ordem' => 1,
            ],
            [
                'vertice_inicial_id' => 6,
                'vertice_final_id' => 7,
                'azimute' => 180.234567,
                'distancia' => 50.25,
                'confrontante' => 'Sítio São Pedro',
                'tipo_limite' => 'Divisa',
                'ordem' => 2,
            ],
            [
                'vertice_inicial_id' => 7,
                'vertice_final_id' => 8,
                'azimute' => 270.345678,
                'distancia' => 75.75,
                'confrontante' => 'Chácara São Paulo',
                'tipo_limite' => 'Divisa',
                'ordem' => 3,
            ],
            [
                'vertice_inicial_id' => 8,
                'vertice_final_id' => 5,
                'azimute' => 0.456789,
                'distancia' => 50.00,
                'confrontante' => 'Rancho São Francisco',
                'tipo_limite' => 'Divisa',
                'ordem' => 4,
            ],
        ];

        foreach ($segmentos2 as $segmento) {
            Segmento::create(array_merge($segmento, ['planilha_ods_id' => $planilha2->id]));
        }
    }
} 