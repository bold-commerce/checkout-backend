<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\AbstractCheckoutException;
use App\Models\FrontendShop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateShopInfos
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string[] ...$guards
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards): mixed
    {
        try {
            $shopDomain = $request->route()->parameter('shopDomain');
            $shop = new FrontendShop();
            $shop->populate($shopDomain);
            App()->instance(FrontendShop::class, $shop);
        } catch (AbstractCheckoutException $e) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

        return $next($request);
    }
}
