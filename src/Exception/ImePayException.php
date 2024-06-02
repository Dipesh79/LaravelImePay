<?php

namespace Dipesh79\LaravelImePay\Exception;

class ImePayException extends \Exception
{
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
