<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\Constants;
use App\Constants\Events;
use App\Exceptions\AbstractCheckoutException;
use App\Facades\Logging;
use App\Models\FrontendShop;
use App\Services\EndpointService;
use App\Services\EventsService;
use App\Services\ExperienceService;
use App\Services\ShopApiTokenService;
use App\Services\ShopAssetsService;
use App\Services\ShopService;
use App\Services\ShopUrlService;
use App\Services\UserService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class AbstractController extends BaseController
{
    protected ExperienceService $experienceService;
    protected EndpointService $endpointService;
    protected ShopService $shopService;
    protected ShopApiTokenService $shopApiTokenService;
    protected ShopAssetsService $shopAssetsService;
    protected ShopUrlService $shopUrlsService;
    protected EventsService $eventsService;
    protected UserService $userService;

    protected array $eventsList = [];

    protected string $log = '';
    protected string $flags = '';

    protected FrontendShop $shop;

    public function __construct(
        ExperienceService $experienceService,
        EndpointService $endpointService,
        ShopService $shopService,
        ShopApiTokenService $shopApiTokenService,
        ShopAssetsService $shopAssetsService,
        ShopUrlService $shopUrlsService,
        EventsService $eventsService,
        UserService $userService
    ) {
        $this->experienceService = $experienceService;
        $this->endpointService = $endpointService;
        $this->shopService = $shopService;
        $this->shopApiTokenService = $shopApiTokenService;
        $this->shopAssetsService = $shopAssetsService;
        $this->shopUrlsService = $shopUrlsService;
        $this->eventsService = $eventsService;
        $this->userService = $userService;

        $this->log = config('LOG', '');
        $this->flags = config('FLAGS', '');
    }

    protected function getShopData(string $methodName = null)
    {
        if (empty($methodName)) {
            return $this->shop->getShop();
        } else {
            try {
                return $this->shop->$methodName();
            } catch (Exception $e) {
                return $this->shop;
            }
        }
    }

    protected function hasInFlags(string $flag): bool
    {
        return Str::contains($this->flags, $flag);
    }

    protected function handleException(
        GuzzleException|AbstractCheckoutException|Exception $error,
        ?FrontendShop $shop,
        int $statusCode = 500,
        array $context = [],
    ): void {
        Logging::exception(sprintf('%s: %s', $error::class, $error->getMessage()), $context);

        if (!empty($shop) && $this->hasInFlags(Constants::FLAG_LOADTIME)) {
            $this->insertErrorEventInList(shop: $shop, error: $error, publicOrderID: $publicOrderID ?? '');
        }
        $message = App::environment(Constants::APP_ENV_LOCAL) ? $error->getMessage() : '';
        abort($statusCode, $message);
    }

    protected function insertEventInList(FrontendShop $shop, string $eventName, array $context, string $publicOrderID, Carbon|null $dateTime = null): void
    {
        $this->eventsList[] = $this->eventsService->createEvent(
            shopID: $shop->getID(),
            eventName: $eventName,
            dateTime: $dateTime ?? Carbon::now(),
            context: $context,
            publicOrderID: $publicOrderID
        );
    }

    protected function insertErrorEventInList(FrontendShop $shop, Exception $error, string $publicOrderID, bool $isInitFunction = true): void
    {
        $this->eventsList[] = $this->eventsService->createEvent(
            shopID: $shop->getID(),
            eventName: $isInitFunction ? Events::CONTROLLER_INIT_ORDER_ERROR : Events::CONTROLLER_RESUME_ORDER_ERROR,
            dateTime: Carbon::now(),
            context: ['error_type' => $error::class, 'error_message' => $error->getMessage()],
            publicOrderID: $publicOrderID
        );
        $this->eventsService->registerEventsList($this->eventsList);
    }
}
