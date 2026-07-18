# 🔐 Autenticação — AuthNexora

> Documentação completa de todos os fluxos de autenticação suportados pelo sistema, com diagramas de sequência.

---

## Índice

1. [Registro de Usuário (Signup)](#1-registro-de-usuário-signup)
2. [Login com E-mail e Senha](#2-login-com-e-mail-e-senha)
3. [Login com 2FA (TOTP)](#3-login-com-2fa-totp)
4. [Renovação de Token (Refresh)](#4-renovação-de-token-refresh)
5. [Logout](#5-logout)
6. [Verificação de E-mail](#6-verificação-de-e-mail)
7. [Recuperação de Senha](#7-recuperação-de-senha)
8. [Autenticação via Google OAuth](#8-autenticação-via-google-oauth)
9. [Configuração de 2FA](#9-configuração-de-2fa)

---

## 1. Registro de Usuário (Signup)

> [!IMPORTANT]
> O endpoint `/auth/signup` **requer autenticação JWT**. Apenas usuários já autenticados (administradores) podem criar novos usuários. Este é um design intencional para sistemas com cadastro controlado.

```mermaid
sequenceDiagram
    participant Admin as 👤 Admin
    participant API as AuthNexora API
    participant DB as MySQL
    participant Mail as 📧 SMTP

    Admin->>API: POST /auth/signup<br/>Authorization: Bearer {adminToken}<br/>{name, email, password, academy_name, ...}

    API->>API: AuthMiddleware: valida JWT admin
    API->>API: Valida campos obrigatórios
    API->>DB: findByEmail(email)

    alt E-mail já existe
        DB-->>API: user encontrado
        API-->>Admin: 409 EMAIL_ALREADY_EXISTS
    else E-mail novo
        DB-->>API: null
        API->>API: password_hash(password, ARGON2ID)
        API->>DB: INSERT INTO users (...)
        DB-->>API: {id, name, email, ...}
        API->>API: Gera JWT com scope=email_verification
        API->>Mail: sendWelcomeEmail(email, name, verifyLink)
        Mail-->>Admin: 📧 E-mail de boas-vindas enviado
        API-->>Admin: 201 {message, user}
    end
```

### Campos obrigatórios no signup

| Campo | Validação |
|---|---|
| `name` | Mínimo 3 caracteres |
| `email` | Formato válido (`FILTER_VALIDATE_EMAIL`) |
| `academy_name` | Mínimo 2 caracteres |
| `password` | 8+ chars, maiúscula, minúscula, número e símbolo |
| `confirmPassword` | Deve ser igual a `password` |

### Campos opcionais

`phone`, `birth_date`, `gender`, `cpf`, `address`, `belt`, `degree`, `last_graduation`

---

## 2. Login com E-mail e Senha

```mermaid
sequenceDiagram
    participant C as 👤 Usuário
    participant API as AuthNexora API
    participant DB as MySQL
    participant RL as RateLimitService

    C->>API: POST /auth/login<br/>{email, password}

    API->>RL: hit("login:" + IP)
    alt Rate limit excedido (> 5/60s)
        RL-->>API: false
        API-->>C: 429 RATE_LIMIT
    else Dentro do limite
        RL-->>API: true
        API->>DB: findByEmail(email)

        alt Usuário não existe
            DB-->>API: null
            API-->>C: 401 INVALID_CREDENTIALS
        else Usuário encontrado
            DB-->>API: user
            API->>API: Verifica failed_login_attempts >= 3
            alt Conta bloqueada
                API-->>C: 403 ACCOUNT_LOCKED
            else Conta ativa
                API->>API: password_verify(password, hash)
                alt Senha incorreta
                    API->>DB: incrementFailedLogin(userId)
                    API-->>C: 401 INVALID_CREDENTIALS
                else Senha correta
                    API->>DB: resetFailedLogin(userId) se havia falhas
                    alt 2FA ativado
                        API->>API: Gera tempToken (scope=2fa)
                        API-->>C: 200 {requires_2fa: true, tempToken}
                    else Sem 2FA
                        API->>API: issueToken({user_id})
                        API->>API: Gera refreshToken aleatório (32 bytes)
                        API->>DB: INSERT refresh_tokens (user_id, sha256(token), expires_at)
                        API-->>C: 200 {accessToken, refreshToken, tokenType, expiresIn, user}
                    end
                end
            end
        end
    end
```

### Resposta de login bem-sucedido (sem 2FA)

```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refreshToken": "a1b2c3d4e5f6...",
  "tokenType": "Bearer",
  "expiresIn": 3600,
  "user": {
    "id": 1,
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

### Resposta quando 2FA está ativo

```json
{
  "requires_2fa": true,
  "tempToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

> [!NOTE]
> O `tempToken` é um JWT com `scope: "2fa"` e TTL padrão (1h). Ele **não concede acesso** a nenhum recurso protegido além do endpoint `/auth/2fa/verify`.

---

## 3. Login com 2FA (TOTP)

Este fluxo é a **continuação do login** quando `requires_2fa: true`.

```mermaid
sequenceDiagram
    participant C as 👤 Usuário
    participant API as AuthNexora API
    participant DB as MySQL
    participant App2FA as 📱 Authenticator App

    Note over C,App2FA: Usuário já recebeu tempToken do login
    App2FA->>C: Exibe código TOTP de 6 dígitos

    C->>API: POST /auth/2fa/verify<br/>Authorization: Bearer {tempToken}<br/>{code: "123456"}

    API->>API: AuthMiddleware: valida tempToken
    API->>API: Verifica claims.scope === "2fa"
    alt scope inválido
        API-->>C: 401 INVALID_TOKEN
    else scope correto
        API->>DB: findById(claims.sub)
        API->>API: tfa->verifyCode(user.two_factor_secret, code)
        alt Código inválido
            API-->>C: 401 INVALID_CODE
        else Código válido
            API->>API: issueTokenForUser(user)
            API->>DB: INSERT refresh_tokens
            API-->>C: 200 {accessToken, refreshToken, tokenType, expiresIn, user}
        end
    end
```

---

## 4. Renovação de Token (Refresh)

O AuthNexora implementa **Token Rotation**: a cada renovação, o refresh token antigo é revogado e um novo par é emitido.

```mermaid
sequenceDiagram
    participant C as 👤 Cliente
    participant API as AuthNexora API
    participant DB as MySQL

    C->>API: POST /auth/refresh<br/>{refreshToken: "abc123..."}

    API->>API: hash("sha256", refreshToken)
    API->>DB: findValidByHash(hash)
    note right of DB: WHERE token_hash = ? AND expires_at > NOW()

    alt Token inválido ou expirado
        DB-->>API: null
        API-->>C: 401 INVALID_TOKEN
    else Token válido
        DB-->>API: {id, user_id, expires_at}
        API->>DB: deleteByHash(hash) ← revoga token antigo
        API->>DB: findById(user_id)
        API->>API: issueToken({user_id}) ← novo access token
        API->>API: Gera novo refreshToken (32 bytes random)
        API->>DB: INSERT novo refresh_token (sha256)
        API-->>C: 200 {accessToken, refreshToken, tokenType, expiresIn, user}
    end
```

> [!IMPORTANT]
> **Token Rotation** significa que um refresh token só pode ser usado **uma única vez**. Após uso, ele é deletado do banco. Qualquer tentativa de reutilizá-lo retornará `401 INVALID_TOKEN`.

---

## 5. Logout

```mermaid
sequenceDiagram
    participant C as 👤 Cliente
    participant API as AuthNexora API
    participant DB as MySQL

    C->>API: POST /auth/logout<br/>{refreshToken: "abc123..."}

    alt refreshToken fornecido
        API->>API: hash("sha256", refreshToken)
        API->>DB: deleteByHash(hash)
        DB-->>API: OK
    else sem refreshToken
        Note over API: Silencioso — sem erro
    end

    API-->>C: 200 {message: "Logout realizado com sucesso"}
```

> [!NOTE]
> O logout **não invalida o Access Token JWT** (que expira naturalmente em 1h). Para segurança máxima, o cliente deve descartar o access token localmente após o logout.

---

## 6. Verificação de E-mail

```mermaid
sequenceDiagram
    participant U as 👤 Usuário
    participant Mail as 📧 Caixa de E-mail
    participant API as AuthNexora API
    participant DB as MySQL

    Note over U,DB: Após o signup, e-mail de verificação é enviado automaticamente

    Mail->>U: 📧 "Bem-vindo à Nexora — Confirme seu e-mail"<br/>Link: /auth/verify-email?token=JWT_TOKEN

    U->>API: GET /auth/verify-email?token=JWT_TOKEN

    API->>API: JwtService->decodeToken(token)
    API->>API: Verifica claims.scope === "email_verification"

    alt Token inválido ou expirado
        API-->>U: 401 INVALID_TOKEN
    else scope inválido
        API-->>U: 401 INVALID_TOKEN
    else Token válido
        API->>DB: findById(claims.user_id)
        alt Usuário não encontrado
            API-->>U: 401 Usuário não encontrado
        else is_email_verified já = 1
            Note over API: Idempotente — não faz nada
            API-->>U: 200 "E-mail verificado com sucesso"
        else is_email_verified = 0
            API->>DB: UPDATE users SET is_email_verified = 1
            API-->>U: 200 "E-mail verificado com sucesso"
        end
    end
```

> [!NOTE]
> O token de verificação de e-mail é um **JWT comum** com o campo adicional `scope: "email_verification"` e `user_id`. Ele usa o mesmo TTL configurado em `JWT_EXPIRES_IN` (padrão: 1h).

---

## 7. Recuperação de Senha

```mermaid
sequenceDiagram
    participant U as 👤 Usuário
    participant API as AuthNexora API
    participant DB as MySQL
    participant Mail as 📧 SMTP

    Note over U,Mail: ETAPA 1 — Solicitar reset
    U->>API: POST /auth/forgot-password<br/>{email: "usuario@email.com"}

    API->>API: RateLimitService->hit("forgot:" + IP)
    alt Rate limit excedido
        API-->>U: 429 RATE_LIMIT
    else
        API->>API: Valida formato do e-mail
        API->>DB: findByEmail(email)

        alt Usuário não encontrado
            Note over API: Silencioso por segurança (evita user enumeration)
            API-->>U: 200 "Se o e-mail existir, enviaremos instruções..."
        else Usuário encontrado
            API->>API: Gera token = bin2hex(random_bytes(32))
            API->>API: hash = sha256(token)
            API->>DB: DELETE tokens anteriores não usados do usuário
            API->>DB: INSERT password_resets (user_id, hash, expires_at=+30min)
            API->>Mail: sendForgotPassword(email, name, resetLink)
            API-->>U: 200 "Se o e-mail existir, enviaremos instruções..."
        end
    end

    Note over U,Mail: ETAPA 2 — Validar token (opcional, para UX)
    U->>API: GET /auth/reset-password/validate?token=TOKEN_BRUTO
    API->>API: sha256(token)
    API->>DB: findValidByHash(hash)
    note right of DB: WHERE used_at IS NULL AND expires_at > NOW()
    alt Token válido
        API-->>U: 200 {valid: true}
    else
        API-->>U: 400 {valid: false}
    end

    Note over U,Mail: ETAPA 3 — Redefinir senha
    U->>API: POST /auth/reset-password<br/>{token, newPassword, confirmPassword}
    API->>API: Valida política de senha
    API->>API: sha256(token)
    API->>DB: findValidByHash(hash)
    alt Token inválido/expirado/usado
        API-->>U: 400 INVALID_TOKEN
    else Token válido
        API->>API: password_hash(newPassword, ARGON2ID)
        API->>DB: updatePassword(userId, hash)
        API->>DB: markUsed(resetId) — marca used_at = NOW()
        API->>DB: resetFailedLogin(userId) ← desbloqueia conta
        API-->>U: 200 "Senha alterada com sucesso"
    end
```

> [!TIP]
> O reset de senha também **reseta o contador de tentativas falhas** (`failed_login_attempts = 0`), desbloqueando contas que foram bloqueadas por brute force.

---

## 8. Autenticação via Google OAuth

```mermaid
sequenceDiagram
    participant U as 👤 Usuário
    participant FE as 🖥️ Frontend
    participant API as AuthNexora API
    participant G as 🔵 Google

    FE->>API: GET /auth/google
    API->>G: Gera authUrl (scopes: email, profile)
    API-->>FE: 200 {url: "https://accounts.google.com/o/oauth2/auth?..."}
    FE->>U: Redireciona para URL do Google

    U->>G: Login no Google + autoriza
    G->>API: Redireciona GET /auth/google/callback?code=AUTH_CODE

    API->>G: fetchAccessTokenWithAuthCode(code)
    G-->>API: access_token
    API->>G: userinfo.get()
    G-->>API: {id, email, name, picture}

    API->>DB: findByGoogleId(google_id)
    alt Usuário com google_id encontrado
        DB-->>API: user
    else
        API->>DB: findByEmail(email)
        alt Usuário com e-mail encontrado
            DB-->>API: user
            API->>DB: UPDATE users SET google_id = ? WHERE id = ?
            note right of API: Vincula conta Google ao usuário existente
        else E-mail não cadastrado
            API-->>FE: Redirect /login?error=google_auth_failed
        end
    end

    API->>API: jwt->issueToken({user_id})
    API-->>FE: Redirect /auth/callback?token=JWT_ACCESS_TOKEN
```

> [!WARNING]
> O fluxo OAuth emite apenas um **Access Token** (sem Refresh Token persistido no banco). O usuário precisará fazer novo login OAuth quando o token expirar.

> [!IMPORTANT]
> Novos usuários **não podem** se cadastrar via Google. Apenas usuários já cadastrados no sistema (por e-mail) podem vincular/usar a conta Google.

---

## 9. Configuração de 2FA

```mermaid
sequenceDiagram
    participant U as 👤 Usuário
    participant API as AuthNexora API
    participant App as 📱 Google Authenticator

    Note over U,App: ETAPA 1 — Gerar secret e QR Code
    U->>API: POST /2fa/generate<br/>Authorization: Bearer {accessToken}
    API->>API: tfa->createSecret()
    API->>API: getQRCodeImageAsDataUri(email, secret)
    API-->>U: 200 {secret, qrCode (base64 PNG), url (otpauth://...)}

    U->>App: Escaneia QR Code
    App-->>U: Exibe código TOTP de 6 dígitos

    Note over U,App: ETAPA 2 — Ativar 2FA
    U->>API: POST /2fa/enable<br/>Authorization: Bearer {accessToken}<br/>{secret, code: "123456"}
    API->>API: tfa->verifyCode(secret, code)
    alt Código inválido
        API-->>U: 400 INVALID_CODE
    else Código válido
        API->>API: Gera 8 recovery codes (bin2hex(random_bytes(4)))
        API->>DB: UPDATE users SET two_factor_secret = ?
        API->>DB: UPDATE users SET is_two_factor_enabled = 1, recovery_codes = JSON
        API-->>U: 200 {message, recoveryCodes: [...]}
    end

    Note over U,App: ETAPA 3 — Desativar 2FA (requer senha)
    U->>API: POST /2fa/disable<br/>Authorization: Bearer {accessToken}<br/>{password: "SenhaForte@123"}
    API->>DB: findByEmail(user.email) ← busca com password_hash
    API->>API: password_verify(password, hash)
    alt Senha inválida
        API-->>U: 401 INVALID_CREDENTIALS
    else Senha válida
        API->>DB: UPDATE users SET is_two_factor_enabled = 0, two_factor_secret = NULL, recovery_codes = NULL
        API-->>U: 200 "2FA desativado com sucesso"
    end
```

> [!IMPORTANT]
> Os **recovery codes** são gerados no momento da ativação e exibidos **uma única vez**. O usuário deve salvá-los em local seguro. Não há forma de recuperá-los depois.

---

## Ver também

- [authorization.md](authorization.md) — JWT, escopos e proteção de rotas
- [security.md](security.md) — Análise de segurança dos fluxos
- [api.md](api.md) — Referência completa dos endpoints
