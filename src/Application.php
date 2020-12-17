<?php
declare(strict_types=1);

namespace faulty;

use Slim\App;

class Application extends App
{
    public function __construct()
    {
        $container = (new Dependency())->buildApplicationContainer();
        parent::__construct($container);
    }
}