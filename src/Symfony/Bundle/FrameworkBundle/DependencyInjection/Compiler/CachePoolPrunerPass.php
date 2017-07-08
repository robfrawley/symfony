<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler;

use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class CachePoolPrunerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $cacheCommandServiceId;

    /**
     * @var string
     */
    private $cachePoolTag;

    /**
     * @param string $cacheCommandServiceId
     * @param string $cachePoolTag
     */
    public function __construct($cacheCommandServiceId = 'cache.command.pool_pruner', $cachePoolTag = 'cache.pool')
    {
        $this->cacheCommandServiceId = $cacheCommandServiceId;
        $this->cachePoolTag = $cachePoolTag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition($this->cacheCommandServiceId)) {
            $container->getDefinition($this->cacheCommandServiceId)->replaceArgument(0, $this->getCachePoolIteratorArgument($container));
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return IteratorArgument
     */
    private function getCachePoolIteratorArgument(ContainerBuilder $container)
    {
        $services = $this->getCachePoolServiceIds($container);

        return new IteratorArgument(array_combine($services, array_map(function ($id) {
            return new Reference($id);
        }, $services)));
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function getCachePoolServiceIds(ContainerBuilder $container)
    {
        $services = array();

        foreach ($container->findTaggedServiceIds($this->cachePoolTag) as $id => $tags) {
            if ($container->getDefinition($id)->isAbstract()) {
                continue;
            }

            if (!$this->resolveServiceReflectionClass($container, $id)->implementsInterface(PruneableInterface::class)) {
                continue;
            }

            $services[] = $id;
        }

        return $services;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $id
     *
     * @return \ReflectionClass
     */
    private function resolveServiceReflectionClass(ContainerBuilder $container, $id)
    {
        $class = $this->resolveServiceClassName($container, $id);

        if (null === $reflection = $container->getReflectionClass($class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $class, $id));
        }

        return $reflection;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $id
     *
     * @return string
     */
    private function resolveServiceClassName(ContainerBuilder $container, $id)
    {
        $definition = $container->getDefinition($id);
        $class = $definition->getClass();

        while (!$class && $definition instanceof ChildDefinition) {
            $definition = $container->findDefinition($definition->getParent());
            $class = $definition->getClass();
        }

        return $container->getParameterBag()->resolveValue($class);
    }
}
