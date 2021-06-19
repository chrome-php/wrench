<?php

namespace Wrench\Socket;

class ServerClientSocket extends Socket
{
    /**
     * Constructor
     * A server client socket is accepted from a listening socket, so there's
     * no need to call ->connect() or whatnot.
     *
     * @param resource $accepted_socket
     * @param array    $options
     */
    public function __construct($accepted_socket, array $options = [])
    {
        parent::__construct($options);

        $this->socket = $accepted_socket;
        $this->connected = (bool) $accepted_socket;
    }
}
