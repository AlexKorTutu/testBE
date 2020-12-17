<?php
namespace faulty\workers;

use faulty\Dependency;

class Requester
{
    const HASH_LENGTH = 7;
    const CHARACTERS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private $bdConnection;
    private $finishby;
    private $runId;
    private $hash;

    public function __construct(int $runId, int $finishby)
    {
        $container = (new Dependency())->buildWorkerContainer();
        $this->bdConnection = $container->get('bdConnection');;
        $this->finishby = $finishby;
        $this->runId = $runId;
        $this->hash = $this->generateRandomString();
        $this->registerRun();
    }

    public function spamRequestsInLoop(): void
    {
        $timeIsOut = time() >= $this->finishby;
        $sum = 0;
        $successful = 0;

        while (!$timeIsOut) {
            $result = $this->makeRequestAndReturnValue();

            $timeIsOut = time() >= $this->finishby;
            //пишем результат, только если запрос закончился вовремя
            if (!is_null($result) && !$timeIsOut) {
                $sum += $result;
                $successful++;
                $this->updateRun($sum, $successful);
                //запрос к базе тоже может занять время
                $timeIsOut = time() >= $this->finishby;
            }
        }
        $this->updateRun($sum, $successful, true);
    }

    private function updateRun(int $sum, int $successful, $finalize = false)
    {
        $status = $finalize ? 'FINISHED' : 'IN_PROGRESS';
        $request = 'UPDATE result SET val = ?, successful = ?, status = ? WHERE run_id = ? AND hash = ?';

        $statement = $this->bdConnection->prepare($request);
        $statement->execute([$sum, $successful, $status, $this->runId, $this->hash]);
    }

    private function registerRun (): void
    {
        $sql = 'INSERT INTO result (run_id, hash)
        VALUES (:run_id, :hash)';
        $stmt = $this->bdConnection->prepare($sql);
        $stmt->execute([
            'run_id' => $this->runId,
            'hash' => $this->hash
        ]);
    }

    private function mockResponse(): string
    {
        sleep(rand(0, 5));

        $responses = [
            '{ "value": ' . rand(0, 1000) .  ' }',
            '{ "error": "Internal server error" }',
            '{ "error": "Timed out" }',
            '{ "error": "Too many concurrent requests" }',
        ];

        $variant = rand(0,6);
        //чуть поднимем шансы на корректный ответ
        $variant = $variant > 3 ? 0 : $variant;

        return $responses[$variant];
    }

    private function makeRequestAndReturnValue(): ?int
    {
        $response = json_decode($this->mockResponse(), true);
        if(array_key_exists('error', $response)) {
            return null;
        }
        return $response['value'];
    }

    private function generateRandomString(): string
    {
        $string = '';
        for ($i = 0; $i < self::HASH_LENGTH; $i++) {
            $string .= self::CHARACTERS[random_int(0, strlen(self::CHARACTERS) - 1)];
        }
        return $string;
    }


}