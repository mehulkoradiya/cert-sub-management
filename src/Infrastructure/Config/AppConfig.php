<?php
declare(strict_types=1);

namespace App\Infrastructure\Config;

final class AppConfig
{
    private static ?AppConfig $instance = null;

    private array $config;

    private function __construct()
    {
        $path = __DIR__ . '/../../../config/config.php';
        $this->config = require $path;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

