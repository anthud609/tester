<?php
namespace App;

use DI\Container;
use DI\ContainerBuilder as PhpDiBuilder;

class ContainerBuilder
{
    /**
     * @param  array  $config
     * @return Container
     */
    public function build(array $config): Container
    {
        $builder = new PhpDiBuilder();

        // 1) add PHP-DI definitions (you can split into multiple files)
        $builder->addDefinitions(__DIR__ . '/../config/di.php');

        // 2) enable autowiring & annotations if you like
        $builder->useAutowiring(true);

        // 3) pass your config array into the container
        $builder->addDefinitions([
            'settings' => $config,
        ]);

        return $builder->build();
    }
}
