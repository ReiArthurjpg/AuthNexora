<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class JwtService
{
    public function __construct(
        private readonly string $secret,
        private readonly string $issuer,
        private readonly int $expiresIn
    ) {
    }

    public function issueToken(array $payload): string
    {
        $now = time();

        $claims = [
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $now + $this->expiresIn,
            'sub' => (string) $payload['user_id'],
        ];

        return $this->encode($claims);
    }

    public function verify(string $token): array
    {
        [$h, $p, $s] = explode('.', $token);
        $signature = $this->base64UrlEncode(hash_hmac('sha256', "$h.$p", $this->secret, true));
        if (!hash_equals($signature, $s)) {
            throw new RuntimeException('Token inválido');
        }

        $payload = json_decode($this->base64UrlDecode($p), true);
        if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) {
            throw new RuntimeException('Token expirado');
        }

        return $payload;
    }

    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode((string) json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = $this->base64UrlEncode((string) json_encode($payload));
        $sig = $this->base64UrlEncode(hash_hmac('sha256', "$header.$body", $this->secret, true));

        return "$header.$body.$sig";
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
