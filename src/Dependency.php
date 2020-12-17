<?php
declare(strict_types=1);

namespace faulty;

use DI\Container;
use DI\ContainerBuilder;

final class Dependency
{
    public const RUNS_TABLE = 'runs';
    public const RESULTS_TABLE = 'result';
    public const EXCHANGE = 'runs';

    private $builder;

    public function __construct()
    {
        $this->builder = new ContainerBuilder();
        $this->builder->addDefinitions(__DIR__ . '/di.php');
    }

    /**
     * Build and return a container.
     *
     * @return Container
     */
    public function buildApplicationContainer()
    {
        $this->builder->addDefinitions(__DIR__ . '/slim-config.php');

        return $this->builder->build();
    }

    /**
     * Build and return a container.
     *
     * @return Container
     */
    public function buildWorkerContainer()
    {
        return $this->builder->build();
    }
}