<?php
declare(strict_types=1);

namespace faulty\controllers;

use faulty\Dependency;
use PDO;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class GetRun
{
    public function __construct($bdConnection) {
        $this->bdConnection = $bdConnection;
    }

    public function getRun(Request $request, Response $response, $id)
    {
        $id = (int) $id;
        $jsonResponse = $response->withHeader('Content-Type', 'application/json;charset=utf-8');

        if (!$this->isRunExists($id)) {
            $jsonResponse->withStatus(404);
            $body = 'No such run registered';
        } else {
            $jsonResponse->withStatus(200);
            $body = $this->getRunData($id);
        }

        $jsonResponse->getBody()->write(json_encode($body));

        return $jsonResponse;
    }

    private function isRunExists($runId): bool
    {
        $request = 'SELECT * FROM ' . Dependency::RUNS_TABLE
            . ' WHERE id = ?';

        $statement = $this->bdConnection->prepare($request);
        $statement->execute([$runId]);
        $queryResult = $statement->fetchAll(PDO::FETCH_ASSOC);

        return !empty($queryResult);
    }

    private function getRunData($run_id): array
    {
        $request = 'SELECT * FROM ' . Dependency::RESULTS_TABLE
            . ' WHERE run_id = ?';

        $statement = $this->bdConnection->prepare($request);
        $statement->execute([$run_id]);
        $queryResult = $statement->fetchAll(PDO::FETCH_ASSOC);

        $status = 'FINISHED';
        $responses = 0;
        $sum = 0;

        if (empty($queryResult))
        {
            $status = 'QUEUED';
        } else {
            foreach ($queryResult as $line) {
                $responses += $line['successful'];
                $sum += $line['val'];
                if ($line['status'] != 'FINISHED') {
                    $status = "IN_PROGRESS";
                }
            }
        }

        return [
            "status" => $status,
            "successful_responses_count" => $responses,
            "sum" => $sum,
        ];
    }
}