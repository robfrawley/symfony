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
    private $cachePruneCommandId;

    /**
     * @var string
     */
    private $cachePoolTag;

    /**
     * @param string $cachePruneCommandId
     * @param string $cachePoolTag
     */
    public function __construct($cachePruneCommandId = 'cache.command.pool_pruner', $cachePoolTag = 'cache.pool')
    {
        $this->cachePruneCommandId = $cachePruneCommandId;
        $this->cachePoolTag = $cachePoolTag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition($this->cachePruneCommandId)) {
            $container->getDefinition($this->cachePruneCommandId)->replaceArgument(0, $this->getCachePoolArgument($container));
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return IteratorArgument
     */
    private function getCachePoolArgument(ContainerBuilder $container)
    {
        $services = $this->getPruneableCachePoolServiceIds($container);

        return new IteratorArgument(array_combine($services, array_map(function ($id) {
            return new Reference($id);
        }, $services)));
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function getPruneableCachePoolServiceIds(ContainerBuilder $container)
    {
        return array_filter($this->getNonAbstractCachePoolServiceIds($container), function ($id) use ($container) {
            return $this->getServiceReflection($container, $id)->implementsInterface(PruneableInterface::class);
        });
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function getNonAbstractCachePoolServiceIds(ContainerBuilder $container)
    {
        return array_filter(array_keys($container->findTaggedServiceIds($this->cachePoolTag)), function ($id) use ($container) {
            return false === $container->getDefinition($id)->isAbstract();
        });
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $id
     *
     * @return \ReflectionClass
     */
    private function getServiceReflection(ContainerBuilder $container, $id)
    {
        $class = $this->getServiceClassName($container, $id);

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
    private function getServiceClassName(ContainerBuilder $container, $id)
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
