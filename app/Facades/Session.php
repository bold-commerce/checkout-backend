<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\SessionService;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Illuminate\Session\SessionManager
 * @see \Illuminate\Session\Store
 *
 * @method static void    put(string $key, $value)
 * @method static mixed   all()
 * @method static mixed   get(string $key, $default = null)
 * @method static mixed   pull(string $key, $default = null)
 * @method static boolean has(string $key)
 * @method static void    forget(string $key)
 * @method static void    flush()
 */
class Session extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return SessionService::class;
    }
}
