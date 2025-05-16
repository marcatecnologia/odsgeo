# Sistema de Diretórios ODSGeo

## Visão Geral

O sistema de diretórios do ODSGeo é uma estrutura hierárquica que organiza os dados técnicos do sistema através de uma navegação cliente > projeto > serviço. Esta documentação descreve a implementação, arquitetura e boas práticas do sistema.

## Estrutura de Diretórios

### Hierarquia
```
cliente/
  ├── projetos/
  │   └── servico/
  │       ├── planilhas/
  │       ├── memoriais/
  │       ├── shapes/
  │       ├── coordenadas/
  │       ├── mapas/
  │       ├── relatorios/
  │       └── documentos/
  └── documentos/
```

### Categorias de Arquivos

- **planilhas/**: Arquivos Excel, CSV e outros formatos de planilha
- **memoriais/**: Documentos técnicos e memoriais descritivos
- **shapes/**: Arquivos de formato shapefile (.shp, .shx, .dbf, .prj)
- **coordenadas/**: Arquivos de coordenadas em diversos formatos
- **mapas/**: Imagens de mapas e layouts cartográficos
- **relatorios/**: Relatórios gerados pelo sistema
- **documentos/**: Documentos gerais e outros arquivos

## Componentes Principais

### 1. SelecionarDiretorioModal

Componente Livewire responsável pela interface de seleção de diretório.

**Funcionalidades:**
- Seleção hierárquica (cliente > projeto > serviço)
- Busca em tempo real
- Cache de consultas
- Validação de hierarquia
- Persistência de estado

### 2. DiretorioService

Serviço responsável pelo gerenciamento de diretórios.

**Métodos Principais:**
- `criarEstruturaDiretoriosServico()`
- `criarDiretorioServico()`
- `removerDiretorio()`
- `listarArquivosPorCategoria()`
- `moverArquivo()`

### 3. CacheService

Serviço responsável pelo gerenciamento de cache.

**Funcionalidades:**
- Cache em camadas com tags
- Invalidação inteligente
- TTL configurável
- Limpeza automática

## Eventos e Jobs

### Eventos
- `ServicoAtualizado`: Disparado quando um serviço é atualizado

### Jobs
- `LimparCacheDiretorio`: Responsável pela limpeza do cache

## Boas Práticas

### 1. Cache
- Usar tags para melhor organização
- Implementar TTL adequado
- Limpar cache seletivamente
- Monitorar uso de memória

### 2. Segurança
- Validar permissões
- Sanitizar inputs
- Proteger contra XSS
- Implementar rate limiting

### 3. Performance
- Usar eager loading
- Implementar paginação
- Otimizar consultas
- Monitorar uso de recursos

### 4. Manutenção
- Documentar alterações
- Seguir padrões de código
- Implementar testes
- Manter logs

## Fluxo de Trabalho

1. Usuário seleciona cliente
2. Sistema carrega projetos do cliente
3. Usuário seleciona projeto
4. Sistema carrega serviços do projeto
5. Usuário seleciona serviço
6. Sistema atualiza diretório atual
7. Cache é atualizado
8. Eventos são disparados

## Troubleshooting

### Problemas Comuns

1. **Cache Desatualizado**
   - Verificar TTL
   - Limpar cache manualmente
   - Verificar eventos

2. **Permissões**
   - Verificar ACL
   - Validar usuário
   - Checar grupos

3. **Performance**
   - Monitorar consultas
   - Verificar índices
   - Otimizar cache

## Contribuição

1. Seguir padrões de código
2. Documentar alterações
3. Implementar testes
4. Criar PR com descrição clara

## Suporte

Para suporte técnico, contate a equipe de desenvolvimento. 