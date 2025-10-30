<?php

namespace Softadastra\Cache;

use Predis\Client;

class RedisCache
{
    private ?Client $client = null;
    private int $maxKeySizeBytes = 400_000; // 400 KB
    private int $maxMemoryBytes = 15 * 1024 * 1024; // 15 MB

    public function __construct(string $socketPath = '/home/sys/redis.sock')
    {
        if (getenv('APP_ENV') !== 'production' || !file_exists($socketPath)) {
            $this->client = null;
            return;
        }

        $this->client = new Client([
            'scheme' => 'unix',
            'path'   => $socketPath,
        ]);

        $this->deleteLargeKeys();
    }

    private function isEnabled(): bool
    {
        return $this->client !== null;
    }

    public function get(string $key): mixed
    {
        if (!$this->isEnabled()) return null;

        $value = $this->client->get($key);
        return $value ? json_decode($value, true) : null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        if (!$this->isEnabled()) return;
        $this->client->setex($key, $ttl, json_encode($value));
    }

    public function setSmart(string $key, mixed $value, int $ttl = 3600): void
    {
        if (!$this->isEnabled()) return;

        $encoded = json_encode($value);
        if (strlen($encoded) > $this->maxKeySizeBytes) {
            return;
        }

        $this->client->setex($key, $ttl, $encoded);
    }

    public function delete(string $key): void
    {
        if (!$this->isEnabled()) return;
        $this->client->del([$key]);
    }

    public function has(string $key): bool
    {
        return $this->isEnabled() && $this->client->exists($key) > 0;
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        if ($this->isEnabled()) {
            $value = $this->get($key);
            if ($value !== null) return $value;
        }

        $value = $callback();

        if ($this->isEnabled()) {
            $this->setSmart($key, $value, $ttl);
        }

        return $value;
    }

    public function deleteByPrefix(string $prefix): void
    {
        if (!$this->isEnabled()) return;

        $keys = $this->client->keys($prefix . '*');
        if (!empty($keys)) {
            $this->client->del($keys);
        }
    }

    public function getAllKeys(string $pattern = '*'): array
    {
        if (!$this->isEnabled()) return [];
        return $this->client->keys($pattern);
    }

    public function info(): array
    {
        if (!$this->isEnabled()) return [];
        return $this->client->info();
    }

    public function ttl(string $key): int
    {
        if (!$this->isEnabled()) return -1;
        return $this->client->ttl($key);
    }

    public function deleteLargeKeys(): void
    {
        if (!$this->isEnabled()) return;

        $keys = $this->getAllKeys();
        foreach ($keys as $key) {
            $raw = $this->client->get($key);
            if ($raw && strlen($raw) > $this->maxKeySizeBytes) {
                $this->delete($key);
            }
        }
    }

    public function getLargestKeys(int $limit = 5): array
    {
        if (!$this->isEnabled()) return [];

        $allKeys = $this->getAllKeys();
        $sizes = [];

        foreach ($allKeys as $key) {
            $raw = $this->client->get($key);
            if ($raw) {
                $sizes[$key] = strlen($raw);
            }
        }

        arsort($sizes);
        return array_slice($sizes, 0, $limit, true);
    }
}
