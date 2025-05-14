# ODSGeo

Sistema de visualização e análise de dados geográficos para os Objetivos de Desenvolvimento Sustentável (ODS).

## Requisitos do Sistema

- PHP 8.1 ou superior
- PostgreSQL 12 ou superior com PostGIS 3.0 ou superior
- GeoServer 2.22.0 ou superior
- Composer 2.0 ou superior
- Node.js 16.0 ou superior
- NPM 8.0 ou superior

## Passos para Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/odsgeo.git
cd odsgeo
```

2. Instale as dependências do PHP:
```bash
composer install
```

3. Copie o arquivo de ambiente:
```bash
cp .env.example .env
```

4. Configure as variáveis de ambiente no arquivo `.env`:
- Configure as credenciais do banco de dados PostgreSQL
- Configure as credenciais do GeoServer
- Gere a chave da aplicação:
```bash
php artisan key:generate
```

5. Execute as migrações do banco de dados:
```bash
php artisan migrate
```

6. Importe os dados dos municípios:
```bash
php artisan municipios:import-postgis storage/app/municipios.geojson
```

7. Configure o GeoServer:
```bash
php artisan geoserver:setup
```

8. Instale as dependências do Node.js:
```bash
npm install
```

9. Compile os assets:
```bash
npm run build
```

## Configuração do GeoServer

O sistema utiliza o GeoServer para servir as camadas geográficas. Siga os passos abaixo para configurar o GeoServer:

1. Instale o GeoServer:
   - Baixe a versão mais recente do GeoServer em https://geoserver.org/download/
   - Extraia o arquivo baixado
   - Execute o script `bin/startup.sh` (Linux/Mac) ou `bin/startup.bat` (Windows)

2. Acesse a interface web do GeoServer:
   - Abra o navegador e acesse http://localhost:8080/geoserver
   - Faça login com as credenciais padrão (usuário: admin, senha: geoserver)

3. Configure o workspace:
   - Vá para "Workspaces" no menu lateral
   - Clique em "Add new workspace"
   - Nome: odsgeo
   - Namespace URI: http://odsgeo
   - Marque "Set as default workspace"
   - Clique em "Submit"

4. Configure o store PostGIS:
   - Vá para "Stores" no menu lateral
   - Clique em "Add new Store"
   - Selecione "PostGIS"
   - Configure as credenciais do banco de dados
   - Clique em "Save"

5. Publique a camada de municípios:
   - Vá para "Layers" no menu lateral
   - Clique em "Add new layer"
   - Selecione o store PostGIS
   - Selecione a tabela "municipios_simplificado"
   - Configure o SRS como EPSG:4326
   - Clique em "Save"

6. Configure o WFS:
   - Vá para "Services" no menu lateral
   - Clique em "WFS"
   - Configure as opções de serviço
   - Clique em "Save"

7. Configure o CORS:
   - Edite o arquivo `webapps/geoserver/WEB-INF/web.xml`
   - Adicione o filtro CORS
   - Reinicie o GeoServer

## Desenvolvimento

1. Inicie o servidor de desenvolvimento:
```bash
php artisan serve
```

2. Em outro terminal, inicie o Vite:
```bash
npm run dev
```

3. Acesse a aplicação em http://localhost:8000

## Comandos Úteis

- Importar municípios: `php artisan municipios:import-postgis {arquivo}`
- Configurar GeoServer: `php artisan geoserver:setup`
- Limpar cache: `php artisan cache:clear`
- Limpar configuração: `php artisan config:clear`
- Limpar rotas: `php artisan route:clear`
- Limpar views: `php artisan view:clear`

## Estrutura do Projeto

```
odsgeo/
├── app/
│   ├── Console/
│   │   └── Commands/         # Comandos Artisan
│   ├── Http/
│   │   ├── Controllers/      # Controladores
│   │   └── Livewire/         # Componentes Livewire
│   ├── Models/               # Modelos
│   └── Services/             # Serviços
├── config/                   # Arquivos de configuração
├── database/
│   └── migrations/           # Migrações do banco de dados
├── public/                   # Arquivos públicos
├── resources/
│   ├── js/                   # Arquivos JavaScript
│   └── views/                # Views Blade
└── storage/
    └── app/                  # Arquivos de dados
```

## Contribuindo

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-feature`)
3. Faça commit das suas alterações (`git commit -am 'Adiciona nova feature'`)
4. Faça push para a branch (`git push origin feature/nova-feature`)
5. Crie um Pull Request

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para mais detalhes. 