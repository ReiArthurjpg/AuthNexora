<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Repositories\UserRepository;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use OpenApi\Annotations as OA;

final class TwoFactorAuthController
{
    private TwoFactorAuth $tfa;

    public function __construct(private readonly UserRepository $users)
    {
        $this->tfa = new TwoFactorAuth(new \App\Providers\ChillerlanQRCodeProvider(), 'AuthNexora');
    }

    public function generate(array $claims): void
    {
        $userId = (int) ($claims['sub'] ?? 0);
        $user = $this->users->findById($userId);

        if (!$user) {
            Response::error('USER_NOT_FOUND', 'Usuário não encontrado', [], 404);
            return;
        }

        $secret = $this->tfa->createSecret();
        $qrCodeUrl = $this->tfa->getQRCodeImageAsDataUri($user['email'], $secret);

        Response::json([
            'secret' => $secret,
            'qrCode' => $qrCodeUrl,
            'url' => "otpauth://totp/AuthNexora:" . urlencode($user['email']) . "?secret={$secret}&issuer=AuthNexora"
        ]);
    }

    public function enable(array $claims): void
    {
        $userId = (int) ($claims['sub'] ?? 0);
        $body = Request::jsonBody();

        if (empty($body['secret']) || empty($body['code'])) {
            Response::error('VALIDATION_ERROR', 'Secret e código são obrigatórios', [], 422);
            return;
        }

        if (!$this->tfa->verifyCode($body['secret'], $body['code'])) {
            Response::error('INVALID_CODE', 'Código 2FA inválido', [], 400);
            return;
        }

        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = bin2hex(random_bytes(4));
        }

        $this->users->updateTwoFactorSecret($userId, $body['secret']);
        $this->users->enableTwoFactor($userId, $recoveryCodes);

        Response::json([
            'message' => 'Autenticação de dois fatores ativada com sucesso.',
            'recoveryCodes' => $recoveryCodes
        ]);
    }

    public function disable(array $claims): void
    {
        $userId = (int) ($claims['sub'] ?? 0);
        $body = Request::jsonBody();
        $user = $this->users->findById($userId); // findById need to return password_hash to check password? No findById doesn't return password_hash.

        // Fix to get user with password to verify
        $userWithPassword = $this->users->findByEmail($user['email']);

        if (empty($body['password']) || !password_verify($body['password'], $userWithPassword['password_hash'])) {
            Response::error('INVALID_CREDENTIALS', 'Senha inválida', [], 401);
            return;
        }

        $this->users->disableTwoFactor($userId);

        Response::json([
            'message' => 'Autenticação de dois fatores desativada com sucesso.'
        ]);
    }
}
