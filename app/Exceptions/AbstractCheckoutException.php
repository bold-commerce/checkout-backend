<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Facades\Logging;
use Exception;

abstract class AbstractCheckoutException extends Exception
{
    protected string $responseError;

    /**
     * CheckoutExperienceException constructor.
     */
    public function __construct(string $message = '', int $responseCode = 0, Exception $previous = null, string $responseErrors = '')
    {
        $this->responseError = $responseErrors;
        parent::__construct($message.'-'.$responseCode.'-'.$this->getResponseError(), 0, $previous);

        Logging::exception($message, ['responseError' => $responseErrors]);
    }

    public function getResponseError(): string
    {
        return $this->responseError ?? '';
    }
}
