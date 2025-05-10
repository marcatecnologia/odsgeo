<?php

return [
    'cliente' => [
        'singular' => 'Cliente',
        'plural' => 'Clientes',
        'attributes' => [
            'nome' => 'Nome',
            'email' => 'E-mail',
            'telefone' => 'Telefone',
            'endereco' => 'Endereço',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ],
    ],

    'projeto' => [
        'singular' => 'Projeto',
        'plural' => 'Projetos',
        'attributes' => [
            'nome' => 'Nome',
            'descricao' => 'Descrição',
            'cliente_id' => 'Cliente',
            'status' => 'Status',
            'data_inicio' => 'Data de Início',
            'data_fim' => 'Data de Término',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ],
    ],

    'servico' => [
        'singular' => 'Serviço',
        'plural' => 'Serviços',
        'attributes' => [
            'nome' => 'Nome',
            'descricao' => 'Descrição',
            'projeto_id' => 'Projeto',
            'tipo' => 'Tipo',
            'status' => 'Status',
            'data_inicio' => 'Data de Início',
            'data_fim' => 'Data de Término',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ],
        'types' => [
            'ods' => 'ODS',
            'memorial' => 'Memorial Descritivo',
            'outro' => 'Outro',
        ],
        'status' => [
            'pendente' => 'Pendente',
            'em_andamento' => 'Em Andamento',
            'concluido' => 'Concluído',
            'cancelado' => 'Cancelado',
        ],
    ],
]; 