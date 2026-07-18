# 🐳 Docker — AuthNexora

> Guia de containerização da API, uso do Dockerfile e integração com o repositório CICD.

---

## Visão Geral

O AuthNexora é distribuído como um container **PHP 8.2-FPM** via Docker. A infraestrutura completa (Nginx, MySQL, rede) está centralizada no repositório externo **[CICD](https://github.com/ReiArthurjpg/CICD)**.

```
AuthNexora/api/Dockerfile  →  Imagem PHP 8.2-FPM
CICD/docker-compose.yml    →  Stack completa (API + Nginx + MySQL)
```

---

## Dockerfile

```dockerfile
FROM php:8.2-fpm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev \
    libxml2-dev zip unzip && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copia o código-fonte
COPY . /var/www

# Instala dependências PHP (sem dev, com otimização)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Configura permissões
RUN chown -R www-data:www-data /var/www/storage 2>/dev/null || true

EXPOSE 9000
CMD ["php-fpm"]
```

### Extensões PHP instaladas

| Extensão | Uso |
|---|---|
| `pdo_mysql` | Conexão com MySQL via PDO |
| `mbstring` | Strings multibyte (normalização de e-mails) |
| `exif` | Metadados de imagens |
| `pcntl` | Controle de processos |
| `bcmath` | Matemática de precisão arbitrária |
| `gd` | Geração de imagens (QR Code) |

---

## Executando com Docker Compose (CICD)

### 1. Clone os repositórios

```bash
# Repositório da API
git clone https://github.com/ReiArthurjpg/AuthNexora.git

# Repositório de infraestrutura
git clone https://github.com/ReiArthurjpg/CICD.git
```

### 2. Configure as variáveis de ambiente

```bash
cd CICD
cp .env.example .env
# Edite o .env com suas configurações
```

### 3. Suba a stack completa

```bash
docker compose up -d
```

### 4. Execute as migrations

```bash
docker compose exec api php -r "/* execute SQL */"
# ou use o script do CICD:
# CICD/database/schemas/authnexora/001_initial_schema.sql
```

### 5. Verifique os containers

```bash
docker compose ps
docker compose logs api
```

---

## Build Manual da Imagem

```bash
cd AuthNexora/api

# Build da imagem
docker build -t authnexora-api:latest .

# Executar isoladamente (sem MySQL)
docker run -d \
  -p 9000:9000 \
  --env-file .env \
  --name authnexora \
  authnexora-api:latest
```

---

## Variáveis de Ambiente no Docker

Ao usar com Docker Compose, as variáveis são passadas via `environment:` ou `env_file:`:

```yaml
# Exemplo de service no docker-compose.yml
services:
  api:
    build: ./AuthNexora/api
    environment:
      APP_BASE_URL: http://localhost:8080
      DB_HOST: mysql            # Nome do service MySQL no Compose
      DB_PORT: 3306
      DB_DATABASE: authnexora
      DB_USERNAME: nexora_user
      DB_PASSWORD: senha_segura
      JWT_SECRET: sua_chave_secreta_aqui
      JWT_EXPIRES_IN: 3600
      MAIL_HOST: smtp.gmail.com
      MAIL_PORT: 465
      MAIL_USERNAME: noreply@nexora.com
      MAIL_PASSWORD: senha_app_gmail
      MAIL_FROM_EMAIL: noreply@nexora.com
      MAIL_FROM_NAME: Nexora
      GOOGLE_CLIENT_ID: seu_client_id
      GOOGLE_CLIENT_SECRET: seu_client_secret
      FRONTEND_URL: http://localhost:3000
      FRONTEND_RESET_URL: http://localhost:3000/reset-password
      FRONTEND_VERIFY_EMAIL_URL: http://localhost:3000/verify-email
```

---

## Comandos Úteis

```bash
# Ver logs da API em tempo real
docker compose logs -f api

# Acessar o container
docker compose exec api bash

# Verificar PHP instalado
docker compose exec api php -v

# Listar extensões
docker compose exec api php -m

# Executar Composer dentro do container
docker compose exec api composer install
```

---

## Problemas Comuns no Docker

| Problema | Causa | Solução |
|---|---|---|
| `Connection refused` MySQL | Container MySQL ainda iniciando | Aguarde e tente novamente; adicione `healthcheck` |
| `Permission denied` em `/storage` | Permissões do `www-data` | `docker compose exec api chown -R www-data:www-data /var/www/storage` |
| Composer lento no build | Download de dependências | Use `--no-dev` e `--prefer-dist` |
| `Extension not found` | Extensão não instalada | Adicione no Dockerfile: `RUN docker-php-ext-install ...` |

---

## Ver também

- [deployment.md](deployment.md) — Deploy em produção
- [environment.md](environment.md) — Referência de variáveis de ambiente
- [troubleshooting.md](troubleshooting.md) — Problemas comuns
