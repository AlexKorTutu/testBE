<?php
declare(strict_types=1);
require_once '../../vendor/autoload.php';

use faulty\Dependency;
use PhpAmqpLib\Message\AMQPMessage;

$container = (new Dependency())->buildWorkerContainer();

$connection = $container->get('amqpConnection');
$channel = $connection->channel();
$channel->exchange_declare(Dependency::EXCHANGE, 'fanout', false, false, false);
list($queue_name, ,) = $channel->queue_declare("");
$channel->queue_bind($queue_name, 'runs');

$callback = function ($msg) {
    $run = json_decode($msg->body, true);
    var_dump($run);
    $requester = new \faulty\workers\Requester($run['run_id'], time() + $run['seconds']);
    echo 'Начали: ' . $run['run_id'] . ' на ' . $run['seconds'] . ' секунд' . PHP_EOL;
    $requester->spamRequestsInLoop();
    echo 'Готово ' . $run['run_id'] . PHP_EOL;
};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}