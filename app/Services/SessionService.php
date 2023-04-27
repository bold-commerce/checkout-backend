<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Session;

class SessionService
{
    protected ShopService $shopService;

    public function __construct(ShopService $shopService)
    {
        $this->shopService = $shopService;
    }

    /**
     * @param mixed $value
     */
    public function put(string $key, $value)
    {
        Session::put($this->getKey($key), $value);
    }

    /**
     * @return array
     */
    public function all()
    {
        return Session::get($this->getPrefix(), []);
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Session::get($this->getKey($key), $default);
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function pull(string $key, $default = null)
    {
        return Session::pull($this->getKey($key), $default);
    }

    /**
     * @return bool
     */
    public function has(string $key)
    {
        return Session::has($this->getKey($key));
    }

    public function forget(string $key)
    {
        Session::forget($this->getKey($key));
    }

    public function flush()
    {
        Session::forget($this->getPrefix());
        Session::save();
    }

    /**
     * @return string
     */
    protected function getPrefix()
    {
        $shop = $this->shopService->getInstance();
        $platformType = $shop->getShop()->getPlatformType();
        $platformId = str_replace('.', '-', $shop->getShop()->getPlatformDomain());

        return $platformType.'.'.$platformId;
    }

    /**
     * @return string
     */
    protected function getKey(string $key)
    {
        return $this->getPrefix().'.'.$key;
    }
}
