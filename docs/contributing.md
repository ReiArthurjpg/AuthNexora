# 🤝 Contribuindo — AuthNexora

> Guia para quem deseja contribuir com o projeto AuthNexora.

---

## Como Contribuir

Contribuições são bem-vindas! Seja para corrigir bugs, melhorar documentação, ou adicionar novas funcionalidades.

### 1. Fork o repositório

```bash
git clone https://github.com/ReiArthurjpg/AuthNexora.git
cd AuthNexora
```

### 2. Crie uma branch

Use a convenção abaixo:

| Tipo | Padrão | Exemplo |
|---|---|---|
| Feature | `feature/nome-da-feature` | `feature/apple-auth` |
| Bug fix | `fix/descricao-do-bug` | `fix/rate-limit-docker` |
| Documentação | `docs/o-que-foi-documentado` | `docs/api-endpoints` |
| Refactor | `refactor/o-que-foi-refatorado` | `refactor/jwt-service` |

```bash
git checkout -b feature/minha-nova-feature
```

### 3. Desenvolva seguindo os padrões

- PHP 8.2+ com `declare(strict_types=1)`
- Classes `final` por padrão (a menos que extensão seja necessária)
- Properties `readonly` onde possível
- Injeção de dependência via construtor
- Prepared Statements em **todas** as queries SQL

### 4. Abra um Pull Request

```
Título: [Tipo] Descrição curta e clara
Corpo: O que foi feito, por quê, e como testar
```

---

## Padrões de Código

### PHP

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;

final class ExemploController
{
    public function __construct(
        private readonly ExemploService $service
    ) {
    }

    public function metodo(): void
    {
        Response::json(['status' => 'ok']);
    }
}
```

### Nomenclatura

| Elemento | Convenção | Exemplo |
|---|---|---|
| Classes | PascalCase | `AuthController` |
| Métodos | camelCase | `findByEmail()` |
| Variáveis | camelCase | `$refreshToken` |
| Constantes | UPPER_SNAKE | `MAX_ATTEMPTS` |
| Arquivos | PascalCase (classes) | `AuthService.php` |

### Resposta da API

Use sempre `Response::json()` e `Response::error()`:

```php
// Sucesso
Response::json(['message' => 'OK'], 200);

// Erro
Response::error('CODIGO_ERRO', 'Mensagem humana', $details, 422);
```

---

## Adicionando um Novo Endpoint

1. **Adicione a rota** em `public/index.php`
2. **Crie o método** no controller apropriado (ou crie um novo controller)
3. **Adicione a anotação OpenAPI** em `src/Docs/OpenApi.php`
4. **Documente** em `docs/api.md`

### Exemplo de anotação OpenAPI

```php
#[OA\Post(
    path: "/novo-endpoint",
    tags: ["Tag"],
    summary: "Descrição curta",
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["campo"],
            properties: [
                new OA\Property(property: "campo", type: "string", example: "valor")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Sucesso"),
        new OA\Response(response: 401, description: "Não autenticado")
    ]
)]
```

---

## Reportar Bugs

Ao reportar um bug, inclua:

- Versão do PHP (`php -v`)
- Sistema operacional
- Passos para reproduzir
- Comportamento esperado vs. atual
- Logs relevantes

Abra uma issue em: [github.com/ReiArthurjpg/AuthNexora/issues](https://github.com/ReiArthurjpg/AuthNexora/issues)

---

## Ver também

- [architecture.md](architecture.md) — Entenda a arquitetura antes de contribuir
- [api.md](api.md) — Convenções da API
- [changelog.md](changelog.md) — Histórico de mudanças
