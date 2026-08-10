# 🔧 Troubleshooting — AuthNexora

> Guia de resolução dos problemas mais comuns ao executar e integrar com o AuthNexora.

---

## Problemas de Banco de Dados

### `SQLSTATE[HY000] [2002] Connection refused`

**Causa:** O MySQL não está rodando ou o `DB_HOST` está incorreto.

**Soluções:**
```bash
# XAMPP: verifique se MySQL está iniciado no painel XAMPP
# Docker: verifique se o container MySQL está up
docker compose ps

# Verifique a conexão
mysql -h localhost -P 3306 -u root -p
```

**Variáveis a verificar:**
```env
DB_HOST=localhost   # Para XAMPP. Para Docker use o nome do service (ex: mysql)
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=        # Pode ser vazio no XAMPP
```

---

### `SQLSTATE[42S02] Table 'authnexora.users' doesn't exist`

**Causa:** O schema SQL não foi importado.

**Solução:**
```bash
mysql -u root -p authnexora < 001_initial_schema.sql
```

Ou use o schema incluso em [`docs/database.md`](database.md).

---

### `SQLSTATE[23000] Duplicate entry` no signup

**Causa:** E-mail já cadastrado.

**Verificar:**
```sql
SELECT id, email FROM users WHERE email = 'email@exemplo.com';
```

---

## Problemas de JWT

### `401 Token ausente`

**Causa:** Header `Authorization` não enviado ou mal formatado.

**Formato correto:**
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Erros comuns:**
- `Authorization: eyJ...` (sem "Bearer ")
- `authorization: Bearer eyJ...` (header em lowercase — funciona no PHP via `$_SERVER['HTTP_AUTHORIZATION']`)

---

### `401 Token inválido`

**Causas possíveis:**
1. `JWT_SECRET` diferente entre emissão e verificação
2. Token corrompido na transmissão
3. Token de outro ambiente (ex: token de dev usado em produção)

**Verificar:**
```bash
# Decodifique o token em jwt.io para inspecionar o payload
# Certifique-se de que JWT_SECRET é o mesmo nos dois ambientes
```

---

### `401 Token expirado`

**Causa:** O access token ultrapassou o `JWT_EXPIRES_IN`.

**Solução:** Use o endpoint `/auth/refresh`:
```bash
curl -X POST http://localhost:8080/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refreshToken": "SEU_REFRESH_TOKEN"}'
```

---

## Problemas de E-mail

### E-mail não enviado (sem erro visível)

**Causa:** Falha silenciosa no `EmailService` — exceções SMTP são capturadas e ignoradas.

**Diagnóstico:**
1. Verifique credenciais SMTP no `.env`
2. Para Gmail: certifique-se de usar **senha de app** (não a senha da conta)
3. Teste com Mailtrap para ambiente de desenvolvimento

**Teste rápido:**
```php
<?php
require 'vendor/autoload.php';
$mail = new PHPMailer\PHPMailer\PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->Port = 465;
$mail->SMTPAuth = true;
$mail->Username = 'seu@gmail.com';
$mail->Password = 'senha_de_app';
$mail->SMTPSecure = 'ssl';
$mail->setFrom('seu@gmail.com', 'Teste');
$mail->addAddress('destino@email.com');
$mail->Subject = 'Teste';
$mail->Body = 'Funcionou!';
$mail->send();
echo 'Enviado!';
```

---

### `SMTP Error: Could not connect to SMTP host`

**Soluções:**
- Verifique se a porta 465 (SSL) ou 587 (TLS) não está bloqueada pelo firewall
- No XAMPP Windows, às vezes o Outlook/antivírus bloqueia conexões SMTP
- Use Mailtrap para desenvolvimento local: `MAIL_PORT=2525`

---

## Problemas de CORS

### `Access to XMLHttpRequest blocked by CORS policy`

**Causa:** A origem do frontend não está sendo aceita.

**Verificar:**
1. O frontend está enviando o header `Origin`?
2. O `FRONTEND_URL` no `.env` corresponde à origem real do frontend?

**Debug:**
```bash
curl -v -H "Origin: http://localhost:3000" http://localhost:8080/auth/login
# Verifique o header Access-Control-Allow-Origin na resposta
```

**Solução temporária (desenvolvimento):**
O sistema já aceita qualquer `Origin` em requisições sem validação. Se o problema persiste, verifique se o Nginx/Apache não está sobrescrevendo os headers CORS.

---

## Problemas de Rate Limiting

### `429 RATE_LIMIT` inesperado

**Causa:** Muitas requisições do mesmo IP em 60 segundos.

**Limpar manualmente:**
```bash
# Deletar arquivos de rate limit
rm -rf api/storage/rate_limit/*.json

# Windows PowerShell
Remove-Item "C:\caminho\para\api\storage\rate_limit\*.json"
```

---

### Rate limit não funciona (em Docker multi-servidor)

**Causa:** O rate limiter usa arquivos locais — não compartilhado entre containers.

**Solução:** Veja [security.md](security.md) — seção "Known Issues".

---

## Problemas de 2FA

### `401 INVALID_CODE` mesmo com código correto

**Causas:**
1. **Relógio desincronizado** — O TOTP é baseado em tempo. Se o relógio do servidor e do dispositivo estiverem mais de 30 segundos defasados, o código será inválido.
2. **Secret incorreto** — Certifique-se de que o mesmo `secret` usado no `generate` foi enviado no `enable`

**Verificar relógio do servidor:**
```bash
date
# Sincronizar (Linux):
ntpdate -s time.google.com
```

---

### QR Code não aparece no Authenticator

**Causa:** A URL `otpauth://` pode estar com caracteres não escapados.

**Verificar:** Use a URL `otpauth://totp/AuthNexora:{email}?secret={secret}&issuer=AuthNexora` diretamente no app de authenticator ou em [totp.app](https://totp.app).

---

## Problemas de Permissão

### `Permission denied: /storage/rate_limit`

**Causa:** O processo PHP não tem permissão de escrita no diretório.

```bash
# Linux/Mac:
chmod -R 777 api/storage/rate_limit

# Docker:
docker compose exec api chown -R www-data:www-data /var/www/storage
```

---

## Problemas com `.htaccess` (XAMPP)

### `404 Not Found` em todas as rotas (exceto `/`)

**Causa:** `mod_rewrite` não está ativo no Apache.

**Verificar `httpd.conf`:**
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

**E na configuração do Virtual Host:**
```apache
AllowOverride All
```

---

## Logs

### Onde encontrar os logs

| Ambiente | Localização |
|---|---|
| XAMPP | `C:\xampp\apache\logs\error.log` |
| Docker | `docker compose logs api` |
| PHP direto | `api/server.log` (se configurado) |

> [!NOTE]
> O arquivo `api/server.log` está listado no `.gitignore`. Em produção, configure `error_log` no `php.ini` para um caminho adequado.

---

## Ver também

- [deployment.md](deployment.md) — Configuração de ambientes
- [environment.md](environment.md) — Variáveis de ambiente
- [security.md](security.md) — Known issues documentados
