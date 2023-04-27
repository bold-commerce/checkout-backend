<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\InvalidTokenException;
use App\Exceptions\ShopNotFoundException;
use App\Exceptions\ShopTokenNotFoundException;
use App\Facades\Logging;
use App\Services\ShopApiTokenService;
use App\Services\ShopService;
use Closure;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ValidateToken
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string[] ...$guards
     *
     * @return mixed
     *
     */
    public function handle($request, Closure $next, ...$guards): mixed
    {
        try {
            $shopToken = $request->header('X-Bold-Api-Token', '');
            $shopDomain = $request->header('X-Bold-Shop-Domain', '');
            $shopService = App::make(ShopService::class);
            $shopApiTokenService = App::make(ShopApiTokenService::class);

            $shop = $shopService->getShopByIdentifierOrDomain($shopDomain);
            $shopApiTokenService->verifyToken($shop, $shopToken);
        } catch (ShopNotFoundException|InvalidTokenException $e) {
            abort(500);
        }

        return $next($request);
    }
}
