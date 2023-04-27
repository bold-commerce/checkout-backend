<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ShopNotFoundException;
use App\Services\EventsService;
use App\Services\ShopService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class EventsController extends Controller
{
    protected ShopService $shopService;
    protected EventsService $eventsService;

    public function __construct(ShopService $shopService, EventsService $eventsService)
    {
        $this->shopService = $shopService;
        $this->eventsService = $eventsService;
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $result = [
                'saved' => 0,
                'in_error' => 0,
            ];
            $events = $request->post('events') ?? [];
            if (empty($events)) {
                return response()->json([
                    'message' => 'No events found',
                ], 204);
            }

            $shop = $this->shopService->getShopByDomain($request->header('X-Bold-Shop-Domain'));

            foreach ($events as $event) {
                if ($this->eventsService->eventNameExists($event['event_name'] ?? '')) {
                    $dateTime = $event['event_date_time'] ?? Carbon::now();
                    $eventModel = $this->eventsService->createEvent(
                        shopID: $shop->getID(),
                        eventName: $event['event_name'],
                        dateTime: $dateTime,
                        context: $event['context'] ?? '',
                        publicOrderID: $event['public_order_id'] ?? '',
                        saveImmediatly: true
                    );
                    if (!empty($eventModel->getID())) {
                        ++$result['saved'];
                    } else {
                        ++$result['in_error'];
                    }
                } else {
                    ++$result['in_error'];
                }
            }
        } catch (ShopNotFoundException|Exception $e) {
            Log::info($e::class, ['message' => $e->getMessage()]);
        } finally {
            return response()->json([
                'message' => 'Event(s) saved',
                'results' => $result,
            ], 201);
        }
    }
}
