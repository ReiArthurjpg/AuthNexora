<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(title: "NexoraAuth", version: "1.4", description: "API de autenticação em PHP/MySQL")]

#[OA\Server(url: "https://authnexora-api.onrender.com", description: "Production (Render)")]
#[OA\Server(url: "http://localhost:8080", description: "Local (Port 8080)")]
#[OA\Server(url: "http://localhost:8081", description: "Local (Port 8081)")]
#[OA\Tag(name: "Auth", description: "Fluxo de autenticação")]
#[OA\SecurityScheme(securityScheme: "bearerAuth", type: "http", scheme: "bearer", bearerFormat: "JWT")]

#[OA\Post(
    path: "/auth/signup",
    tags: ["Auth"],
    summary: "Cria novo usuário",
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name", "email", "password", "confirmPassword"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "Maria Silva"),
                new OA\Property(property: "email", type: "string", format: "email", example: "maria@email.com"),
                new OA\Property(property: "academy_name", type: "string", example: "Gracie Barra"),
                new OA\Property(property: "phone", type: "string", example: "+55 11 99999-9999"),
                new OA\Property(property: "birth_date", type: "string", format: "date", example: "1990-05-15"),
                new OA\Property(property: "gender", type: "string", example: "Feminino"),
                new OA\Property(property: "cpf", type: "string", example: "123.456.789-00"),
                new OA\Property(property: "address", type: "string", example: "Rua das Flores, 123"),
                new OA\Property(property: "belt", type: "string", example: "Azul"),
                new OA\Property(property: "degree", type: "string", example: "2º Grau"),
                new OA\Property(property: "last_graduation", type: "string", format: "date", example: "2023-10-01"),
                new OA\Property(property: "password", type: "string", format: "password", example: "SenhaForte@123"),
                new OA\Property(property: "confirmPassword", type: "string", format: "password", example: "SenhaForte@123")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: "Usuário criado"),
        new OA\Response(response: 401, description: "Não autenticado"),
        new OA\Response(response: 422, description: "Dados inválidos"),
        new OA\Response(response: 409, description: "E-mail já cadastrado")
    ]
)]

#[OA\Post(
    path: "/auth/login",
    tags: ["Auth"],
    summary: "Autentica usuário",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "maria@email.com"),
                new OA\Property(property: "password", type: "string", format: "password", example: "SenhaForte@123")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Login realizado com sucesso"),
        new OA\Response(response: 401, description: "Credenciais inválidas"),
        new OA\Response(response: 429, description: "Muitas tentativas (Rate Limit)")
    ]
)]

#[OA\Get(
    path: "/auth/me",
    tags: ["Auth"],
    summary: "Retorna usuário autenticado",
    security: [["bearerAuth" => []]],
    responses: [
        new OA\Response(response: 200, description: "Usuário atual"),
        new OA\Response(response: 401, description: "Não autenticado")
    ]
)]

#[OA\Put(
    path: "/auth/me",
    tags: ["Auth"],
    summary: "Atualiza dados do usuário autenticado",
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "name", type: "string", example: "Maria Silva"),
                new OA\Property(property: "academy_name", type: "string", example: "Gracie Barra"),
                new OA\Property(property: "phone", type: "string", example: "+55 11 99999-9999"),
                new OA\Property(property: "birth_date", type: "string", format: "date", example: "1990-05-15"),
                new OA\Property(property: "gender", type: "string", example: "Feminino"),
                new OA\Property(property: "cpf", type: "string", example: "123.456.789-00"),
                new OA\Property(property: "address", type: "string", example: "Rua das Flores, 123"),
                new OA\Property(property: "belt", type: "string", example: "Azul"),
                new OA\Property(property: "degree", type: "string", example: "2º Grau"),
                new OA\Property(property: "last_graduation", type: "string", format: "date", example: "2023-10-01")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Perfil atualizado com sucesso"),
        new OA\Response(response: 401, description: "Não autenticado"),
        new OA\Response(response: 422, description: "Dados inválidos")
    ]
)]

#[OA\Post(
    path: "/auth/logout",
    tags: ["Auth"],
    summary: "Logout",
    security: [["bearerAuth" => []]],
    responses: [
        new OA\Response(response: 200, description: "Logout realizado")
    ]
)]

#[OA\Post(
    path: "/auth/forgot-password",
    tags: ["Auth"],
    summary: "Solicita recuperação de senha",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "maria@email.com")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Instruções enviadas"),
        new OA\Response(response: 422, description: "Dados inválidos"),
        new OA\Response(response: 429, description: "Muitas tentativas")
    ]
)]

#[OA\Post(
    path: "/auth/reset-password",
    tags: ["Auth"],
    summary: "Redefine senha",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["token", "newPassword", "confirmPassword"],
            properties: [
                new OA\Property(property: "token", type: "string", example: "abc-123-uuid"),
                new OA\Property(property: "newPassword", type: "string", format: "password", example: "NovaSenha@123"),
                new OA\Property(property: "confirmPassword", type: "string", format: "password", example: "NovaSenha@123")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Senha alterada com sucesso"),
        new OA\Response(response: 400, description: "Token inválido ou expirado"),
        new OA\Response(response: 422, description: "Dados inválidos")
    ]
)]

#[OA\Get(
    path: "/auth/reset-password/validate",
    tags: ["Auth"],
    summary: "Valida token de recuperação",
    parameters: [
        new OA\Parameter(name: "token", in: "query", required: true, schema: new OA\Schema(type: "string"))
    ],
    responses: [
        new OA\Response(response: 200, description: "Token válido"),
        new OA\Response(response: 400, description: "Token inválido ou expirado")
    ]
)]

#[OA\Get(
    path: "/auth/google",
    tags: ["Auth"],
    summary: "Retorna a URL de redirecionamento do Google",
    responses: [
        new OA\Response(response: 200, description: "URL do Google gerada com sucesso")
    ]
)]

#[OA\Get(
    path: "/auth/google/callback",
    tags: ["Auth"],
    summary: "Callback da autenticação Google",
    parameters: [
        new OA\Parameter(name: "code", in: "query", required: true, description: "Código retornado pelo Google", schema: new OA\Schema(type: "string"))
    ],
    responses: [
        new OA\Response(response: 200, description: "Autenticação bem sucedida"),
        new OA\Response(response: 401, description: "Autenticação falhou")
    ]
)]
#[OA\Post(
    path: "/auth/2fa/verify",
    tags: ["Auth"],
    summary: "Verifica o código 2FA e retorna o token de acesso final",
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["code"],
            properties: [
                new OA\Property(property: "code", type: "string", example: "123456")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Sucesso")
    ]
)]

#[OA\Post(
    path: "/2fa/generate",
    tags: ["2FA"],
    summary: "Gera uma nova chave secreta e QR Code para 2FA",
    security: [["bearerAuth" => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: "Sucesso",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "secret", type: "string", example: "JBSWY3DPEHPK3PXP"),
                    new OA\Property(property: "qrCode", type: "string", example: "data:image/png;base64,..."),
                    new OA\Property(property: "url", type: "string", example: "otpauth://totp/AuthNexora...")
                ]
            )
        )
    ]
)]

#[OA\Post(
    path: "/2fa/enable",
    tags: ["2FA"],
    summary: "Ativa o 2FA",
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["secret", "code"],
            properties: [
                new OA\Property(property: "secret", type: "string", example: "JBSWY3DPEHPK3PXP"),
                new OA\Property(property: "code", type: "string", example: "123456")
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Ativado com sucesso",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "message", type: "string", example: "Autenticação de dois fatores ativada com sucesso."),
                    new OA\Property(property: "recoveryCodes", type: "array", items: new OA\Items(type: "string"))
                ]
            )
        )
    ]
)]

#[OA\Post(
    path: "/2fa/disable",
    tags: ["2FA"],
    summary: "Desativa o 2FA",
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["password"],
            properties: [
                new OA\Property(property: "password", type: "string", format: "password", example: "SenhaForte@123")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Desativado com sucesso"),
        new OA\Response(response: 401, description: "Senha inválida")
    ]
)]
#[OA\Get(
    path: "/auth/verify-email",
    tags: ["Auth"],
    summary: "Verifica e-mail do usuário",
    parameters: [
        new OA\Parameter(name: "token", in: "query", required: true, description: "Token de verificação enviado por e-mail", schema: new OA\Schema(type: "string"))
    ],
    responses: [
        new OA\Response(response: 200, description: "E-mail verificado com sucesso"),
        new OA\Response(response: 400, description: "Token ausente"),
        new OA\Response(response: 401, description: "Token inválido ou expirado")
    ]
)]

#[OA\Post(
    path: "/auth/refresh",
    tags: ["Auth"],
    summary: "Renova o Access Token usando um Refresh Token",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["refreshToken"],
            properties: [
                new OA\Property(property: "refreshToken", type: "string", example: "seu_refresh_token_aqui")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Tokens renovados com sucesso"),
        new OA\Response(response: 400, description: "Refresh token ausente"),
        new OA\Response(response: 401, description: "Refresh token inválido ou expirado")
    ]
)]
final class OpenApi
{
}
