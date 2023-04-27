<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\API;
use App\Constants\Constants;
use App\Constants\Events;
use App\Constants\Paths;
use App\Constants\SupportedPlatforms;
use App\Exceptions\ApiCallExceptions\InitializeOrderApiCallException;
use App\Exceptions\ApiCallExceptions\InitializeShopifyOrderFromAdminApiCallException;
use App\Exceptions\ApiCallExceptions\ResumeOrderApiCallException;
use App\Facades\Session as CheckoutSession;
use App\Models\ApiResponseContent;
use App\Models\FrontendShop;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExperienceController extends AbstractController
{
    protected ApiResponseContent $apiResponseContent;

    public function init(Request $request): View
    {
        try {
            $initStart = Carbon::now();
            $this->shop = $this->shopService->getInstance();
            $this->apiResponseContent = new ApiResponseContent();

            $params = $request->all();
            $publicOrderID = $params['public_order_id'] ?? '';
            $cartID = $params['cart_id'] ?? '';
            $isAdminOrder = $params['checkout_from_admin'] ?? false;
            $variants = $params['variants'] ?? '';
            $customerID = $params['customer_id'] ?? '';
            $checkoutLoadTime = (int) ($params['checkout_local_time'] ?? 0);
            $returnUrl = $params['return_url'] ?? '';

            CheckoutSession::put('_cart_id', $cartID);
            if (!empty($returnUrl)) {
                CheckoutSession::put('return_url', $returnUrl);
            }

            $initApiCall = Carbon::now();
            if (!empty($publicOrderID)) {
                $this->apiResponseContent->setApiResponseContent($this->endpointService->resumeOrder($publicOrderID));
            } elseif ($isAdminOrder && $this->getShopData('getPlatformType') === SupportedPlatforms::SHOPIFY_PLATFORM_TYPE) {
                $resumeUrl = $this->experienceService->getResumableOrderUrl($this->getShopData());
                $response = $this->endpointService->initializeShopifyAdminOrder($variants, $resumeUrl);
                $this->apiResponseContent->setApiResponseContent($response);
            } else {
                $initOrderParams = $this->experienceService->getInitializationOrderData($params);
                $response = $this->endpointService->initializeOrder($initOrderParams);
                $this->apiResponseContent->setApiResponseContent($response);
                $publicOrderID = $this->apiResponseContent->getPublicOrderID();
            }
            $responseApiCall = Carbon::now();

            CheckoutSession::put('_public_order_id', $publicOrderID);

            if (!empty($customerID)) {
                $newApplicationState = $this->userService->addAuthenticatedUser($customerID, $this->apiResponseContent->getApiResponseContent()->toArray());
                $this->apiResponseContent->setApplicationState($newApplicationState);
            }
            $this->shop->setReturnToCheckoutAfterLogin($this->experienceService->getReturnToCheckoutUrl($publicOrderID, $cartID));
            $viewParameters = $this->getViewParameters($params);

            $this->handleEvent($this->shop, $initStart, $initApiCall, $responseApiCall, $checkoutLoadTime, $publicOrderID, true, ['cart_id' => $cartID]);

            return view('experience/init', $viewParameters);
        } catch (GuzzleException|ResumeOrderApiCallException|InitializeShopifyOrderFromAdminApiCallException|InitializeOrderApiCallException $e) {
            $this->handleException(
                error: $e,
                shop: $shop ?? null,
                statusCode: $e->getCode() ?? Response::HTTP_INTERNAL_SERVER_ERROR,
                context: ['public_order_id' => $publicOrderID ?? '']
            );
        } catch (Exception $e) {
            $this->handleException(
                error: $e,
                shop: $shop ?? null,
                statusCode: $e->getCode() ?? Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function resume(Request $request, string $platformType, string $shopDomain, string $requestPage = ''): View|RedirectResponse
    {
        try {
            $this->shop = $this->shopService->getInstance();
            $this->apiResponseContent = new ApiResponseContent();

            if (!$this->experienceService->isCheckoutExperiencePage($requestPage)) {
                throw new NotFoundHttpException(code: Response::HTTP_NOT_FOUND);
            }

            $resumeStart = Carbon::now();
            $params = $request->all();
            $isResumePath = $requestPage === Paths::RESUME_PATH;
            $publicOrderID = '';
            $cartID = $params['cart_id'] ?? '';
            $isPublicOrderIdFromSession = false;

            if ($isResumePath) {
                $publicOrderID = $params['public_order_id'] ?? '';
            }
            if (empty($publicOrderID)) {
                $publicOrderID = CheckoutSession::get('_public_order_id') ?? '';
                $isPublicOrderIdFromSession = true;
            }

            if (empty($publicOrderID)) {
                CheckoutSession::flush();
                abort(Response::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                $resumeApiCall = Carbon::now();
                $response = $this->endpointService->resumeOrder($publicOrderID);
                $this->apiResponseContent->setApiResponseContent($response);
                $responseApiCall = Carbon::now();
                $isOrderProcessed = $this->apiResponseContent->isOrderProcessed();
                $shouldClearOrder = $this->experienceService->shouldClearOrder($publicOrderID, $params);
                if (!$isPublicOrderIdFromSession && $shouldClearOrder) {
                    $this->experienceService->cleanOrder($publicOrderID, $response);
                    $this->apiResponseContent->cleanPiiFromResponse();
                    CheckoutSession::put('_public_order_id', $publicOrderID);
                }
                if ($isPublicOrderIdFromSession && $isOrderProcessed) {
                    $this->apiResponseContent->cleanJwtFromResponse();
                    CheckoutSession::flush();
                }
            }

            $this->shop->setReturnToCheckoutAfterLogin($this->experienceService->getReturnToCheckoutUrl($cartID, $publicOrderID));
            $viewParameters = $this->getViewParameters($params);

            $this->handleEvent($this->shop, $resumeStart, $resumeApiCall, $responseApiCall, 0, $publicOrderID, false, ['cart_id' => $cartID]);

            return view('experience/init', $viewParameters);
        } catch (GuzzleException $e) {
            $this->handleException(
                error: $e,
                shop: $this->shop ?? null,
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                context: ['public_order_id' => $publicOrderID ?? ''],
            );
        } catch (Exception $e) {
            $this->handleException(
                error: $e,
                shop: $this->shop ?? null,
                statusCode: $e->getCode() > 0 ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function getReturnToCartUrl(): string {
        if($this->shop->getShop()->getPlatformType() === SupportedPlatforms::BOLD_PLATFORM_TYPE){
            return $this->shop->getUrls()->getBackToStoreUrl();
        } else {
            return $this->shop->getUrls()->getBackToCartUrl();
        }
    }

    private function getViewParameters(array $params): array
    {
        return [
            'shop' => $this->shop->getShop(),
            'shopAssets' => $this->shop->getAssets(),
            'shopUrls' => [
                'shopUrls' => $this->shop->getUrls(),
                'returnToCheckoutUrl' => $this->shop->getReturnToCheckoutAfterLogin(),
                'returnToCart' =>  $this->getReturnToCartUrl()
            ],
            'flags' => $this->experienceService->getFlags(),
            'cartID' => $params['cart_id'] ?? '',
            'publicOrderID' => $this->apiResponseContent->getPublicOrderID(),
            'initResponse' => $this->apiResponseContent->getContent(),
            'keys' => [
                'bugsnagApiKey' => config('services.bold_checkout.bugsnag_api_key', ''),
            ],
            'indicators' => [
                'environment' => [
                    'type' => config('services.bold_checkout.api_environment'),
                    'path' => config('services.bold_checkout.api_path'),
                    'url' => config('services.bold_checkout.api_url'),
                ],
                'enableConsole' => false,
                'loadTimes' => [],
            ],
            'stylesheet' => $this->shop->getStylesheetUrl(),
        ];
    }

    private function handleEvent(
        FrontendShop $shop,
        Carbon $functionStart,
        Carbon $apiCallStart,
        Carbon $apiCallResponded,
        int $checkoutLoadTime,
        string $publicOrderID,
        bool $isInit,
        array $context = []
    ): void {
        if ($this->hasInFlags(Constants::FLAG_LOADTIME)) {
            if ($isInit && !empty($checkoutLoadTime)) {
                $this->insertEventInList(
                    shop: $shop,
                    eventName: Events::CLICK_CHECKOUT_BUTTON,
                    context: $context,
                    publicOrderID: $publicOrderID,
                    dateTime: Carbon::createFromTimestampMs($checkoutLoadTime)
                );
            }
            $this->insertEventInList(
                shop: $shop,
                eventName: $isInit ? Events::CONTROLLER_INIT_ORDER_INITIALIZE : Events::CONTROLLER_RESUME_ORDER_INITIALIZE,
                context: $context,
                publicOrderID: $publicOrderID,
                dateTime: $functionStart
            );
            $this->insertEventInList(
                shop: $shop,
                eventName: $isInit ? Events::CONTROLLER_INIT_ORDER_INIT_ENDPOINT_CALLED : Events::CONTROLLER_RESUME_ORDER_RESUME_ENDPOINT_CALLED,
                context: $context,
                publicOrderID: $publicOrderID,
                dateTime: $apiCallStart
            );
            $this->insertEventInList(
                shop: $shop,
                eventName: $isInit ? Events::CONTROLLER_INIT_ORDER_INIT_ENDPOINT_RESPONDED : Events::CONTROLLER_RESUME_ORDER_RESUME_ENDPOINT_RESPONDED,
                context: $context,
                publicOrderID: $publicOrderID,
                dateTime: $apiCallResponded
            );
            $this->insertEventInList(
                shop: $shop,
                eventName: $isInit ? Events::CONTROLLER_INIT_ORDER_FINALIZE : Events::CONTROLLER_RESUME_ORDER_FINALIZE,
                context: $context,
                publicOrderID: $publicOrderID
            );
            $this->eventsService->registerEventsList($this->eventsList);
        }
    }
}
