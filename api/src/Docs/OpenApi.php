<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(title: "Auth API", version: "1.0.0", description: "API de autenticação em PHP/MySQL")]
#[OA\Server(url: "http://localhost:8080", description: "Local (Port 8080)")]
#[OA\Server(url: "http://localhost:8081", description: "Local (Port 8081)")]
#[OA\Tag(name: "Auth", description: "Fluxo de autenticação")]
#[OA\SecurityScheme(securityScheme: "bearerAuth", type: "http", scheme: "bearer", bearerFormat: "JWT")]

#[OA\Post(
    path: "/auth/signup",
    tags: ["Auth"],
    summary: "Cria novo usuário",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name", "email", "academyName", "password", "confirmPassword"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "Maria Silva"),
                new OA\Property(property: "email", type: "string", format: "email", example: "maria@email.com"),
                new OA\Property(property: "academyName", type: "string", example: "Gracie Barra"),
                new OA\Property(property: "password", type: "string", format: "password", example: "SenhaForte@123"),
                new OA\Property(property: "confirmPassword", type: "string", format: "password", example: "SenhaForte@123")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: "Usuário criado"),
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
final class OpenApi
{
}
