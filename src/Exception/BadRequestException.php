<?php

namespace Wrench\Exception;

use Wrench\Protocol\Protocol;

class BadRequestException extends HandshakeException
{
    /**
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct($message = null, $code = null, $previous = null)
    {
        if (null == $code) {
            $code = Protocol::HTTP_BAD_REQUEST;
        }
        parent::__construct($message, $code, $previous);
    }
}
