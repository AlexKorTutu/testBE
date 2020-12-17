<?php
declare(strict_types=1);

use faulty\controllers\GetRun;
use faulty\controllers\StartRun;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Container\ContainerInterface;

$definitions = [
    'amqpConnection' => function (ContainerInterface $container) {
        $connection = AMQPStreamConnection::create_connection([
            //TODO: унести в .env
            ['host' => 'localhost', 'port' => 5672, 'user' => 'guest', 'password' => 'guest'],
        ]);
        return $connection;
    },

    'bdConnection' => function (ContainerInterface $container) {
        //TODO: унести в .env
        $pdo = new \PDO('mysql:dbname=runs;host=127.0.0.1', 'root', '1234');
        return $pdo;
    },

    StartRun::class => function ($c) {
        $qConnection = $c->get("amqpConnection");
        $bdConnection = $c->get("bdConnection");
        return new StartRun($qConnection, $bdConnection);
    },

    GetRun::class => function ($c) {
        $bdConnection = $c->get("bdConnection");
        return new GetRun($bdConnection);
    },
];

return $definitions;