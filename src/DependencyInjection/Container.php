<?php

namespace Wadify\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class Container
{
    /**
     * @var ContainerBuilder
     */
    private static $container = null;

    /**
     * @var YamlFileLoader
     */
    private static $loader;

    private static function load()
    {
        self::$container = new ContainerBuilder();
        self::$loader = new YamlFileLoader(self::$container, new FileLocator(__DIR__));
        self::$loader->load(__DIR__.'/../Resources/services.yml');
    }

    /**
     * @param string $serviceName
     *
     * @return mixed
     */
    public static function get($serviceName)
    {
        if (self::$container === null) {
            self::load();
        }
        self::$container->compile();

        return self::$container->get($serviceName);
    }

    /**
     * @param string $id
     * @param mixed  $service
     */
    public static function set($id, $service)
    {
        self::load();
        self::$container->set($id, $service);
    }
}
