<?php namespace Epic;

use \React\Stomp;
use \Epic\Queue\Message;

class Queue
{
    /**
     * @var Stomp\Client $client
     */
    protected $client;
    protected $subscriptions = [];
    public static $factory;

    public function __construct(Stomp\Client $client)
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
            $this->client->subscribe($subscription['pipe'], function ($frame) use ($subscription) {
                $message = new Message($frame, $this->client);
                $subscription['action']($message, $this);
            });
        }
    }

    public function subscribe($pipe, $action)
    {
        $this->subscriptions[] = ['pipe' => $pipe, 'action' => $action];
        return $this;
    }

    public function subscribeQueue($destination, $action)
    {
        $this->subscribe('/queue/' . $destination, $action);
        return $this;
    }

    public function subscribeTopic($destination, $action)
    {
        $this->subscribe('/topic/' . $destination, $action);
        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public static function create(\React\EventLoop\LoopInterface $loop, $params)
    {
        if (null === static::$factory OR !(static::$factory instanceof Stomp\Factory)) {
            static::$factory = new Stomp\Factory($loop);
        }
        return new static(static::$factory->createClient($params));
    }
}