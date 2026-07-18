# 📋 Changelog — AuthNexora

> Histórico de versões e mudanças do projeto AuthNexora.

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).

---

## [1.4.0] — Versão Atual

### Adicionado
- Autenticação de dois fatores (2FA) com TOTP via `robthree/twofactorauth`
- Geração de QR Code local via `chillerlan/php-qrcode` (sem serviços externos)
- Recovery codes (8 códigos únicos gerados no ativação do 2FA)
- Endpoint `POST /2fa/generate` — gera secret e QR Code
- Endpoint `POST /2fa/enable` — ativa 2FA com verificação do código
- Endpoint `POST /2fa/disable` — desativa 2FA com confirmação de senha
- Endpoint `POST /auth/2fa/verify` — verifica TOTP e emite tokens completos
- Suporte a escopos de JWT (`scope: "2fa"`, `scope: "email_verification"`)
- Verificação de e-mail via token JWT no signup
- Endpoint `GET /auth/verify-email` — verifica e-mail pelo token
- Atualização de perfil via `PUT /auth/me` com campos de artes marciais
- Campos de perfil: `belt`, `degree`, `last_graduation`, `academy_name`, `cpf`, `birth_date`, `gender`, `address`, `phone`
- Documentação OpenAPI via PHP Attributes (`zircote/swagger-php ^4.10`)

### Alterado
- `POST /auth/signup` agora requer JWT de autenticação (criação controlada pelo admin)
- `issueTokenForUser()` separado em método público para reuso (2FA + login padrão)
- Refresh Token TTL configurável via `security.refresh_token_ttl_days`

---

## [1.2.0]

### Adicionado
- Token Rotation no endpoint `POST /auth/refresh`
- Tabela `refresh_tokens` com hash SHA-256
- Endpoint `POST /auth/logout` com revogação de Refresh Token
- Bloqueio de conta após 3 tentativas falhas (`failed_login_attempts`)
- Reset de senha também desbloqueia contas bloqueadas

### Alterado
- Login agora retorna par `{accessToken, refreshToken}` em vez de apenas JWT

---

## [1.1.0]

### Adicionado
- Autenticação via Google OAuth 2.0 (`google/apiclient`)
- Endpoints `GET /auth/google` e `GET /auth/google/callback`
- Vinculação de conta Google a usuário existente por e-mail

---

## [1.0.0] — Versão Inicial

### Adicionado
- API REST em PHP 8.2 puro (sem framework)
- Arquitetura em camadas: Controller → Service → Repository
- Roteamento manual em `public/index.php`
- Endpoint `POST /auth/signup` — registro de usuário
- Endpoint `POST /auth/login` — autenticação com e-mail/senha
- Endpoint `GET /auth/me` — perfil do usuário autenticado
- JWT HS256 implementado manualmente (`JwtService`)
- Hash de senhas com Argon2ID
- Rate Limiting por IP via sistema de arquivos
- Recuperação de senha com token SHA-256 e TTL de 30 minutos
- Templates HTML de e-mail (boas-vindas, recuperação de senha)
- Containerização via Dockerfile (php:8.2-fpm)
- Documentação Swagger UI em `/swagger`
- Configuração via `vlucas/phpdotenv`
- CORS configurado dinamicamente

---

## 🗺️ Roadmap

Veja o [README.md](../README.md#roadmap) para a lista de funcionalidades planejadas.
