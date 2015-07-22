<?php namespace Epic;

use \React\Stomp\Client;
use \React\Stomp\Factory;
use \React\EventLoop\LoopInterface;
use \Epic\Queue\Message;

class Queue
{
    /**
     * @var Client $client
     */
    protected $client;
    protected $subscriptions = [];
    public static $factory;

    public function __construct(Client $client)
    {
        $this->client = $client;

        $self = $this;
        $client->connect()->then(function () use ($self) {
            $self->applySubscriptions();
        });
    }

    protected function applySubscriptions()
    {


        foreach ($this->subscriptions as $subscription) {

            $subscribeAction = function ($frame, $ackResolver) use ($subscription) {
                $message = new Message($frame, $this->client);
                if ($ackResolver) {
                    $message->setAckResolver($ackResolver);
                }
                $subscription['action']($message, $this);
            };

            if ($subscription['ack']) {
                $this->client->subscribeWithAck($subscription['pipe'], $subscription['ack_mode'], $subscribeAction);
            } else {
                $this->client->subscribe($subscription['pipe'], $subscribeAction);
            }
        }
    }

    public function subscribe($pipe, $action)
    {
        $this->subscriptions[] = ['pipe' => $pipe, 'action' => $action, 'ack' => false];
        return $this;
    }

    public function subscribeAck($pipe, $action, $ackMode = 'client')
    {
        $this->subscriptions[] = ['pipe' => $pipe, 'action' => $action, 'ack' => true, 'ack_mode' => $ackMode];
        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public static function create(LoopInterface $loop, $params)
    {
        if (null === static::$factory OR !(static::$factory instanceof Factory)) {
            static::$factory = new Factory($loop);
        }
        return new static(static::$factory->createClient($params));
    }
}