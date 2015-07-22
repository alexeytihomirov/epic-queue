<?php namespace Epic\Queue;

use \React\Stomp\Protocol\Frame;
use \React\Stomp\Client;

class Message {

    protected $frame;
    protected $client;

    public function __construct(Frame $frame, Client $client = null)
    {
        $this->frame = $frame;
        $this->client = $client;
    }

    public function reply($pipe, $text, $headers = [])
    {
        $this->client->send($pipe, $text, $headers);
    }
}