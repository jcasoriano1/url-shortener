<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ShortUrlNotFound extends Exception
{
    public function __construct(
        $message = 'URL not found',
        $code = 404,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
