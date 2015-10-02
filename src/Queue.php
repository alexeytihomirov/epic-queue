<?php namespace Epic;

use \React\Stomp\Client;
use \React\Stomp\Factory;
use \React\EventLoop\LoopInterface;
use \Epic\Queue\Message;

class Queue
{
    const ACK_MODE_CLIENT = 'client';
    const ACK_MODE_CLIENT_INDIVIDUAL = 'client-individual';

    /**
     * @var Client $client
     */
    protected $client;

    /**
     * @var LoopInterface
     */
    protected $loop;

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

            $subscribeAction = function ($frame, $ackResolver = null) use ($subscription) {
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

    public function subscribeAck($pipe, $action, $ackMode = self::ACK_MODE_CLIENT)
    {
        $this->subscriptions[] = ['pipe' => $pipe, 'action' => $action, 'ack' => true, 'ack_mode' => $ackMode];
        return $this;
    }

    public function subscribeQueue($pipe, $action) {
        $this->subscribe('/queue/' . $pipe, $action);
        return $this;
    }

    public function subscribeQueueAck($pipe, $action, $ackMode = self::ACK_MODE_CLIENT) {
        $this->subscribeAck('/queue/' . $pipe, $action, $ackMode);
        return $this;
    }

    public function subscribeTopic($pipe, $action) {
        $this->subscribe('/topic/' . $pipe, $action);
        return $this;
    }

    public function subscribeTopicAck($pipe, $action, $ackMode = self::ACK_MODE_CLIENT) {
        $this->subscribeAck('/topic/' . $pipe, $action, $ackMode);
        return $this;
    }

    public function send($pipe, $message, array $headers = [])
    {
        if($message instanceof Message) {
            $body = $message->getBody();
        }else{
            $body = $message;
        }

        $this->getClient()->send($pipe, $body, $headers);
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
        return (new static(static::$factory->createClient($params)))->setLoop($loop);
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * @param LoopInterface $loop
     * @return Queue
     */
    public function setLoop($loop)
    {
        $this->loop = $loop;
        return $this;
    }


}