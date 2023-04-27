<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Exception;

class Event extends AbstractExperienceModel
{
    protected $table = 'events';
    protected $fillable = [
        'shop_id',
        'event_date_time',
        'event_name',
        'context',
        'public_order_id',
    ];

    protected Carbon $dateTime;
    protected int $precision = 6;
    protected static string $format = 'Y-m-d H:i:s.u';

    public static function getFormat(): string
    {
        return self::$format;
    }

    public function getID(): ?int
    {
        return $this->id;
    }

    public function getShopID(): int
    {
        return $this->shop_id;
    }

    public function getEventDateTime(): Carbon
    {
        return new Carbon($this->event_date_time);
    }

    public function setEventName(string $eventName): void
    {
        $this->event_name = $eventName;
    }

    public function getEventName(): string
    {
        return $this->event_name;
    }

    public function getContext($asJSON = true): string|array
    {
        return $asJSON ? $this->context : json_decode($this->context, true);
    }

    public function setContext(array $context): void
    {
        $this->context = json_encode($context);
    }

    public function getPublicOrderID(): string
    {
        return $this->public_order_id;
    }

    public function setPublicOrderID(string $publicOrderID): void
    {
        $this->public_order_id = $publicOrderID;
    }

    public function setEventDateTime(Carbon|string $dateTime): void
    {
        if (is_string($dateTime)) {
            try {
                $this->dateTime = Carbon::createFromFormat(self::$format, $dateTime);
            } catch (Exception $e) {
                $this->dateTime = new Carbon();
            }
        } else {
            $this->dateTime = $dateTime;
        }
        $this->event_date_time = $this->dateTime->format(self::$format);
    }

    public function createEventFromData(int $shopID, string $eventName, Carbon|string $dateTime, array|string|null $context, string $publicOrderID): Event
    {
        $this->shop_id = $shopID;
        $this->event_name = $eventName;
        $this->public_order_id = $publicOrderID;
        $this->setEventDateTime($dateTime);
        if (is_string($context)) {
            $this->context = $context;
        } elseif (is_array($context)) {
            $this->context = json_encode($context);
        } else {
            $this->context = '';
        }

        return $this;
    }
}
