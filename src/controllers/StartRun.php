<?php
declare(strict_types=1);

namespace faulty\controllers;

use Exception;
use PhpAmqpLib\Message\AMQPMessage;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class StartRun
{
    private $qConnection;
    private $bdConnection;

    public function __construct($qConnection, $bdConnection) {
        $this->qConnection = $qConnection;
        $this->bdConnection = $bdConnection;
    }

    public function startRun(Request $request, Response $response)
    {
        try {
            $data = $request->getParsedBody();
            $seconds = (int)filter_var($data['seconds'], FILTER_SANITIZE_STRING);

            $id = $this->registerRunAndGetId($seconds );

            $this->addToQueue($id, $seconds);

            $jsonResponse = $response->withHeader('Content-Type', 'application/json;charset=utf-8');
            $jsonResponse->getBody()->write(json_encode(["id" => $id]));

            return $jsonResponse;

        } catch (Exception $e) {
            $jsonResponse = $response->withStatus('400');
            $jsonResponse->withHeader('X-Status-Reason', $e->getMessage());
            return $jsonResponse;
        }
    }

    private function addToQueue($id, $seconds): void
    {
        $arr = [
            'run_id' => $id,
            'seconds' => $seconds,
        ];
        $message = json_encode($arr);

        $channel = $this->qConnection->channel();
        $channel->exchange_declare('runs', 'fanout', false, false, false);

        $msg = new AMQPMessage($message, ['delivery_mode' => 2]);
        $channel->basic_publish($msg, 'runs');

        $channel->close();
        $this->qConnection->close();
    }

    private function registerRunAndGetId($seconds): int
    {
        $sql = 'INSERT INTO runs (seconds)
        VALUES (:seconds)';
        $stmt = $this->bdConnection->prepare($sql);
        $stmt->execute([
            'seconds' => $seconds,
        ]);

        return (int)$this->bdConnection->lastInsertId();
    }
}