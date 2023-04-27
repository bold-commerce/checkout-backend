<?php

declare(strict_types=1);

namespace App\Constants;

class Constants
{
    public const FLAG_LOADTIME = 'LOADTIME';
    public const FLAG_LOG = 'LOG';

    public const LOG_EXCEPTION = 'EXCEPTION';
    public const LOG_WARNING = 'WARNING';
    public const LOG_INFO = 'INFO';
    public const LOG_TRACE = 'TRACE';

    public const APP_ENV_LOCAL = 'local';
    public const APP_ENV_STAGING = 'staging';
    public const APP_ENV_PRODUCTION = 'production';
}
