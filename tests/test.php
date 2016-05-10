e<?php

use LightQueueClient\Queue;
use LightQueueClient\Message;

include __DIR__ . '/../vendor/autoload.php';

$client = new Queue([
    //队列名称
    'name' => 'jyz_dev_defer_log',

    //适配器配置
    'adapter' => [
      //适配器类名
      'class' => LightQueueClient\Adapter\Redis::class,

      //default
      //'host' => '127.0.0.1',

      //default
      'port' => 7210,

      //default
      //'auth' => null,
    ],
]);


$msg = new Message();
$msg->did = 3;
$msg->e = 0x0001;

$s_msg = Queue::messageSerialize($msg);
var_dump($s_msg, Queue::messageUnserialize($s_msg));

var_dump($msg->toJson(), Message::class);
