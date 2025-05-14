#!/bin/bash

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Iniciando migração para PostgreSQL...${NC}"

# Verificar se o pgloader está instalado
if ! command -v pgloader &> /dev/null; then
    echo -e "${RED}pgloader não encontrado. Instalando...${NC}"
    sudo apt-get update
    sudo apt-get install -y pgloader
fi

# Configurações do banco de dados
MYSQL_HOST="mysql"
MYSQL_PORT="3306"
MYSQL_DB="odsgeo"
MYSQL_USER="root"
MYSQL_PASS="root"

PG_HOST="postgres"
PG_PORT="5432"
PG_DB="odsgeo"
PG_USER="postgres"
PG_PASS="postgres"

# Criar arquivo de configuração do pgloader
cat > migrate.load << EOF
LOAD DATABASE
    FROM mysql://${MYSQL_USER}:${MYSQL_PASS}@${MYSQL_HOST}:${MYSQL_PORT}/${MYSQL_DB}
    INTO postgresql://${PG_USER}:${PG_PASS}@${PG_HOST}:${PG_PORT}/${PG_DB}

WITH include no drop, create tables, create indexes, reset sequences,
     workers = 8, concurrency = 1,
     multiple readers per thread, rows per range = 50000

SET PostgreSQL PARAMETERS
    maintenance_work_mem to '128MB',
    work_mem to '12MB',
    search_path to 'public'

CAST type datetime to timestamp,
     type date to date,
     type time to time,
     type year to integer,
     type tinyint to boolean using tinyint-to-boolean,
     type int to integer,
     type bigint to bigint,
     type float to float,
     type double to double precision,
     type decimal to numeric,
     type char to varchar,
     type varchar to varchar,
     type text to text,
     type longtext to text,
     type mediumtext to text,
     type tinytext to text,
     type enum to varchar,
     type json to jsonb;

EOF

echo -e "${YELLOW}Configuração do pgloader criada.${NC}"

# Executar migração
echo -e "${YELLOW}Iniciando migração dos dados...${NC}"
pgloader migrate.load

if [ $? -eq 0 ]; then
    echo -e "${GREEN}Migração concluída com sucesso!${NC}"
else
    echo -e "${RED}Erro durante a migração.${NC}"
    exit 1
fi

# Limpar arquivo temporário
rm migrate.load

echo -e "${GREEN}Processo finalizado!${NC}" 