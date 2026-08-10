# 🗃️ Repositories — AuthNexora

> Referência de todos os repositories do sistema com as queries SQL utilizadas e comportamentos documentados.

---

## Visão Geral

Os repositories encapsulam **todo o acesso ao banco de dados**. Utilizam PDO com Prepared Statements em 100% das queries. Retornam arrays associativos ou `null`.

---

## UserRepository

**Arquivo:** [`src/Repositories/UserRepository.php`](../api/src/Repositories/UserRepository.php)

O repository central do sistema. Gerencia todas as operações de leitura e escrita na tabela `users`.

### Métodos

#### `findByEmail(string $email): ?array`

```sql
SELECT * FROM users WHERE email = :email LIMIT 1
```

> E-mail normalizado em **lowercase** antes da query.

#### `findByGoogleId(string $googleId): ?array`

```sql
SELECT * FROM users WHERE google_id = :google_id LIMIT 1
```

#### `findById(int $id): ?array`

```sql
SELECT id, name, email, phone, birth_date, gender, cpf, address,
       belt, degree, last_graduation, academy_name,
       is_email_verified, is_two_factor_enabled, failed_login_attempts
FROM users WHERE id = :id LIMIT 1
```

> [!NOTE]
> `findById` **não retorna** `password_hash`, `two_factor_secret` nem outros campos sensíveis. Use `findByEmail` quando precisar verificar senha.

#### `create(array $data): array`

```sql
INSERT INTO users (name, email, password_hash, google_id, academy_name,
                   phone, birth_date, gender, cpf, address,
                   belt, degree, last_graduation, created_by)
VALUES (:name, :email, :password_hash, :google_id, :academy_name, ...)
```

Retorna os dados do usuário criado (sem `password_hash`).

#### `update(int $userId, array $data): void`

```sql
UPDATE users
SET name = :name, academy_name = :academy_name, phone = :phone,
    birth_date = :birth_date, gender = :gender, cpf = :cpf,
    address = :address, belt = :belt, degree = :degree,
    last_graduation = :last_graduation, updated_by = :updated_by
WHERE id = :id
```

#### `updatePassword(int $userId, string $passwordHash): void`

```sql
UPDATE users SET password_hash = :password_hash WHERE id = :id
```

#### `linkGoogleAccount(int $userId, string $googleId): void`

```sql
UPDATE users SET google_id = :google_id WHERE id = :id
```

#### `verifyEmail(int $userId): void`

```sql
UPDATE users SET is_email_verified = 1 WHERE id = :id
```

#### `updateTwoFactorSecret(int $userId, string $secret): void`

```sql
UPDATE users SET two_factor_secret = :secret WHERE id = :id
```

#### `enableTwoFactor(int $userId, array $recoveryCodes): void`

```sql
UPDATE users
SET is_two_factor_enabled = 1, two_factor_recovery_codes = :codes
WHERE id = :id
```

> Recovery codes são serializados como JSON.

#### `disableTwoFactor(int $userId): void`

```sql
UPDATE users
SET is_two_factor_enabled = 0,
    two_factor_secret = NULL,
    two_factor_recovery_codes = NULL
WHERE id = :id
```

#### `incrementFailedLogin(int $userId): void`

```sql
UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = :id
```

#### `resetFailedLogin(int $userId): void`

```sql
UPDATE users SET failed_login_attempts = 0 WHERE id = :id
```

---

## RefreshTokenRepository

**Arquivo:** [`src/Repositories/RefreshTokenRepository.php`](../api/src/Repositories/RefreshTokenRepository.php)

Gerencia os refresh tokens de sessão. Todos os tokens são armazenados como **hash SHA-256**.

### Métodos

#### `create(int $userId, string $tokenHash, DateTimeImmutable $expiresAt): void`

```sql
INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
VALUES (:user_id, :token_hash, :expires_at)
```

#### `findValidByHash(string $tokenHash): ?array`

```sql
SELECT * FROM refresh_tokens
WHERE token_hash = :token_hash AND expires_at > NOW()
LIMIT 1
```

#### `deleteByHash(string $tokenHash): void`

```sql
DELETE FROM refresh_tokens WHERE token_hash = :token_hash
```

#### `deleteAllForUser(int $userId): void`

```sql
DELETE FROM refresh_tokens WHERE user_id = :user_id
```

> Útil para "sair de todos os dispositivos" — não chamado pelos controllers atualmente, mas disponível para uso futuro.

---

## PasswordResetRepository

**Arquivo:** [`src/Repositories/PasswordResetRepository.php`](../api/src/Repositories/PasswordResetRepository.php)

Gerencia os tokens de recuperação de senha com TTL e uso único.

### Métodos

#### `create(int $userId, string $tokenHash, DateTimeImmutable $expiresAt): void`

```sql
-- 1. Invalida tokens anteriores (antes de inserir o novo)
DELETE FROM password_resets WHERE user_id = :user_id AND used_at IS NULL;

-- 2. Insere o novo token
INSERT INTO password_resets (user_id, token_hash, expires_at)
VALUES (:user_id, :token_hash, :expires_at)
```

#### `findValidByHash(string $tokenHash): ?array`

```sql
SELECT pr.*, u.name, u.email
FROM password_resets pr
INNER JOIN users u ON u.id = pr.user_id
WHERE pr.token_hash = :token_hash
  AND pr.used_at IS NULL
  AND pr.expires_at > NOW()
ORDER BY pr.id DESC
LIMIT 1
```

> Inclui `JOIN` com `users` para retornar nome e e-mail do usuário no mesmo resultado.

#### `markUsed(int $id): void`

```sql
UPDATE password_resets SET used_at = NOW() WHERE id = :id
```

---

## Convenções dos Repositories

| Convenção | Detalhe |
|---|---|
| Retorno | `array` (dados encontrados) ou `null` (não encontrado) |
| Fetch mode | `PDO::FETCH_ASSOC` — arrays associativos |
| Parâmetros | Named parameters (`:email`, `:id`) — sem posicionais |
| E-mails | Sempre normalizados em `mb_strtolower()` antes de salvar/buscar |
| Senhas | **Nunca** tratadas nos repositories — responsabilidade dos services |

---

## Ver também

- [database.md](database.md) — Schema completo das tabelas
- [services.md](services.md) — Como os repositories são usados pelos services
- [security.md](security.md) — Proteção contra SQL Injection
