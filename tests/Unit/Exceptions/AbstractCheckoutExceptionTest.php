<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\ShopInstanceException;
use Tests\TestCase;

class AbstractCheckoutExceptionTest extends TestCase
{
    public function testGetResponseError() {
        $errorMessage = 'some error message';
        $errorResponse = 'some API response error';
        $error = new ShopInstanceException($errorMessage, 500, null, $errorResponse);
        $this->assertEquals($errorResponse, $error->getResponseError());
    }
}
