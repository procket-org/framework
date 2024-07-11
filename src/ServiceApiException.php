<?php

namespace Pocket\Framework;

use Exception;
use Throwable;

/**
 * Service Api exception class
 *
 * If this exception is thrown, the error message will be displayed on the front end.
 */
class ServiceApiException extends Exception
{
    /**
     * Constructor
     *
     * @param string $errorMsg Error message
     * @param int $httpStatus HTTP status code, default is 500
     * @param Throwable|null $previous Previous exception object
     */
    public function __construct(string $errorMsg = "", int $httpStatus = 500, ?Throwable $previous = null)
    {
        parent::__construct($errorMsg, $httpStatus, $previous);
    }
}