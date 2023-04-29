<?php

namespace App\WebSocket\Handlers;

use Ratchet\WebSocket\MessageComponentInterface;

class MyWebSocketHandler implements MessageComponentInterface
{
    public function onOpen(ConnectionInterface $connection)
    {
        // Handle WebSocket connection opening
    }

    public function onClose(ConnectionInterface $connection)
    {
        // Handle WebSocket connection closing
    }

    public function onError(ConnectionInterface $connection, \Exception $e)
    {
        // Handle WebSocket error
    }

    public function onMessage(ConnectionInterface $connection, $message)
    {
        // Handle WebSocket message received
    }
}
