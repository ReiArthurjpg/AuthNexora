# 🔄 Workflows — AuthNexora

> Documentação detalhada de workflows completos: configuração de 2FA, reset de senha e verificação de e-mail.

---

## Workflow 1 — Configuração de 2FA do Zero

Este workflow descreve todos os passos necessários para um usuário configurar a autenticação de dois fatores.

```mermaid
sequenceDiagram
    actor U as Usuário
    participant FE as Frontend
    participant API as API
    participant APP as 📱 Authenticator

    Note over U,APP: PRÉ-REQUISITO: Usuário logado com accessToken válido

    U->>FE: Clica em "Ativar 2FA"

    rect rgb(20, 40, 60)
        Note over FE,API: ETAPA 1 — Gerar Secret
        FE->>API: POST /2fa/generate<br/>Auth: Bearer {accessToken}
        API-->>FE: {secret, qrCode (base64), url}
        FE->>U: Exibe QR Code na tela
    end

    rect rgb(20, 60, 40)
        Note over U,APP: ETAPA 2 — Escanear QR Code
        U->>APP: Escaneia QR Code
        APP-->>U: Exibe código TOTP (renova a cada 30s)
    end

    rect rgb(60, 40, 20)
        Note over U,API: ETAPA 3 — Confirmar e Ativar
        U->>FE: Digita código TOTP de 6 dígitos
        FE->>API: POST /2fa/enable<br/>{secret, code: "123456"}
        API->>API: Verifica TOTP
        alt Código correto
            API->>API: Gera 8 recovery codes
            API-->>FE: 200 {message, recoveryCodes[]}
            FE->>U: ⚠️ Exibe recovery codes (UMA VEZ APENAS)
            U->>U: Salva recovery codes em local seguro
        else Código errado
            API-->>FE: 400 INVALID_CODE
            FE->>U: "Código inválido, tente novamente"
        end
    end
```

### Checklist do Frontend

- [ ] Exibir QR Code como `<img src="data:image/png;base64,{qrCode}">`
- [ ] Dar opção de copiar o `secret` manualmente (para quem não consegue escanear)
- [ ] Após ativação, exibir os 8 recovery codes com destaque visual
- [ ] Incluir botão "Copiar todos os códigos" ou "Baixar como .txt"
- [ ] Confirmar que o usuário salvou os códigos antes de fechar o modal

---

## Workflow 2 — Login com 2FA Ativo

```mermaid
flowchart TD
    A["POST /auth/login\n{email, password}"] --> B{Credenciais válidas?}
    B -->|Não| C["401 INVALID_CREDENTIALS"]
    B -->|Sim| D{2FA ativo?}
    D -->|Não| E["200 {accessToken, refreshToken, user}"]
    D -->|Sim| F["200 {requires_2fa: true, tempToken}"]
    F --> G["Frontend exibe campo de código TOTP"]
    G --> H["POST /auth/2fa/verify\nAuthorization: Bearer tempToken\n{code: '123456'}"]
    H --> I{Código TOTP válido?}
    I -->|Não| J["401 INVALID_CODE"]
    I -->|Sim| K["200 {accessToken, refreshToken, user}"]
```

---

## Workflow 3 — Reset de Senha Completo

```mermaid
flowchart TD
    A["Usuário esqueceu a senha"] --> B["POST /auth/forgot-password\n{email}"]
    B --> C["200 - Resposta sempre igual\n(sem expor se e-mail existe)"]
    C --> D["📧 E-mail recebido com link\n(válido por 30 min)"]
    D --> E["Clica no link\n→ Frontend abre página de reset"]
    E --> F["GET /auth/reset-password/validate\n?token=TOKEN"]
    F --> G{Token válido?}
    G -->|Não| H["400 {valid: false}\nFrontend: 'Link expirado'"]
    G -->|Sim| I["200 {valid: true}\nFrontend: exibe formulário"]
    I --> J["POST /auth/reset-password\n{token, newPassword, confirmPassword}"]
    J --> K{Validações OK?}
    K -->|Não| L["422 VALIDATION_ERROR"]
    K -->|Sim| M{Token ainda válido?}
    M -->|Não| N["400 INVALID_TOKEN"]
    M -->|Sim| O["✅ Senha alterada\nConta desbloqueada\nToken marcado como usado"]
    O --> P["200 {message: 'Senha alterada com sucesso'}"]
    P --> Q["Frontend redireciona para /login"]
```

---

## Workflow 4 — Verificação de E-mail

```mermaid
sequenceDiagram
    actor U as Usuário
    participant API as API
    participant DB as Banco

    Note over U,DB: Disparado automaticamente após signup

    API->>DB: INSERT INTO users (...)
    API->>API: Gera JWT com scope=email_verification (TTL: 1h)
    API->>U: 📧 "Bem-vindo! Confirme seu e-mail"<br/>Link: /auth/verify-email?token=JWT

    U->>API: GET /auth/verify-email?token=JWT
    API->>API: JwtService.verify(token)
    API->>API: Checa scope == "email_verification"
    API->>DB: SELECT * FROM users WHERE id = claims.user_id

    alt is_email_verified = 0
        API->>DB: UPDATE users SET is_email_verified = 1
        API-->>U: 200 "E-mail verificado com sucesso"
    else Já verificado
        Note over API: Idempotente — não gera erro
        API-->>U: 200 "E-mail verificado com sucesso"
    end
```

> [!NOTE]
> **O link de verificação expira em 1 hora** (configurado em `JWT_EXPIRES_IN`). Não há funcionalidade de reenvio no momento — adicione ao roadmap se necessário.

---

## Workflow 5 — Gerenciamento de Sessão (Refresh Token)

```mermaid
sequenceDiagram
    participant C as Cliente
    participant API as API

    Note over C,API: Access Token expirou (após 1h)

    C->>API: GET /auth/me<br/>Authorization: Bearer {expirado}
    API-->>C: 401 Token expirado

    C->>API: POST /auth/refresh<br/>{refreshToken: "abc123..."}
    API->>API: sha256(refreshToken) → busca no banco
    alt Token válido (< 7 dias)
        API->>API: Deleta token antigo
        API->>API: Gera novo par (accessToken + refreshToken)
        API-->>C: 200 {accessToken, refreshToken, ...}
        C->>C: Salva novo refreshToken
        C->>API: Retenta GET /auth/me com novo accessToken
        API-->>C: 200 {dados do usuário}
    else Token expirado ou já usado
        API-->>C: 401 INVALID_TOKEN
        C->>C: Redireciona para /login
    end
```

---

## Ver também

- [authentication.md](authentication.md) — Diagramas detalhados de cada fluxo
- [api.md](api.md) — Referência dos endpoints usados nos workflows
- [security.md](security.md) — Segurança dos tokens
