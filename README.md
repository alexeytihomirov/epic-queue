# epic-queue
Queues over reactphp/stomp

```php
<?php
require "vendor/autoload.php";

use \Epic\Queue;

$loop = \React\EventLoop\Factory::create();


$queue = Queue::create($loop, ['vhost' => '/', 'login' => 'guest', 'passcode' => 'guest'])
    ->subscribe("/queue/test", function($message, $queue){
        var_dump($message);
    })
    ->subscribeAck("/queue/test", function(\Epic\Queue\Message $message, $queue){
            echo $message->getCommand();
            echo $message->getBody();
            echo $message->getHeader('destination');
            $message->ack();
    });


$loop->run();
```