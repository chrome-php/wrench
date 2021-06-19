<?php

namespace Wrench\Exception;

use Wrench\Protocol\Protocol;

/**
 * Invalid origin exception.
 */
class InvalidOriginException extends HandshakeException
{
    /**
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct($message = null, $code = null, $previous = null)
    {
        if (null == $code) {
            $code = Protocol::HTTP_FORBIDDEN;
        }
        parent::__construct($message, $code, $previous);
    }
}
