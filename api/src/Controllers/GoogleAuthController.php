<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Repositories\UserRepository;
use App\Services\GoogleAuthService;
use App\Services\JwtService;
use Exception;

final class GoogleAuthController
{
    public function __construct(
        private readonly GoogleAuthService $googleAuth,
        private readonly UserRepository $users,
        private readonly JwtService $jwt
    ) {
    }

    public function login(): void
    {
        $url = $this->googleAuth->getAuthUrl();
        Response::json(['url' => $url]);
    }

    public function callback(): void
    {
        $code = $_GET['code'] ?? null;

        if (!$code) {
            Response::error('MISSING_CODE', 'Código de autenticação ausente', [], 400);
            return;
        }

        try {
            $googleUser = $this->googleAuth->authenticate($code);
            
            // 1. Tenta encontrar pelo google_id
            $user = $this->users->findByGoogleId($googleUser['google_id']);

            if (!$user) {
                // 2. Tenta encontrar pelo email
                $user = $this->users->findByEmail($googleUser['email']);

                if ($user) {
                    // Vincula a conta Google ao usuário existente
                    $this->users->linkGoogleAccount((int) $user['id'], $googleUser['google_id']);
                } else {
                    // 3. Cria novo usuário
                    $user = $this->users->create(
                        $googleUser['name'],
                        $googleUser['email'],
                        null, // Sem senha inicial
                        $googleUser['google_id']
                    );
                }
            }

            // Gera o token JWT
            $token = $this->jwt->issueToken(['user_id' => (int) $user['id']]);

            Response::json([
                'accessToken' => $token,
                'tokenType' => 'Bearer',
                'expiresIn' => 3600,
                'user' => [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                ],
            ]);

        } catch (Exception $e) {
            Response::error('AUTH_FAILED', $e->getMessage(), [], 401);
        }
    }
}
