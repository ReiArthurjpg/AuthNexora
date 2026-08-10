# ⚙️ Variáveis de Ambiente — AuthNexora

> Referência completa de todas as variáveis de ambiente aceitas pela API, com descrição, tipo, valor padrão e exemplos.

---

## Arquivo `.env`

O projeto usa [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) para carregar variáveis de ambiente do arquivo `api/.env`.

```bash
# Copie o exemplo e edite com seus valores
cp api/.env.example api/.env
```

> [!CAUTION]
> **Nunca commite o `.env` com credenciais reais.** O arquivo `.env` está listado no `.gitignore`. Use `.env.example` como template público.

---

## Template Completo (`.env.example`)

```env
# ╔══════════════════════════════════════════════════════════╗
# ║           AuthNexora — Variáveis de Ambiente            ║
# ║  Copie este arquivo para .env e preencha os valores     ║
# ╚══════════════════════════════════════════════════════════╝

# ── APLICAÇÃO ────────────────────────────────────────────────
APP_BASE_URL=http://localhost:8080
FRONTEND_URL=http://localhost:3000
FRONTEND_RESET_URL=http://localhost:3000/reset-password
FRONTEND_VERIFY_EMAIL_URL=http://localhost:3000/verify-email
CORS_ALLOWED_ORIGINS=http://localhost:3000

# ── BANCO DE DADOS ───────────────────────────────────────────
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=authnexora
DB_USERNAME=root
DB_PASSWORD=

# ── JWT ──────────────────────────────────────────────────────
JWT_SECRET=gere-uma-chave-secreta-de-pelo-menos-32-caracteres
JWT_ISSUER=authnexora-api
JWT_EXPIRES_IN=3600

# ── E-MAIL (SMTP) ────────────────────────────────────────────
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-de-app-gmail
MAIL_FROM_EMAIL=seu-email@gmail.com
MAIL_FROM_NAME=Nexora

# ── GOOGLE OAUTH ─────────────────────────────────────────────
GOOGLE_CLIENT_ID=seu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-seu-client-secret
```

---

## Referência Completa

### 🌐 Aplicação

| Variável | Tipo | Padrão | Obrigatória | Descrição |
|---|---|---|---|---|
| `APP_BASE_URL` | string | `http://localhost:8080` | ✅ | URL base da API. Usada para gerar o `redirect_uri` do Google OAuth |
| `FRONTEND_URL` | string | `http://localhost:3000` | ✅ | URL do frontend. Usada nos redirecionamentos OAuth e como CORS origin |
| `FRONTEND_RESET_URL` | string | `http://localhost:3000/reset-password` | ✅ | URL completa da página de reset de senha no frontend |
| `FRONTEND_VERIFY_EMAIL_URL` | string | `http://localhost:3000/verify-email` | ✅ | URL completa da página de verificação de e-mail no frontend |
| `CORS_ALLOWED_ORIGINS` | string | `http://localhost:3000` | ❌ | Origens permitidas pelo CORS. Pode ser múltiplas separadas por vírgula |

**Exemplo de múltiplas origens CORS:**
```env
CORS_ALLOWED_ORIGINS=https://app.nexora.com,https://admin.nexora.com
```

> [!NOTE]
> As aliases `ALLOWED_ORIGINS` e `FRONTEND_URL` também são aceitas para `CORS_ALLOWED_ORIGINS` (fallback em cascata no `env.php`).

---

### 🗄️ Banco de Dados

| Variável | Tipo | Padrão | Obrigatória | Descrição |
|---|---|---|---|---|
| `DB_HOST` | string | — | ✅ | Hostname do servidor MySQL |
| `DB_PORT` | int | `3306` | ✅ | Porta do MySQL |
| `DB_DATABASE` | string | — | ✅ | Nome do banco de dados |
| `DB_USERNAME` | string | — | ✅ | Usuário do banco de dados |
| `DB_PASSWORD` | string | — | ✅ | Senha do banco de dados (pode ser vazio em dev local) |

**Exemplo para Docker:**
```env
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=authnexora
DB_USERNAME=nexora_user
DB_PASSWORD=senha_segura_aqui
```

**Exemplo para XAMPP:**
```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=authnexora
DB_USERNAME=root
DB_PASSWORD=
```

> [!NOTE]
> O charset `utf8mb4` é sempre aplicado e não é configurável via variável de ambiente. O `PDO::ATTR_DEFAULT_FETCH_MODE` é `PDO::FETCH_ASSOC`.

---

### 🔑 JWT

| Variável | Tipo | Padrão | Obrigatória | Descrição |
|---|---|---|---|---|
| `JWT_SECRET` | string | `change-me` | ✅ | Chave secreta para assinar tokens JWT (HMAC-SHA256) |
| `JWT_ISSUER` | string | `authnexora-api` | ❌ | Issuer (`iss`) incluído no payload do JWT |
| `JWT_EXPIRES_IN` | int | `3600` | ❌ | TTL do access token em segundos |

> [!CAUTION]
> O valor padrão `JWT_SECRET=change-me` é **altamente inseguro em produção**. Gere uma chave aleatória forte:
> ```bash
> openssl rand -hex 32
> # ou
> php -r "echo bin2hex(random_bytes(32));"
> ```
> Use no mínimo **32 caracteres** aleatórios.

**Valores recomendados por ambiente:**

| Ambiente | `JWT_EXPIRES_IN` | Razão |
|---|---|---|
| Desenvolvimento | `86400` (24h) | Comodidade |
| Produção | `900` a `3600` | Segurança |
| Testes | `60` | Testes de expiração |

---

### 📧 E-mail (SMTP)

| Variável | Tipo | Padrão | Obrigatória | Descrição |
|---|---|---|---|---|
| `MAIL_HOST` | string | `smtp.gmail.com` | ✅ | Servidor SMTP |
| `MAIL_PORT` | int | `465` | ✅ | Porta SMTP (`465` = SSL, `587` = TLS/STARTTLS) |
| `MAIL_USERNAME` | string | — | ✅ | Usuário de autenticação SMTP |
| `MAIL_PASSWORD` | string | — | ✅ | Senha de app SMTP (para Gmail: use senha de app, não a senha da conta) |
| `MAIL_FROM_EMAIL` | string | — | ✅ | Endereço de e-mail do remetente |
| `MAIL_FROM_NAME` | string | `Nexora` | ❌ | Nome do remetente exibido |

> [!TIP]
> **Gmail — Senha de App:** Para usar o Gmail, ative autenticação em 2 fatores na sua conta Google e gere uma "Senha de App" em: `Conta Google → Segurança → Senhas de app`.

**Exemplo com Gmail:**
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=noreply@minhacademia.com.br
MAIL_PASSWORD=xxxx xxxx xxxx xxxx   # Senha de app (16 chars)
MAIL_FROM_EMAIL=noreply@minhacademia.com.br
MAIL_FROM_NAME=Nexora Academy
```

**Exemplo com Mailtrap (desenvolvimento):**
```env
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_usuario_mailtrap
MAIL_PASSWORD=sua_senha_mailtrap
MAIL_FROM_EMAIL=noreply@authnexora.dev
MAIL_FROM_NAME=AuthNexora Dev
```

> [!NOTE]
> O `encryption` é sempre `ssl` e não é configurável via `.env`. Se precisar de `tls`, ajuste em `src/Services/EmailService.php`.

---

### 🔵 Google OAuth

| Variável | Tipo | Padrão | Obrigatória | Descrição |
|---|---|---|---|---|
| `GOOGLE_CLIENT_ID` | string | — | ✅* | Client ID do projeto no Google Cloud Console |
| `GOOGLE_CLIENT_SECRET` | string | — | ✅* | Client Secret do projeto no Google Cloud Console |

> *Obrigatório apenas se o login com Google for utilizado.

**O `redirect_uri` é gerado automaticamente:**
```
{APP_BASE_URL}/auth/google/callback
```

**Como configurar o Google OAuth:**
1. Acesse [console.cloud.google.com](https://console.cloud.google.com)
2. Crie ou selecione um projeto
3. Ative a API "Google+ API" ou "People API"
4. Vá em **APIs & Services → Credentials → Create OAuth 2.0 Client ID**
5. Adicione `{APP_BASE_URL}/auth/google/callback` como **Authorized redirect URI**
6. Copie o Client ID e Client Secret para o `.env`

---

## Variáveis Internas (Hardcoded)

Algumas configurações são definidas diretamente em `src/Config/env.php` e não possuem variável de ambiente:

| Configuração | Valor | Onde alterar |
|---|---|---|
| Charset do banco | `utf8mb4` | `env.php` → `db.charset` |
| Criptografia SMTP | `ssl` | `env.php` → `mail.encryption` |
| Máx. tentativas de login | `3` | `src/Services/AuthService.php` |
| TTL do reset de senha | `30 minutos` | `env.php` → `security.reset_token_ttl_minutes` |
| Rate limit máximo | `5 tentativas` | `env.php` → `security.rate_limit_max_attempts` |
| Rate limit janela | `60 segundos` | `env.php` → `security.rate_limit_window_seconds` |
| TTL do refresh token | `7 dias` | `env.php` → `security.refresh_token_ttl_days` |

---

## Mapa de Variáveis por Ambiente

| Variável | Dev (XAMPP) | Docker | Produção |
|---|---|---|---|
| `APP_BASE_URL` | `http://localhost:8080` | `http://api.nexora.com` | `https://api.nexora.com` |
| `DB_HOST` | `localhost` | `mysql` | IP/hostname do RDS/servidor |
| `JWT_SECRET` | qualquer valor | segredo forte | segredo forte gerado por `openssl` |
| `JWT_EXPIRES_IN` | `86400` | `3600` | `900` |
| `MAIL_HOST` | Mailtrap sandbox | Mailtrap / SES | SES / SendGrid / Gmail |

---

## Ver também

- [docker.md](docker.md) — Variáveis de ambiente no Docker Compose
- [deployment.md](deployment.md) — Configuração por ambiente
- [security.md](security.md) — Boas práticas para o `JWT_SECRET`
