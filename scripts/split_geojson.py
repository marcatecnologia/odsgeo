#!/usr/bin/env python3
import json
import os
from pathlib import Path
import sys
import re

# Diretórios
SOURCE_FILE = "/var/www/odsgeo/public/geojson/limites/municipios.geojson"
OUTPUT_DIR = "/var/www/odsgeo/storage/app/geojson/municipios"

# Lista de UFs
UFS = ["AC", "AL", "AP", "AM", "BA", "CE", "DF", "ES", "GO", "MA", "MT", "MS", 
       "MG", "PA", "PB", "PR", "PE", "PI", "RJ", "RN", "RS", "RO", "RR", "SC", 
       "SP", "SE", "TO"]

def print_progress(current, total, prefix=''):
    """Imprime uma barra de progresso simples"""
    bar_length = 50
    filled_length = int(round(bar_length * current / float(total)))
    percents = round(100.0 * current / float(total), 1)
    bar = '=' * filled_length + '-' * (bar_length - filled_length)
    sys.stdout.write(f'\r{prefix}[{bar}] {percents}%')
    sys.stdout.flush()

def extract_header(file_path):
    """Extrai o cabeçalho do arquivo GeoJSON"""
    header = {}
    with open(file_path, 'r', encoding='utf-8') as f:
        # Ler até encontrar o início do array de features
        content = f.read(4096)  # Ler os primeiros 4KB
        type_match = re.search(r'"type"\s*:\s*"([^"]+)"', content)
        name_match = re.search(r'"name"\s*:\s*"([^"]+)"', content)
        crs_match = re.search(r'"crs"\s*:\s*({[^}]+})', content)
        
        if type_match:
            header['type'] = type_match.group(1)
        if name_match:
            header['name'] = name_match.group(1)
        if crs_match:
            try:
                header['crs'] = json.loads(crs_match.group(1))
            except:
                pass
    
    return header

def process_features(file_path, uf):
    """Processa as features do arquivo GeoJSON para uma UF específica"""
    features = []
    current_feature = ""
    in_feature = False
    
    with open(file_path, 'r', encoding='utf-8') as f:
        for line in f:
            if '"type": "Feature"' in line:
                in_feature = True
                current_feature = line
            elif in_feature:
                current_feature += line
                if line.strip() == "}," or line.strip() == "}":
                    try:
                        feature = json.loads(current_feature.rstrip(','))
                        if feature.get('properties', {}).get('SIGLA_UF') == uf:
                            features.append(feature)
                    except:
                        pass
                    in_feature = False
                    current_feature = ""
    
    return features

def main():
    print("Iniciando divisão do arquivo GeoJSON de municípios...")
    
    # Verificar se o arquivo fonte existe
    if not os.path.exists(SOURCE_FILE):
        print(f"Erro: Arquivo fonte não encontrado: {SOURCE_FILE}")
        return 1
    
    print(f"Arquivo fonte encontrado: {SOURCE_FILE}")
    
    # Criar diretório de saída
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    os.chmod(OUTPUT_DIR, 0o777)
    print(f"Diretório de saída criado: {OUTPUT_DIR}")
    
    # Extrair cabeçalho
    print("Extraindo cabeçalho do arquivo...")
    header = extract_header(SOURCE_FILE)
    
    # Processar cada UF
    print("\nProcessando municípios por UF...")
    for i, uf in enumerate(UFS, 1):
        print_progress(i, len(UFS), f"Processando UF {uf}: ")
        output_file = os.path.join(OUTPUT_DIR, f"municipios_{uf}.geojson")
        
        # Processar features para a UF atual
        features = process_features(SOURCE_FILE, uf)
        
        if features:
            # Criar novo GeoJSON
            uf_geojson = {
                'type': 'FeatureCollection',
                'name': f'BR_Municipios_{uf}_2024',
                'crs': header.get('crs'),
                'features': features
            }
            
            # Salvar arquivo
            try:
                with open(output_file, 'w', encoding='utf-8') as f:
                    json.dump(uf_geojson, f, ensure_ascii=False)
                
                # Ajustar permissões
                os.chmod(output_file, 0o666)
                
                # Mostrar estatísticas
                file_size = os.path.getsize(output_file) / (1024 * 1024)  # Tamanho em MB
                print(f"\nUF {uf}: {len(features)} municípios ({file_size:.2f} MB)")
            except Exception as e:
                print(f"\nErro ao salvar arquivo para UF {uf}: {e}")
        else:
            print(f"\nAviso: Nenhuma feature encontrada para UF {uf}")
    
    print("\nProcesso concluído!")
    print(f"Arquivos salvos em: {OUTPUT_DIR}")
    return 0

if __name__ == "__main__":
    exit(main()) 