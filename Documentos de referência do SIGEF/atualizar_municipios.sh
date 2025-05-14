#!/bin/bash

# Configurações
MAX_RETRIES=5
RETRY_DELAY=300  # 5 minutos
LOG_FILE="municipios_update.log"

# Função para executar o comando
run_command() {
    php artisan municipios:atualizar
    return $?
}

# Loop principal
attempt=1
while [ $attempt -le $MAX_RETRIES ]; do
    echo "[$(date)] Tentativa $attempt de $MAX_RETRIES" | tee -a $LOG_FILE
    
    if run_command; then
        echo "[$(date)] Comando executado com sucesso!" | tee -a $LOG_FILE
        exit 0
    else
        echo "[$(date)] Falha na tentativa $attempt" | tee -a $LOG_FILE
        
        if [ $attempt -lt $MAX_RETRIES ]; then
            echo "[$(date)] Aguardando $RETRY_DELAY segundos antes da próxima tentativa..." | tee -a $LOG_FILE
            sleep $RETRY_DELAY
        fi
    fi
    
    attempt=$((attempt + 1))
done

echo "[$(date)] Todas as tentativas falharam" | tee -a $LOG_FILE
exit 1 