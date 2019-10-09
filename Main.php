<?php

namespace App\WebSockets;

use App\User;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Main implements MessageComponentInterface
{

    protected $clients;
    protected $users;

    public function __construct()
    {
        $this->clients = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients[$conn->resourceId] = $conn;

        $this->emit($conn, [
            'event' => 'connection',
            'status' => 'connected'
        ]);


        echo "{$conn->resourceId} connected\n";
    }

    public function onMessage(ConnectionInterface $sender, $data)
    {
        $data = json_decode($data);

        $event = $data->event;

        if ($event === 'login') {

            $user = User::where('api_token', $data->token)->first();

            if ($user) $this->users[$sender->resourceId] = $user;

            $this->emit(
                $sender,
                ['event' => 'login', 'user' => $user]
            );

            //
        }

        dump($data);

        //
    }

    public function onClose(ConnectionInterface $conn)
    {
        unset($this->clients[$conn->resourceId]);
        unset($this->users[$conn->resourceId]);

        echo "{$conn->resourceId}  disconnected\n";

        //
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();

        //
    }

    protected function emit_all($data, $encodeJson = true)
    {
        if ($encodeJson) $data = json_encode($data);

        foreach ($this->clients as $client) $client->send($data);

        //
    }

    protected function emit_except($data, array $exceptions, $encodeJson = true)
    {

        if ($encodeJson) $data = json_encode($data);

        foreach ($this->clients as $client) {

            $check = !in_array($client->resourceId, $exceptions);

            if ($check) $client->send($data);

            //
        }

        //
    }

    protected function emit($to, $data, $encodeJson = true)
    {
        if ($encodeJson) $data = json_encode($data);

        $to->send($data);

        //
    }
}
