# 🚀 Deploy — AuthNexora

> Guia de implantação para ambientes de desenvolvimento local (XAMPP) e produção.

---

## Desenvolvimento Local — XAMPP

### Pré-requisitos

- [XAMPP](https://www.apachefriends.org/) com:
  - PHP 8.2+
  - Apache 2.4+
  - MySQL 8.0+
- [Composer](https://getcomposer.org/) instalado globalmente

### Verificar versão do PHP

```bash
php -v
# PHP 8.2.x (cli) ...
```

### Passo a passo

```bash
# 1. Clone o repositório
git clone https://github.com/ReiArthurjpg/AuthNexora.git
cd AuthNexora/api

# 2. Instale as dependências
composer install

# 3. Configure o ambiente
cp .env.example .env
# Edite .env com suas credenciais MySQL e e-mail

# 4. Crie o banco de dados
# No phpMyAdmin ou MySQL CLI:
mysql -u root -p < /caminho/para/001_initial_schema.sql
mysql -u root -p authnexora < /caminho/para/001_admin_user.sql
```

### Configuração do Apache (Virtual Host)

```apache
<VirtualHost *:8080>
    ServerName authnexora.local
    DocumentRoot "C:/xampp/htdocs/AuthNexora/api/public"

    <Directory "C:/xampp/htdocs/AuthNexora/api/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/authnexora-error.log"
    CustomLog "logs/authnexora-access.log" combined
</VirtualHost>
```

> [!IMPORTANT]
> O arquivo `api/.htaccess` já contém as regras de rewrite necessárias. Certifique-se de que o módulo `mod_rewrite` está ativo no Apache.

### Habilitar `mod_rewrite` no XAMPP

Edite `C:\xampp\apache\conf\httpd.conf` e verifique que a linha está descomentada:
```
LoadModule rewrite_module modules/mod_rewrite.so
```

---

## Docker — Produção

### Via repositório CICD

```bash
# Clone a infraestrutura
git clone https://github.com/ReiArthurjpg/CICD.git
cd CICD

# Configure variáveis de produção
cp .env.example .env.production
nano .env.production

# Suba em produção
docker compose --env-file .env.production up -d --build
```

### Checklist pré-deploy

- [ ] `JWT_SECRET` com 32+ caracteres aleatórios
- [ ] `JWT_EXPIRES_IN` ≤ 3600 segundos
- [ ] `DB_PASSWORD` com senha forte
- [ ] HTTPS configurado no Nginx/proxy
- [ ] `display_errors = Off` no PHP
- [ ] `api/public/info.php` removido
- [ ] `.env` não commitado no repositório

---

## Configuração do Nginx (Proxy Reverso)

```nginx
server {
    listen 443 ssl;
    server_name api.nexora.com;

    ssl_certificate     /etc/letsencrypt/live/api.nexora.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.nexora.com/privkey.pem;

    root /var/www/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass   api:9000;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }
}
```

---

## PHP.ini Recomendado para Produção

```ini
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
memory_limit = 256M
max_execution_time = 30
upload_max_filesize = 10M
post_max_size = 10M
```

---

## Ver também

- [docker.md](docker.md) — Containerização completa
- [environment.md](environment.md) — Variáveis de ambiente
- [security.md](security.md) — Checklist de segurança para produção
