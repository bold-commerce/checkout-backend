<?php

declare(strict_types=1);

namespace App\Facades;

use App\Constants\Constants;
use App\Services\LoggingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection getLevelLogging()
 * @method static bool       isLoggingWarning()
 * @method static bool       isLoggingException()
 * @method static bool       isLoggingInfo()
 * @method static bool       isLoggingTrace()
 * @method static bool       warning(string $message, array $context = [])
 * @method static bool       exception(string $message, array $context = [])
 * @method static bool       info(string $message, array $context = [])
 * @method static bool       trace(string $message, array $context = [])
 * @method static void       log(string $message, array &$context, string $loggingType = Constants::LOG_EXCEPTION)
 */
class Logging extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LoggingService::class;
    }
}
