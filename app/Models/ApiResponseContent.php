<?php

declare(strict_types=1);

namespace App\Models;

use App\Constants\Fields;
use App\Constants\Paths;
use App\Libraries\CheckoutCollection;
use Illuminate\Support\Collection;

class ApiResponseContent
{
    protected CheckoutCollection $apiResponseContent;

    public function __construct()
    {
        $this->apiResponseContent = new CheckoutCollection();
    }

    public function setApiResponseContent(array $apiResponseContent)
    {
        $this->apiResponseContent = new CheckoutCollection($apiResponseContent);
    }

    public function getApiResponseContent(): Collection
    {
        return $this->apiResponseContent;
    }

    public function getContent(): array|Collection
    {
        return $this->apiResponseContent->get(Paths::CONTENT_PATH);
    }

    public function getData(): array|Collection
    {
        return $this->apiResponseContent->getRecursive(Paths::DATA_PATH);
    }

    public function getPublicOrderID(): string
    {
        $publicOrderID = $this->apiResponseContent->getRecursive(Paths::PUBLIC_ORDER_ID_PATH);

        return $publicOrderID ?? '';
    }

    public function setApplicationState($appState = []): void
    {
        $contentArray = $this->apiResponseContent->toArray();
        $contentArray[Fields::CONTENT_IN_RESPONSE][Fields::DATA_IN_RESPONSE][Fields::APPLICATION_STATE_IN_RESPONSE] = $appState;
        $this->setApiResponseContent($contentArray);
    }

    public function getApplicationState(): Collection
    {
        $appState = $this->apiResponseContent->getRecursive(Paths::APP_STATE_PATH);

        return $appState ?? collect([]);
    }

    public function getInitialData(): Collection
    {
        $initialData = $this->apiResponseContent->getRecursive(Paths::INITIAL_DATA_PATH);

        return $initialData ?? collect([]);
    }

    public function getJwtToken(): string
    {
        $jwtToken = $this->apiResponseContent->getRecursive(Paths::JWT_TOKEN_PATH);

        return $jwtToken ?? '';
    }

    public function getFieldFromApplicationState(string $fieldName): array|string|int|bool|null
    {
        $appState = collect($this->getApplicationState());

        return $appState->get($fieldName);
    }

    public function cleanPiiFromResponse(): void
    {
        $appState = $this->getApplicationState()->toArray();
        unset($appState[Fields::ADDRESSES_IN_RESPONSE][Fields::SHIPPING_IN_RESPONSE]);
        unset($appState[Fields::ADDRESSES_IN_RESPONSE][Fields::BILLING_IN_RESPONSE]);
        unset($appState[Fields::CUSTOMER_IN_RESPONSE]);
        $this->setApplicationState($appState);
    }

    public function cleanJwtFromResponse(): void
    {
        $contentArray = $this->apiResponseContent->toArray();
        $contentArray[Fields::CONTENT_IN_RESPONSE][Fields::DATA_IN_RESPONSE][Fields::JWT_TOKEN_IN_RESPONSE] = 'invalid';
        $this->setApiResponseContent($contentArray);
    }

    public function isOrderProcessed(): bool
    {
        return $this->getFieldFromApplicationState(Fields::IS_PROCESSED_IN_RESPONSE) === true;
    }
}
