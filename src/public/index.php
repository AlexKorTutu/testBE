<?php
declare(strict_types=1);

use faulty\Application;
use faulty\controllers\StartRun;
use faulty\controllers\GetRun;

require '../../vendor/autoload.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

try {
    $app = new Application();

    $app->get('/runs/{id}', [GetRun::class, 'getRun']);

    $app->post('/runs/', [StartRun::class, 'startRun']);

    $app->run();

} catch (\Throwable $e) {
    echo $e->getMessage();
}