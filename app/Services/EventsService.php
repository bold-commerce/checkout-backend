<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;

class EventsService
{
    public function createEvent(int $shopID, string $eventName, Carbon|string $dateTime = null, $context = '', $publicOrderID = '', $saveImmediatly = false): Event
    {
        $event = new Event();
        $event->createEventFromData($shopID, $eventName, $dateTime ?? Carbon::now(), $context, $publicOrderID);

        if ($saveImmediatly) {
            $event->save();
        }

        return $event;
    }

//    public function saveEvent(Event $event, $inDB = true, $inDataWarehouse = false): void
//    {
//        if ($inDB) {
//            $event->save();
//        }
//
//        if ($inDataWarehouse) {
//            // TODO: implement DATA WAREHOUSE event API when available
//        }
//    }

    public function eventNameExists(string $eventName): bool
    {
        return defined('\App\Constants\Events::'.$eventName);
    }

    public function registerEventsList(array $eventsList): int
    {
        $eventsSaved = 0;

        foreach ($eventsList as $event) {
            if ($event->save()) {
                ++$eventsSaved;
            }
        }

        return $eventsSaved;
    }
}
