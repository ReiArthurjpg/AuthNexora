<?php

declare(strict_types=1);

/**
 * @OA\Info(
 *   title="Auth API",
 *   version="1.0.0",
 *   description="API de autenticação em PHP/MySQL"
 * )
 *
 * @OA\Server(
 *   url="http://localhost:8080",
 *   description="Local"
 * )
 *
 * @OA\Tag(name="Auth", description="Fluxo de autenticação")
 *
 * @OA\Post(
 *   path="/auth/signup",
 *   tags={"Auth"},
 *   summary="Cria novo usuário",
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"name","email","password","confirmPassword"},
 *       @OA\Property(property="name", type="string", example="Maria Silva"),
 *       @OA\Property(property="email", type="string", format="email", example="maria@email.com"),
 *       @OA\Property(property="password", type="string", format="password", example="SenhaForte@123"),
 *       @OA\Property(property="confirmPassword", type="string", format="password", example="SenhaForte@123")
 *     )
 *   ),
 *   @OA\Response(response=201, description="Usuário criado")
 * )
 *
 * @OA\Post(
 *   path="/auth/login",
 *   tags={"Auth"},
 *   summary="Autentica usuário",
 *   @OA\Response(response=200, description="Login realizado")
 * )
 *
 * @OA\Get(
 *   path="/auth/me",
 *   tags={"Auth"},
 *   summary="Retorna usuário autenticado",
 *   security={{"bearerAuth":{}}},
 *   @OA\Response(response=200, description="Usuário atual")
 * )
 *
 * @OA\Post(
 *   path="/auth/logout",
 *   tags={"Auth"},
 *   summary="Logout",
 *   security={{"bearerAuth":{}}},
 *   @OA\Response(response=200, description="Logout realizado")
 * )
 *
 * @OA\Post(
 *   path="/auth/forgot-password",
 *   tags={"Auth"},
 *   summary="Solicita recuperação de senha",
 *   @OA\Response(response=200, description="Resposta genérica")
 * )
 *
 * @OA\Post(
 *   path="/auth/reset-password",
 *   tags={"Auth"},
 *   summary="Redefine senha",
 *   @OA\Response(response=200, description="Senha alterada")
 * )
 *
 * @OA\Get(
 *   path="/auth/reset-password/validate",
 *   tags={"Auth"},
 *   summary="Valida token",
 *   @OA\Parameter(name="token", in="query", required=true, @OA\Schema(type="string")),
 *   @OA\Response(response=200, description="Token válido")
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT"
 * )
 */
final class OpenApi
{
}
