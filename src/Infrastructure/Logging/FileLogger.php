<?php
declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Infrastructure\Config\AppConfig;

final class FileLogger
{
    public function error(string $message): void
    {
        $this->log('ERROR', $message);
    }

    public function info(string $message): void
    {
        $this->log('INFO', $message);
    }

    private function log(string $level, string $message): void
    {
        $config = AppConfig::getInstance();
        $file = (string) $config->get('logging.file', __DIR__ . '/../../../app.log');
        $date = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $line = sprintf("[%s] %s: %s\n", $date, $level, $message);
        file_put_contents($file, $line, FILE_APPEND);
    }
}

