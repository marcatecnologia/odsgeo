#!/bin/bash

# Diretórios
SOURCE_FILE="/var/www/odsgeo/public/geojson/limites/municipios.geojson"
OUTPUT_DIR="/var/www/odsgeo/storage/app/geojson/municipios"

# Verificar se o arquivo fonte existe
if [ ! -f "$SOURCE_FILE" ]; then
    echo "Erro: Arquivo fonte não encontrado: $SOURCE_FILE"
    exit 1
fi

# Verificar se o jq está instalado
if ! command -v jq &> /dev/null; then
    echo "Erro: jq não está instalado. Por favor, execute como root:"
    echo "sudo apt-get update && sudo apt-get install -y jq"
    exit 1
fi

# Criar diretório de saída
echo "Criando diretório de saída: $OUTPUT_DIR"
mkdir -p "$OUTPUT_DIR"
chmod -R 777 "$OUTPUT_DIR"

# Lista de UFs
UFS=("AC" "AL" "AP" "AM" "BA" "CE" "DF" "ES" "GO" "MA" "MT" "MS" "MG" "PA" "PB" "PR" "PE" "PI" "RJ" "RN" "RS" "RO" "RR" "SC" "SP" "SE" "TO")

# Verificar se o arquivo é um GeoJSON válido
echo "Verificando arquivo GeoJSON..."
if ! head -n 1 "$SOURCE_FILE" | grep -q "^{"; then
    echo "Erro: Arquivo não começa com '{'"
    exit 1
fi

# Verificar a estrutura do arquivo
echo "Verificando estrutura do arquivo..."
if ! grep -q '"type": "FeatureCollection"' "$SOURCE_FILE"; then
    echo "Erro: Arquivo não é um FeatureCollection válido"
    exit 1
fi

# Processar cada UF
for UF in "${UFS[@]}"; do
    echo "Processando UF: $UF"
    OUTPUT_FILE="$OUTPUT_DIR/municipios_$UF.geojson"
    
    # Extrair features da UF usando SIGLA_UF
    echo "  - Extraindo features para UF $UF..."
    jq --arg uf "$UF" '.features = [.features[] | select(.properties.SIGLA_UF == $uf)]' "$SOURCE_FILE" > "$OUTPUT_FILE"
    
    # Verificar se o arquivo foi criado e tem conteúdo
    if [ -s "$OUTPUT_FILE" ]; then
        chmod 666 "$OUTPUT_FILE"
        FEATURE_COUNT=$(jq '.features | length' "$OUTPUT_FILE")
        echo "  - Criado arquivo com $FEATURE_COUNT features"
        
        # Simplificar o GeoJSON para reduzir o tamanho
        echo "  - Simplificando geometrias..."
        jq -c '.' "$OUTPUT_FILE" > "${OUTPUT_FILE}.tmp"
        mv "${OUTPUT_FILE}.tmp" "$OUTPUT_FILE"
        
        # Verificar o tamanho do arquivo
        FILE_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
        echo "  - Tamanho do arquivo: $FILE_SIZE"
    else
        echo "  - Aviso: Nenhuma feature encontrada para UF $UF"
    fi
done

echo "Processo concluído!"
echo "Arquivos salvos em: $OUTPUT_DIR" 