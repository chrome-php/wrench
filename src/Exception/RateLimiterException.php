<?php

namespace Wrench\Exception;

use Wrench\Exception\Exception as WrenchException;
use Wrench\Protocol\Protocol;

class RateLimiterException extends WrenchException
{
    /**
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct($message = null, $code = null, $previous = null)
    {
        if (null == $code) {
            $code = Protocol::CLOSE_GOING_AWAY;
        }
        parent::__construct($message, $code, $previous);
    }
}
