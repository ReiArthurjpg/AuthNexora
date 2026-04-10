# Auth API (PHP + MySQL)

## Rodando localmente

1. Instale dependências:
   ```bash
   composer install
   ```
2. Crie o banco MySQL e rode:
   ```bash
   mysql -u root -p authnexora < database/schema.sql
   ```
3. Suba a API:
   ```bash
   php -S localhost:8080 -t public
   ```

> Porta recomendada para a API local: **8080** (evita conflito com Next.js em 3000 e Swagger UI em 8081/8082).

## Gerar OpenAPI

```bash
./vendor/bin/openapi ./src -o ./public/openapi.json
```

## Endpoints

- `POST /auth/signup`
- `POST /auth/login`
- `GET /auth/me`
- `POST /auth/logout`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /auth/reset-password/validate?token=...`

## Swagger UI

Sirva `public/openapi.json` e aponte no Swagger UI estático.
