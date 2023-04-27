<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Constants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoggingService
{
    public function getLevelLogging(): Collection
    {
        if (Str::contains(config('app.flags'), Constants::FLAG_LOG)) {
            $result = collect(explode(',', config('logging.log')))->map(function ($item) {
                return trim($item);
            });
            return $result->filter();
        }

        return collect([]);
    }

    public function isLoggingWarning(): bool
    {
        return $this->getLevelLogging()->contains(Constants::LOG_WARNING);
    }

    public function isLoggingException(): bool
    {
        return $this->getLevelLogging()->contains(Constants::LOG_EXCEPTION);
    }

    public function isLoggingInfo(): bool
    {
        return $this->getLevelLogging()->contains(Constants::LOG_INFO);
    }

    public function isLoggingTrace(): bool
    {
        return $this->getLevelLogging()->contains(Constants::LOG_TRACE);
    }

    public function exception(string $message, array $context = []): void
    {
        Log::error(Constants::LOG_EXCEPTION.': '.$message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        Log::info(Constants::LOG_INFO.': '.$message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        Log::warning(Constants::LOG_WARNING.': '.$message, $context);
    }

    public function trace(string $message, array $context = []): void
    {
        Log::info(Constants::LOG_TRACE.': '.$message, $context);
    }
}
