<?php

declare(strict_types=1);

namespace App\Services;

final class RateLimitService
{
    private string $storage;

    public function __construct(private readonly int $maxAttempts, private readonly int $windowSeconds)
    {
        $this->storage = __DIR__ . '/../../storage/rate_limit';
        if (!is_dir($this->storage)) {
            @mkdir($this->storage, 0777, true);
        }
    }

    public function hit(string $key): bool
    {
        $file = $this->storage . '/' . sha1($key) . '.json';
        $now = time();
        $data = ['count' => 0, 'started_at' => $now];

        if (file_exists($file)) {
            $decoded = json_decode(file_get_contents($file) ?: '', true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        if (($now - (int) $data['started_at']) > $this->windowSeconds) {
            $data = ['count' => 0, 'started_at' => $now];
        }

        $data['count'] = ((int) $data['count']) + 1;
        file_put_contents($file, json_encode($data));

        return ((int) $data['count']) <= $this->maxAttempts;
    }
}
