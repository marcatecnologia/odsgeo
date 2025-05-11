<?php

namespace App\Services;

use App\Models\PlanilhaOds;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PlanilhaOdsService
{
    public function exportar(PlanilhaOds $planilha)
    {
        try {
            // Validar dados obrigatórios
            if (!$planilha->vertices()->exists()) {
                throw new \Exception('A planilha não possui vértices cadastrados.');
            }

            if (!$planilha->segmentos()->exists()) {
                throw new \Exception('A planilha não possui segmentos cadastrados.');
            }

            $spreadsheet = new Spreadsheet();
            
            // Aba 1: Identificação do Imóvel
            $sheet1 = $spreadsheet->getActiveSheet();
            $sheet1->setTitle('Identificação do Imóvel');
            
            // Configurar cabeçalho
            $sheet1->setCellValue('A1', 'IDENTIFICAÇÃO DO IMÓVEL');
            $sheet1->mergeCells('A1:F1');
            $sheet1->getStyle('A1')->getFont()->setBold(true);
            $sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Preencher dados
            $sheet1->setCellValue('A3', 'Nome do Imóvel:');
            $sheet1->setCellValue('B3', $planilha->nome_imovel);
            
            $sheet1->setCellValue('A4', 'Município:');
            $sheet1->setCellValue('B4', $planilha->municipio);
            
            $sheet1->setCellValue('C4', 'UF:');
            $sheet1->setCellValue('D4', $planilha->uf);
            
            $sheet1->setCellValue('A5', 'Código do Imóvel:');
            $sheet1->setCellValue('B5', $planilha->codigo_imovel);
            
            $sheet1->setCellValue('C5', 'Tipo de Imóvel:');
            $sheet1->setCellValue('D5', $planilha->tipo_imovel);
            
            $sheet1->setCellValue('A6', 'Área do Imóvel (ha):');
            $sheet1->setCellValue('B6', number_format($planilha->area_imovel, 2, ',', '.'));
            
            // Aba 2: Dados do Responsável Técnico
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Responsável Técnico');
            
            // Configurar cabeçalho
            $sheet2->setCellValue('A1', 'DADOS DO RESPONSÁVEL TÉCNICO');
            $sheet2->mergeCells('A1:F1');
            $sheet2->getStyle('A1')->getFont()->setBold(true);
            $sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Preencher dados
            $sheet2->setCellValue('A3', 'Nome:');
            $sheet2->setCellValue('B3', $planilha->rt_nome);
            
            $sheet2->setCellValue('A4', 'CPF:');
            $sheet2->setCellValue('B4', $planilha->rt_cpf);
            
            $sheet2->setCellValue('A5', 'CREA/CAU:');
            $sheet2->setCellValue('B5', $planilha->rt_crea_cau);
            
            $sheet2->setCellValue('A6', 'Telefone:');
            $sheet2->setCellValue('B6', $planilha->rt_telefone);
            
            $sheet2->setCellValue('A7', 'E-mail:');
            $sheet2->setCellValue('B7', $planilha->rt_email);
            
            // Aba 3: Dados do Proprietário
            $sheet3 = $spreadsheet->createSheet();
            $sheet3->setTitle('Proprietário');
            
            // Configurar cabeçalho
            $sheet3->setCellValue('A1', 'DADOS DO PROPRIETÁRIO');
            $sheet3->mergeCells('A1:F1');
            $sheet3->getStyle('A1')->getFont()->setBold(true);
            $sheet3->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Preencher dados
            $sheet3->setCellValue('A3', 'Nome/Razão Social:');
            $sheet3->setCellValue('B3', $planilha->proprietario_nome);
            
            $sheet3->setCellValue('A4', 'CPF/CNPJ:');
            $sheet3->setCellValue('B4', $planilha->proprietario_cpf_cnpj);
            
            $sheet3->setCellValue('A5', 'Endereço:');
            $sheet3->setCellValue('B5', $planilha->proprietario_endereco);
            
            $sheet3->setCellValue('A6', 'Percentual de Posse:');
            $sheet3->setCellValue('B6', number_format($planilha->proprietario_percentual, 2, ',', '.') . '%');
            
            // Aba 4: Vértices
            $sheet4 = $spreadsheet->createSheet();
            $sheet4->setTitle('Vértices');
            
            // Configurar cabeçalho
            $sheet4->setCellValue('A1', 'LISTA DE VÉRTICES');
            $sheet4->mergeCells('A1:F1');
            $sheet4->getStyle('A1')->getFont()->setBold(true);
            $sheet4->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Cabeçalho da tabela
            $sheet4->setCellValue('A3', 'Nome do Ponto');
            $sheet4->setCellValue('B3', 'Coordenada X');
            $sheet4->setCellValue('C3', 'Coordenada Y');
            $sheet4->setCellValue('D3', 'Altitude');
            $sheet4->setCellValue('E3', 'Tipo de Marco');
            $sheet4->setCellValue('F3', 'Código SIRGAS');
            
            // Estilizar cabeçalho
            $headerStyle = [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'CCCCCC'],
                ],
            ];
            $sheet4->getStyle('A3:F3')->applyFromArray($headerStyle);
            
            // Preencher dados dos vértices
            $row = 4;
            foreach ($planilha->vertices()->orderBy('ordem')->get() as $vertice) {
                $sheet4->setCellValue('A' . $row, $vertice->nome_ponto);
                $sheet4->setCellValue('B' . $row, number_format($vertice->coordenada_x, 6, ',', '.'));
                $sheet4->setCellValue('C' . $row, number_format($vertice->coordenada_y, 6, ',', '.'));
                $sheet4->setCellValue('D' . $row, $vertice->altitude ? number_format($vertice->altitude, 2, ',', '.') : '');
                $sheet4->setCellValue('E' . $row, $vertice->tipo_marco);
                $sheet4->setCellValue('F' . $row, $vertice->codigo_sirgas);
                $row++;
            }
            
            // Aplicar bordas na tabela de vértices
            $lastRow = $row - 1;
            $sheet4->getStyle('A3:F' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            // Aba 5: Segmentos
            $sheet5 = $spreadsheet->createSheet();
            $sheet5->setTitle('Segmentos');
            
            // Configurar cabeçalho
            $sheet5->setCellValue('A1', 'SEGMENTOS E CONFRONTAÇÕES');
            $sheet5->mergeCells('A1:F1');
            $sheet5->getStyle('A1')->getFont()->setBold(true);
            $sheet5->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Cabeçalho da tabela
            $sheet5->setCellValue('A3', 'Vértice Inicial');
            $sheet5->setCellValue('B3', 'Vértice Final');
            $sheet5->setCellValue('C3', 'Azimute');
            $sheet5->setCellValue('D3', 'Distância');
            $sheet5->setCellValue('E3', 'Confrontante');
            $sheet5->setCellValue('F3', 'Tipo de Limite');
            
            // Estilizar cabeçalho
            $sheet5->getStyle('A3:F3')->applyFromArray($headerStyle);
            
            // Preencher dados dos segmentos
            $row = 4;
            foreach ($planilha->segmentos()->orderBy('ordem')->get() as $segmento) {
                $sheet5->setCellValue('A' . $row, $segmento->verticeInicial->nome_ponto);
                $sheet5->setCellValue('B' . $row, $segmento->verticeFinal->nome_ponto);
                $sheet5->setCellValue('C' . $row, number_format($segmento->azimute, 6, ',', '.'));
                $sheet5->setCellValue('D' . $row, number_format($segmento->distancia, 2, ',', '.'));
                $sheet5->setCellValue('E' . $row, $segmento->confrontante);
                $sheet5->setCellValue('F' . $row, $segmento->tipo_limite);
                $row++;
            }
            
            // Aplicar bordas na tabela de segmentos
            $lastRow = $row - 1;
            $sheet5->getStyle('A3:F' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            // Aba 6: Dados Adicionais
            $sheet6 = $spreadsheet->createSheet();
            $sheet6->setTitle('Dados Adicionais');
            
            // Configurar cabeçalho
            $sheet6->setCellValue('A1', 'DADOS ADICIONAIS');
            $sheet6->mergeCells('A1:F1');
            $sheet6->getStyle('A1')->getFont()->setBold(true);
            $sheet6->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Preencher dados
            $sheet6->setCellValue('A3', 'Data da Medição:');
            $sheet6->setCellValue('B3', $planilha->data_medicao->format('d/m/Y'));
            
            $sheet6->setCellValue('A4', 'Método Utilizado:');
            $sheet6->setCellValue('B4', $planilha->metodo_utilizado);
            
            $sheet6->setCellValue('A5', 'Tipo de Equipamento:');
            $sheet6->setCellValue('B5', $planilha->tipo_equipamento);
            
            $sheet6->setCellValue('A6', 'Observações:');
            $sheet6->setCellValue('B6', $planilha->observacoes);
            
            // Ajustar largura das colunas
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                foreach (range('A', 'F') as $column) {
                    $worksheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
            
            // Gerar arquivo
            $writer = new Ods($spreadsheet);
            $filename = 'SIGEF_' . str_replace(' ', '_', $planilha->nome_imovel) . '_' . date('Y-m-d') . '.ods';
            $path = storage_path('app/public/planilhas_ods/' . $filename);
            
            // Criar diretório se não existir
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            
            $writer->save($path);
            
            // Atualizar caminho do arquivo no banco
            $planilha->update(['arquivo_ods' => $filename]);
            
            return $path;
        } catch (\Exception $e) {
            throw new \Exception('Erro ao exportar planilha ODS: ' . $e->getMessage());
        }
    }
} 