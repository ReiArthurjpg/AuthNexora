<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\JwtService;
use RuntimeException;

final class AuthMiddleware
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function authenticate(?string $token): array
    {
        if (!$token) {
            throw new RuntimeException('Token ausente');
        }

        return $this->jwt->verify($token);
    }
}
