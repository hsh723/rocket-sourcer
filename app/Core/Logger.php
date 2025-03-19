<?php

namespace RocketSourcer\Core;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;

class Logger implements LoggerInterface
{
    private string $path;
    private string $defaultChannel;
    private array $channels;
    private array $levels = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7,
    ];

    public function __construct(array $config)
    {
        $this->path = $config['path'] ?? __DIR__ . '/../../storage/logs';
        $this->defaultChannel = $config['default'] ?? 'daily';
        $this->channels = $config['channels'] ?? [];

        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        if (!isset($this->levels[$level])) {
            throw new RuntimeException("Undefined log level: {$level}");
        }

        $channel = $this->channels[$this->defaultChannel] ?? null;
        if (!$channel) {
            throw new RuntimeException("Undefined log channel: {$this->defaultChannel}");
        }

        $entry = $this->formatLogEntry($level, $message, $context);

        switch ($channel['driver']) {
            case 'daily':
                $this->writeDaily($entry, $channel);
                break;
            case 'single':
                $this->writeSingle($entry, $channel);
                break;
            default:
                throw new RuntimeException("Unsupported log driver: {$channel['driver']}");
        }
    }

    private function formatLogEntry(string $level, $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $message = $this->interpolate($message, $context);
        $contextJson = !empty($context) ? ' ' . json_encode($context) : '';

        return "[{$timestamp}] {$level}: {$message}{$contextJson}" . PHP_EOL;
    }

    private function interpolate($message, array $context): string
    {
        if (!is_string($message)) {
            return json_encode($message);
        }

        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }

    private function writeDaily(string $entry, array $channel): void
    {
        $filename = $channel['path'] ?? $this->path . '/app.log';
        $filename = str_replace('.log', '-' . date('Y-m-d') . '.log', $filename);
        
        $this->write($filename, $entry);
        $this->cleanup($filename, $channel['days'] ?? 7);
    }

    private function writeSingle(string $entry, array $channel): void
    {
        $filename = $channel['path'] ?? $this->path . '/app.log';
        $this->write($filename, $entry);
    }

    private function write(string $filename, string $entry): void
    {
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!file_put_contents($filename, $entry, FILE_APPEND | LOCK_EX)) {
            throw new RuntimeException("Could not write to log file: {$filename}");
        }
    }

    private function cleanup(string $currentFile, int $days): void
    {
        $pattern = str_replace(date('Y-m-d'), '*', $currentFile);
        $files = glob($pattern);

        foreach ($files as $file) {
            if (is_file($file)) {
                $fileDate = substr(basename($file), -14, 10); // Extract date from filename
                $fileTime = strtotime($fileDate);
                
                if ($fileTime && $fileTime < strtotime("-{$days} days")) {
                    @unlink($file);
                }
            }
        }
    }

    public function channel(string $channel): self
    {
        $new = clone $this;
        $new->defaultChannel = $channel;
        return $new;
    }

    public function getDefaultChannel(): string
    {
        return $this->defaultChannel;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }
} 