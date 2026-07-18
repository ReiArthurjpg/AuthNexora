# 🌐 API Reference — AuthNexora

> Referência completa de todos os endpoints da API, com método, autenticação, request body, respostas e exemplos cURL.

**Base URL (local):** `http://localhost:8080`

**Content-Type:** `application/json`

**Autenticação:** `Authorization: Bearer {accessToken}`

---

## Índice de Endpoints

| # | Método | Endpoint | Auth | Categoria |
|---|---|---|---|---|
| 1 | `GET` | [`/`](#1-get--health-check) | — | Sistema |
| 2 | `GET` | [`/swagger`](#2-get-swagger--swagger-ui) | — | Sistema |
| 3 | `GET` | [`/api-docs`](#3-get-api-docs--openapi-json) | — | Sistema |
| 4 | `POST` | [`/auth/signup`](#4-post-authsignup--criar-usuário) | JWT Admin | Auth |
| 5 | `POST` | [`/auth/login`](#5-post-authlogin--autenticar) | — | Auth |
| 6 | `GET` | [`/auth/me`](#6-get-authme--perfil-do-usuário) | JWT | Auth |
| 7 | `PUT` | [`/auth/me`](#7-put-authme--atualizar-perfil) | JWT | Auth |
| 8 | `POST` | [`/auth/logout`](#8-post-authlogout--logout) | — | Auth |
| 9 | `POST` | [`/auth/refresh`](#9-post-authrefresh--renovar-token) | — | Auth |
| 10 | `GET` | [`/auth/verify-email`](#10-get-authverify-email--verificar-e-mail) | — | Auth |
| 11 | `GET` | [`/auth/google`](#11-get-authgoogle--url-oauth-google) | — | OAuth |
| 12 | `GET` | [`/auth/google/callback`](#12-get-authgooglecallback--callback-oauth) | — | OAuth |
| 13 | `POST` | [`/auth/forgot-password`](#13-post-authforgot-password--solicitar-reset) | — | Senha |
| 14 | `GET` | [`/auth/reset-password/validate`](#14-get-authreset-passwordvalidate--validar-token) | — | Senha |
| 15 | `POST` | [`/auth/reset-password`](#15-post-authreset-password--redefinir-senha) | — | Senha |
| 16 | `POST` | [`/auth/2fa/verify`](#16-post-auth2faverify--verificar-código-totp) | JWT (scope=2fa) | 2FA |
| 17 | `POST` | [`/2fa/generate`](#17-post-2fagenerate--gerar-qr-code) | JWT | 2FA |
| 18 | `POST` | [`/2fa/enable`](#18-post-2faenable--ativar-2fa) | JWT | 2FA |
| 19 | `POST` | [`/2fa/disable`](#19-post-2fadisable--desativar-2fa) | JWT | 2FA |

---

## Padrão de Resposta

**Sucesso:**
```json
HTTP 200 OK
{ "chave": "valor" }
```

**Erro:**
```json
HTTP 4xx
{
  "error": {
    "code": "CODIGO_ERRO",
    "message": "Descrição humana do erro",
    "details": { "campo": ["mensagem de validação"] }
  }
}
```

### Códigos de Erro

| Código | HTTP | Descrição |
|---|---|---|
| `VALIDATION_ERROR` | 422 | Campos com formato inválido |
| `EMAIL_ALREADY_EXISTS` | 409 | E-mail já cadastrado |
| `INVALID_CREDENTIALS` | 401 | Senha incorreta |
| `ACCOUNT_LOCKED` | 403 | Conta bloqueada (3+ tentativas falhas) |
| `RATE_LIMIT` | 429 | Muitas tentativas no período |
| `INVALID_TOKEN` | 401 | JWT inválido, expirado ou escopo errado |
| `MISSING_TOKEN` | 400 | Token ausente no body |
| `UNAUTHORIZED` | 401 | Não autenticado |
| `INVALID_CODE` | 401 | Código TOTP inválido |
| `NOT_FOUND` | 404 | Endpoint não existe |
| `UNEXPECTED_ERROR` | 500 | Erro interno |

---

## 1. `GET /` — Health Check

Verifica se a API está no ar.

**Autenticação:** Nenhuma

**Resposta `200 OK`:**
```json
{
  "name": "Nexora Auth API",
  "version": "1.0.0",
  "status": "running"
}
```

```bash
curl http://localhost:8080/
```

---

## 2. `GET /swagger` — Swagger UI

Exibe a interface Swagger UI interativa no navegador.

**Autenticação:** Nenhuma | **Retorna:** HTML

```bash
# Abrir no navegador
http://localhost:8080/swagger
```

---

## 3. `GET /api-docs` — OpenAPI JSON

Retorna a especificação OpenAPI 3.0 em JSON, gerada dinamicamente via `zircote/swagger-php`.

**Autenticação:** Nenhuma | **Retorna:** `application/json`

```bash
curl http://localhost:8080/api-docs
```

---

## 4. `POST /auth/signup` — Criar Usuário

Cria um novo usuário no sistema e envia e-mail de boas-vindas com link de verificação.

**Autenticação:** `Authorization: Bearer {accessToken}` (usuário autenticado)

> [!IMPORTANT]
> Este endpoint requer autenticação. Apenas administradores logados podem criar novos usuários.

**Request Body:**
```json
{
  "name": "Maria Silva",
  "email": "maria@email.com",
  "academy_name": "Gracie Barra",
  "password": "SenhaForte@123",
  "confirmPassword": "SenhaForte@123",
  "phone": "+55 11 99999-9999",
  "birth_date": "1990-05-15",
  "gender": "Feminino",
  "cpf": "123.456.789-00",
  "address": "Rua das Flores, 123",
  "belt": "Azul",
  "degree": "2º Grau",
  "last_graduation": "2023-10-01"
}
```

**Campos obrigatórios:** `name`, `email`, `academy_name`, `password`, `confirmPassword`

**Resposta `201 Created`:**
```json
{
  "message": "Usuário criado com sucesso",
  "user": {
    "id": 42,
    "name": "Maria Silva",
    "email": "maria@email.com",
    "academy_name": "Gracie Barra",
    "phone": "+55 11 99999-9999",
    "belt": "Azul",
    "degree": "2º Grau"
  }
}
```

| Resposta | Código | Causa |
|---|---|---|
| Usuário criado | 201 | — |
| Não autenticado | 401 | JWT ausente ou inválido |
| Dados inválidos | 422 | Campos faltando ou formato errado |
| E-mail duplicado | 409 | E-mail já cadastrado |

```bash
curl -X POST http://localhost:8080/auth/signup \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{
    "name": "Maria Silva",
    "email": "maria@email.com",
    "academy_name": "Gracie Barra",
    "password": "SenhaForte@123",
    "confirmPassword": "SenhaForte@123"
  }'
```

---

## 5. `POST /auth/login` — Autenticar

Autentica o usuário com e-mail e senha. Se 2FA estiver ativo, retorna `tempToken`.

**Autenticação:** Nenhuma (Rate Limit: 5 req / 60s por IP)

**Request Body:**
```json
{
  "email": "maria@email.com",
  "password": "SenhaForte@123"
}
```

**Resposta `200 OK` (sem 2FA):**
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refreshToken": "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2",
  "tokenType": "Bearer",
  "expiresIn": 3600,
  "user": {
    "id": 42,
    "name": "Maria Silva",
    "email": "maria@email.com",
    "phone": "+55 11 99999-9999",
    "birth_date": "1990-05-15",
    "gender": "Feminino",
    "cpf": "123.456.789-00",
    "address": "Rua das Flores, 123",
    "belt": "Azul",
    "degree": "2º Grau",
    "last_graduation": "2023-10-01",
    "academy_name": "Gracie Barra"
  }
}
```

**Resposta `200 OK` (com 2FA ativo):**
```json
{
  "requires_2fa": true,
  "tempToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

| Resposta | Código | Causa |
|---|---|---|
| Login bem-sucedido | 200 | — |
| 2FA necessário | 200 | `is_two_factor_enabled = 1` |
| Credenciais inválidas | 401 | Senha errada ou usuário não existe |
| Conta bloqueada | 403 | `failed_login_attempts >= 3` |
| Rate limit | 429 | Mais de 5 tentativas em 60s |
| Dados inválidos | 422 | `email` ou `password` ausentes |

```bash
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "maria@email.com", "password": "SenhaForte@123"}'
```

---

## 6. `GET /auth/me` — Perfil do Usuário

Retorna os dados do usuário autenticado.

**Autenticação:** `Authorization: Bearer {accessToken}`

**Resposta `200 OK`:**
```json
{
  "id": 42,
  "name": "Maria Silva",
  "email": "maria@email.com",
  "phone": "+55 11 99999-9999",
  "birth_date": "1990-05-15",
  "gender": "Feminino",
  "cpf": "123.456.789-00",
  "address": "Rua das Flores, 123",
  "belt": "Azul",
  "degree": "2º Grau",
  "last_graduation": "2023-10-01",
  "academy_name": "Gracie Barra",
  "is_email_verified": 1,
  "is_two_factor_enabled": 0,
  "failed_login_attempts": 0
}
```

```bash
curl http://localhost:8080/auth/me \
  -H "Authorization: Bearer SEU_ACCESS_TOKEN"
```

---

## 7. `PUT /auth/me` — Atualizar Perfil

Atualiza os dados do perfil do usuário autenticado.

**Autenticação:** `Authorization: Bearer {accessToken}`

**Request Body:**
```json
{
  "name": "Maria Silva Atualizada",
  "academy_name": "Alliance BJJ",
  "phone": "+55 11 88888-8888",
  "birth_date": "1990-05-15",
  "gender": "Feminino",
  "cpf": "123.456.789-00",
  "address": "Av. Paulista, 1000",
  "belt": "Roxa",
  "degree": "1º Grau",
  "last_graduation": "2025-03-20"
}
```

**Campo obrigatório:** `name`

**Resposta `200 OK`:**
```json
{
  "message": "Perfil atualizado com sucesso",
  "user": { ... }
}
```

```bash
curl -X PUT http://localhost:8080/auth/me \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_ACCESS_TOKEN" \
  -d '{"name": "Maria Silva", "belt": "Roxa", "degree": "1º Grau"}'
```

---

## 8. `POST /auth/logout` — Logout

Revoga o Refresh Token, encerrando a sessão.

**Autenticação:** Nenhuma (o refreshToken é enviado no body)

**Request Body:**
```json
{
  "refreshToken": "a1b2c3d4e5f6..."
}
```

**Resposta `200 OK`:**
```json
{
  "message": "Logout realizado com sucesso"
}
```

> [!NOTE]
> O Access Token não é revogado. O cliente deve descartá-lo localmente.

```bash
curl -X POST http://localhost:8080/auth/logout \
  -H "Content-Type: application/json" \
  -d '{"refreshToken": "SEU_REFRESH_TOKEN"}'
```

---

## 9. `POST /auth/refresh` — Renovar Token

Renova o par de tokens (Token Rotation). O refresh token antigo é revogado.

**Autenticação:** Nenhuma

**Request Body:**
```json
{
  "refreshToken": "a1b2c3d4e5f6..."
}
```

**Resposta `200 OK`:**
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refreshToken": "novo_refresh_token_aqui...",
  "tokenType": "Bearer",
  "expiresIn": 3600,
  "user": { ... }
}
```

| Resposta | Código | Causa |
|---|---|---|
| Tokens renovados | 200 | — |
| Refresh token ausente | 400 | Campo `refreshToken` vazio |
| Token inválido | 401 | Token expirado, inválido ou já usado |

```bash
curl -X POST http://localhost:8080/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refreshToken": "SEU_REFRESH_TOKEN"}'
```

---

## 10. `GET /auth/verify-email` — Verificar E-mail

Valida o token de verificação enviado por e-mail no signup e marca o e-mail como verificado.

**Autenticação:** Nenhuma (token via query string)

**Query Params:**
| Param | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `token` | string | Sim | JWT com scope `email_verification` |

**Resposta `200 OK`:**
```json
{
  "message": "E-mail verificado com sucesso"
}
```

```bash
curl "http://localhost:8080/auth/verify-email?token=JWT_TOKEN_AQUI"
```

---

## 11. `GET /auth/google` — URL OAuth Google

Retorna a URL para redirecionar o usuário ao fluxo de login com Google.

**Autenticação:** Nenhuma

**Resposta `200 OK`:**
```json
{
  "url": "https://accounts.google.com/o/oauth2/auth?client_id=...&redirect_uri=...&scope=email+profile"
}
```

```bash
curl http://localhost:8080/auth/google
```

---

## 12. `GET /auth/google/callback` — Callback OAuth

Endpoint chamado pelo Google após autenticação. Redireciona para o frontend.

**Autenticação:** Nenhuma (chamado pelo Google)

**Query Params:**
| Param | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `code` | string | Sim | Código de autorização do Google |

**Sucesso:** Redireciona para `{FRONTEND_URL}/auth/callback?token=JWT_TOKEN`

**Erro:** Redireciona para `{FRONTEND_URL}/login?error=google_auth_failed&message=...`

---

## 13. `POST /auth/forgot-password` — Solicitar Reset

Solicita recuperação de senha. Envia e-mail com link de reset (TTL: 30 minutos).

**Autenticação:** Nenhuma (Rate Limit: 5 req / 60s por IP)

**Request Body:**
```json
{
  "email": "maria@email.com"
}
```

**Resposta `200 OK`:** *(sempre retorna 200, independente se e-mail existe — previne user enumeration)*
```json
{
  "message": "Se o e-mail existir, enviaremos instruções para redefinição."
}
```

```bash
curl -X POST http://localhost:8080/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email": "maria@email.com"}'
```

---

## 14. `GET /auth/reset-password/validate` — Validar Token

Verifica se um token de reset ainda é válido. Útil para UX (exibir formulário ou mensagem de erro).

**Autenticação:** Nenhuma

**Query Params:**
| Param | Tipo | Obrigatório |
|---|---|---|
| `token` | string | Sim |

**Resposta `200 OK`:**
```json
{ "valid": true }
```

**Resposta `400 Bad Request`:**
```json
{ "valid": false, "message": "Token inválido ou expirado" }
```

```bash
curl "http://localhost:8080/auth/reset-password/validate?token=TOKEN_AQUI"
```

---

## 15. `POST /auth/reset-password` — Redefinir Senha

Redefine a senha usando o token recebido por e-mail.

**Autenticação:** Nenhuma

**Request Body:**
```json
{
  "token": "token_recebido_no_email",
  "newPassword": "NovaSenha@456",
  "confirmPassword": "NovaSenha@456"
}
```

**Resposta `200 OK`:**
```json
{ "message": "Senha alterada com sucesso" }
```

| Resposta | Código | Causa |
|---|---|---|
| Senha alterada | 200 | — |
| Token inválido | 400 | Token expirado, usado ou inexistente |
| Dados inválidos | 422 | Senha fora da política ou senhas diferentes |

```bash
curl -X POST http://localhost:8080/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "token": "abc123def456",
    "newPassword": "NovaSenha@456",
    "confirmPassword": "NovaSenha@456"
  }'
```

---

## 16. `POST /auth/2fa/verify` — Verificar Código TOTP

Verifica o código TOTP após login e emite o par de tokens completo.

**Autenticação:** `Authorization: Bearer {tempToken}` (scope=2fa)

**Request Body:**
```json
{
  "code": "123456"
}
```

**Resposta `200 OK`:**
```json
{
  "accessToken": "...",
  "refreshToken": "...",
  "tokenType": "Bearer",
  "expiresIn": 3600,
  "user": { ... }
}
```

| Resposta | Código | Causa |
|---|---|---|
| Tokens emitidos | 200 | — |
| Scope incorreto | 401 | Token não tem `scope=2fa` |
| Código inválido | 401 | TOTP incorreto |
| Código ausente | 422 | Campo `code` vazio |

---

## 17. `POST /2fa/generate` — Gerar QR Code

Gera um novo secret TOTP e QR Code para configuração em um authenticator app.

**Autenticação:** `Authorization: Bearer {accessToken}`

**Resposta `200 OK`:**
```json
{
  "secret": "JBSWY3DPEHPK3PXP",
  "qrCode": "data:image/png;base64,iVBORw0KGgo...",
  "url": "otpauth://totp/AuthNexora:maria@email.com?secret=JBSWY3DPEHPK3PXP&issuer=AuthNexora"
}
```

> [!NOTE]
> O campo `qrCode` é um PNG em Base64 pronto para ser exibido como `<img src="data:image/png;base64,...">`.

---

## 18. `POST /2fa/enable` — Ativar 2FA

Ativa o 2FA após confirmar que o código TOTP está funcionando.

**Autenticação:** `Authorization: Bearer {accessToken}`

**Request Body:**
```json
{
  "secret": "JBSWY3DPEHPK3PXP",
  "code": "123456"
}
```

**Resposta `200 OK`:**
```json
{
  "message": "Autenticação de dois fatores ativada com sucesso.",
  "recoveryCodes": [
    "a1b2c3d4",
    "e5f6g7h8",
    "i9j0k1l2",
    "m3n4o5p6",
    "q7r8s9t0",
    "u1v2w3x4",
    "y5z6a7b8",
    "c9d0e1f2"
  ]
}
```

> [!CAUTION]
> Os `recoveryCodes` são exibidos **uma única vez**. Guarde-os em local seguro — não há como recuperá-los depois.

---

## 19. `POST /2fa/disable` — Desativar 2FA

Desativa o 2FA. Requer confirmação de senha para segurança.

**Autenticação:** `Authorization: Bearer {accessToken}`

**Request Body:**
```json
{
  "password": "SenhaForte@123"
}
```

**Resposta `200 OK`:**
```json
{
  "message": "Autenticação de dois fatores desativada com sucesso."
}
```

| Resposta | Código | Causa |
|---|---|---|
| 2FA desativado | 200 | — |
| Senha inválida | 401 | Senha incorreta |

---

## Ver também

- [authentication.md](authentication.md) — Fluxos de autenticação com diagramas
- [authorization.md](authorization.md) — JWT e escopos
