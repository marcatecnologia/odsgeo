#!/bin/bash

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Configurando PostgreSQL e PostGIS...${NC}"

# Instalar dependências necessárias
echo -e "${YELLOW}Instalando dependências...${NC}"
apt-get update
apt-get install -y postgresql-client

# Configurar banco de dados
echo -e "${YELLOW}Configurando banco de dados...${NC}"
PGPASSWORD=postgres psql -h postgres -U postgres -d odsgeo -c "
    -- Habilitar PostGIS
    CREATE EXTENSION IF NOT EXISTS postgis;
    CREATE EXTENSION IF NOT EXISTS postgis_topology;
    CREATE EXTENSION IF NOT EXISTS fuzzystrmatch;
    CREATE EXTENSION IF NOT EXISTS postgis_tiger_geocoder;

    -- Configurar parâmetros
    ALTER SYSTEM SET max_connections = '100';
    ALTER SYSTEM SET shared_buffers = '256MB';
    ALTER SYSTEM SET effective_cache_size = '768MB';
    ALTER SYSTEM SET maintenance_work_mem = '64MB';
    ALTER SYSTEM SET work_mem = '4MB';
    ALTER SYSTEM SET random_page_cost = '1.1';
    ALTER SYSTEM SET effective_io_concurrency = '200';
    ALTER SYSTEM SET default_statistics_target = '100';
    ALTER SYSTEM SET max_worker_processes = '8';
    ALTER SYSTEM SET max_parallel_workers_per_gather = '4';
    ALTER SYSTEM SET max_parallel_workers = '8';
    ALTER SYSTEM SET max_parallel_maintenance_workers = '4';

    -- Recarregar configurações
    SELECT pg_reload_conf();
"

# Verificar instalação
echo -e "${YELLOW}Verificando instalação...${NC}"
PGPASSWORD=postgres psql -h postgres -U postgres -d odsgeo -c "
    SELECT PostGIS_version();
"

echo -e "${GREEN}Configuração concluída!${NC}"
echo -e "${YELLOW}Acesse o pgAdmin em: http://localhost:5050${NC}"
echo -e "${YELLOW}Email: admin@odsgeo.com${NC}"
echo -e "${YELLOW}Senha: admin${NC}" 