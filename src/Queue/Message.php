<?php namespace Epic\Queue;

use \React\Stomp\Protocol\Frame;
use \React\Stomp\Client;
use \React\Stomp\AckResolver;

class Message
{

    protected $frame;
    protected $client;
    protected $ackResolver;

    public function __construct(Frame $frame, Client $client = null)
    {
        $this->frame = $frame;
        $this->client = $client;
    }

    public function reply($text, $headers = [])
    {
        if ($pipe = $this->getHeader('replyTo')) {
            $this->client->send($pipe, $text, $headers);
        }
    }

    public function setAckResolver(AckResolver $ackResolver)
    {
        $this->ackResolver = $ackResolver;
    }

    public function ack(array $headers = [])
    {
        if (!($this->ackResolver instanceof AckResolver)) {
            throw new \RuntimeException('You cannot use acknowledgement without ack subscription');
        }
        $this->ackResolver->ack($headers);
    }

    public function nack(array $headers = [])
    {
        if (!($this->ackResolver instanceof AckResolver)) {
            throw new \RuntimeException('You cannot use acknowledgement without ack subscription');
        }
        $this->ackResolver->nack($headers);
    }

    public function getBody()
    {
        return $this->frame->body;
    }

    public function getCommand()
    {
        return $this->frame->command;
    }

    public function getHeader($name)
    {
        return $this->frame->getHeader($name);
    }

    public function getHeaders()
    {
        return $this->frame->headers;
    }
}