# 🛡️ Autorização — AuthNexora

> Como funciona a proteção de rotas, o sistema JWT, escopos de token e o middleware de autenticação.

---

## JWT — JSON Web Token

O AuthNexora usa **JWT com algoritmo HS256** (HMAC-SHA256), implementado de forma manual no `JwtService`. Não há biblioteca externa para JWT — apenas criptografia nativa do PHP.

### Estrutura do Token

```
Header.Payload.Signature
```

**Header:**
```json
{
  "alg": "HS256",
  "typ": "JWT"
}
```

**Payload (Access Token padrão):**
```json
{
  "iss": "authnexora-api",
  "iat": 1721260800,
  "exp": 1721264400,
  "sub": "42"
}
```

**Payload (Token 2FA — scope intermediário):**
```json
{
  "iss": "authnexora-api",
  "iat": 1721260800,
  "exp": 1721264400,
  "sub": "42",
  "scope": "2fa"
}
```

**Payload (Token de verificação de e-mail):**
```json
{
  "iss": "authnexora-api",
  "iat": 1721260800,
  "exp": 1721264400,
  "sub": "42",
  "user_id": 42,
  "scope": "email_verification"
}
```

---

## Claims Padrão

| Claim | Tipo | Descrição |
|---|---|---|
| `iss` | string | Issuer — configurado em `JWT_ISSUER` |
| `iat` | int | Issued At — timestamp Unix de emissão |
| `exp` | int | Expiration — `iat + JWT_EXPIRES_IN` |
| `sub` | string | Subject — ID do usuário como string |
| `scope` | string | (Opcional) Escopo do token |
| `user_id` | int | (Apenas em email_verification) |

---

## Escopos de Token

O sistema utiliza o campo `scope` para restringir o uso de tokens temporários:

| Scope | Token | Endpoint autorizado | Descrição |
|---|---|---|---|
| *(ausente)* | Access Token | Rotas protegidas em geral | Token de sessão completo |
| `"2fa"` | Temp Token | `POST /auth/2fa/verify` apenas | Aguardando verificação TOTP |
| `"email_verification"` | Verify Token | `GET /auth/verify-email` apenas | Confirmação de cadastro |

### Como o escopo é verificado

```php
// Em AuthController::verify2fa()
if (($claims['scope'] ?? '') !== '2fa') {
    Response::error('INVALID_TOKEN', 'Token inválido para esta operação', [], 401);
    return;
}

// Em AuthController::verifyEmail()
if (($claims['scope'] ?? '') !== 'email_verification') {
    throw new \RuntimeException('Token inválido');
}
```

> [!IMPORTANT]
> Um Access Token padrão (sem `scope`) **não pode** ser usado para verificar 2FA ou e-mail, e vice-versa.

---

## AuthMiddleware

O middleware é chamado manualmente no `index.php` para rotas protegidas:

```php
// Arquivo: src/Middleware/AuthMiddleware.php

final class AuthMiddleware
{
    public function __construct(private readonly JwtService $jwt) {}

    public function authenticate(?string $token): array
    {
        if (!$token) {
            throw new RuntimeException('Token ausente');
        }
        return $this->jwt->verify($token);
    }
}
```

O método `authenticate()` lança exceções que são capturadas pelo bloco `catch (Throwable $e)` global no `index.php`, retornando HTTP 401.

### Como o Bearer Token é extraído

```php
// Arquivo: src/Helpers/Request.php

public static function bearerToken(): ?string
{
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches) !== 1) {
        return null;
    }
    return trim($matches[1]);
}
```

---

## Classificação das Rotas

```mermaid
graph LR
    subgraph "🔓 Rotas Públicas"
        P1["GET /"]
        P2["GET /swagger"]
        P3["GET /api-docs"]
        P4["POST /auth/login"]
        P5["POST /auth/forgot-password"]
        P6["GET /auth/reset-password/validate"]
        P7["POST /auth/reset-password"]
        P8["GET /auth/verify-email"]
        P9["POST /auth/refresh"]
        P10["GET /auth/google"]
        P11["GET /auth/google/callback"]
        P12["POST /auth/logout"]
    end

    subgraph "🔐 Rotas Protegidas (JWT)"
        R1["GET /auth/me"]
        R2["PUT /auth/me"]
        R3["POST /2fa/generate"]
        R4["POST /2fa/enable"]
        R5["POST /2fa/disable"]
        R6["POST /auth/signup ⚙️ Admin"]
    end

    subgraph "🔑 Escopo Específico"
        S1["POST /auth/2fa/verify<br/>(scope=2fa)"]
    end
```

> [!NOTE]
> `POST /auth/logout` é tecnicamente pública — não valida JWT. O cliente apenas envia o refresh token para revogação.

---

## Verificação JWT — JwtService

```mermaid
flowchart TD
    A["Bearer token recebido"] --> B{"Token tem 3 partes<br/>(h.p.s)?"}
    B -->|Não| C["RuntimeException: Token inválido"]
    B -->|Sim| D["Recalcula signature = base64url(HMAC-SHA256(h.p, secret))"]
    D --> E{"hash_equals(signature, s)?"}
    E -->|Não| F["RuntimeException: Token inválido"]
    E -->|Sim| G["Decodifica payload (base64url)"]
    G --> H{"payload.exp < time()?"}
    H -->|Sim| I["RuntimeException: Token expirado"]
    H -->|Não| J["Retorna claims[]"]
```

---

## Segurança do JWT

| Aspecto | Implementação |
|---|---|
| Algoritmo | HS256 (HMAC-SHA256) |
| Comparação | `hash_equals()` — resistente a timing attacks |
| Chave secreta | `JWT_SECRET` via variável de ambiente |
| TTL padrão | 3600 segundos (1 hora) |
| Renovação | Via Refresh Token (endpoint `/auth/refresh`) |
| Revogação | Access Token não é revogável; Refresh Token sim |

> [!WARNING]
> O Access Token JWT **não pode ser revogado** antes de expirar. Se um token for comprometido, o atacante terá acesso até o TTL expirar (máximo 1h). Para mitigação, mantenha o `JWT_EXPIRES_IN` curto (recomendado: 900–3600 segundos).

---

## Como Usar um Token (Cliente)

### Requisição autenticada

```bash
curl -X GET https://api.nexora.com/auth/me \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

### Estratégia recomendada no frontend

```typescript
// 1. Fazer login e salvar tokens
const { accessToken, refreshToken } = await api.post('/auth/login', credentials);
localStorage.setItem('refreshToken', refreshToken);
// accessToken em memória (não localStorage por segurança)

// 2. Interceptor: renovar automaticamente quando expirar
api.interceptors.response.use(null, async (error) => {
  if (error.response?.status === 401) {
    const { accessToken: newToken } = await api.post('/auth/refresh', {
      refreshToken: localStorage.getItem('refreshToken')
    });
    // Retry com novo token
  }
});
```

---

## Ver também

- [authentication.md](authentication.md) — Fluxos completos de autenticação
- [middleware.md](middleware.md) — Detalhes do AuthMiddleware
- [security.md](security.md) — Análise de segurança
