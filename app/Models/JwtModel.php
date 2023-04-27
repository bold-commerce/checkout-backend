<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;

class JwtModel
{
    protected int $expiryTimestamp;
    protected ?int $issuedAtTimestamp;
    protected ?int $notBeforeTimestamp;
    protected string $authType;
    protected array $payload;

    public function __construct(string $authType, array $payload, ?int $expiryTimestamp = null, ?int $notBeforeTimestamp = null, ?int $issuedAtTimestamp = null)
    {
        $this->authType = $authType;
        $this->payload = $payload;
        $this->expiryTimestamp = $expiryTimestamp ?? Carbon::now()->addMinutes(15)->getTimestamp();
        $this->notBeforeTimestamp = $notBeforeTimestamp;
        $this->issuedAtTimestamp = $issuedAtTimestamp;
    }

    public function getAuthType(): string
    {
        return $this->authType;
    }

    public function toArray(): array
    {
        return [
            'auth_type' => $this->authType,
            'payload' => $this->payload,
            'exp' => $this->expiryTimestamp,
            'nbf' => $this->notBeforeTimestamp,
            'iat' => $this->issuedAtTimestamp,
        ];
    }
}
