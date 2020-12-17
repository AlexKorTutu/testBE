<?php
declare(strict_types=1);
require_once '../../vendor/autoload.php';

use faulty\Dependency;
use PhpAmqpLib\Message\AMQPMessage;

$container = (new Dependency())->buildWorkerContainer();


/** @var PDO $pdo */
$pdo = $container->get('bdConnection');
$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$sql = file_get_contents(__DIR__ . '/initdb.sql');
try{
$pdo->exec($sql);
} catch(PDOException $e) {
    echo $e->getMessage();
}