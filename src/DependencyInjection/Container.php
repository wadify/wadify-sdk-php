<?php

namespace Wadify\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
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
     * @param string $id
     * @param array  $addToArgument
     *
     * @return mixed
     */
    public static function get($id, array $addToArgument = [])
    {
        if (self::$container === null) {
            self::load();
        }

        if (!empty($addToArgument)) {
            $definition = self::$container->getDefinition($id);
            foreach ($addToArgument as $key => $value) {
                $arguments = $definition->getArguments();
                foreach ($value as $newKey => $newValue) {
                    $arguments[$key][$newKey] = $newValue;
                }
                $definition->setArguments($arguments);
            }
        }

        if (false === self::$container->isFrozen()) {
            self::$container->compile();
        }

        return self::$container->get($id);
    }

    /**
     * @param string $id
     * @param mixed  $service
     */
    public static function set($id, $service)
    {
        if (self::$container === null || self::$container->isFrozen()) {
            self::load();
        }

        self::$container->set($id, $service);
        $definition = new Definition(get_class($service));
        self::$container->setDefinition($id, $definition);
    }
}