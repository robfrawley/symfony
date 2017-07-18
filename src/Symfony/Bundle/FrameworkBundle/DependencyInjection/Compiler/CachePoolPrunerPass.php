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
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class CachePoolPrunerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $poolTag;

    /**
     * @var string
     */
    private $commandServiceId;

    /**
     * @var string
     */
    private $locatorServiceId;

    /**
     * @param string $poolTag
     * @param string $commandServiceId
     * @param string $locatorServiceId
     */
    public function __construct($poolTag = 'cache.pool', $commandServiceId = 'cache.command.pool_prune', $locatorServiceId = 'cache.command.pool_prune_locator')
    {
        $this->poolTag = $poolTag;
        $this->commandServiceId = $commandServiceId;
        $this->locatorServiceId = $locatorServiceId;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition($this->locatorServiceId) && $container->hasDefinition($this->commandServiceId)) {
            $services = $this->getCachePoolServiceIds($container);

            $container->getDefinition($this->locatorServiceId)->replaceArgument(0, array_combine($services, $services));
            $container->getDefinition($this->commandServiceId)->replaceArgument(1, $services);
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function getCachePoolServiceIds(ContainerBuilder $container)
    {
        $services = array();

        foreach ($container->findTaggedServiceIds($this->poolTag) as $id => $tags) {
            if ($container->getDefinition($id)->isAbstract()) {
                continue;
            }

            if (!$this->getServiceReflection($container, $id)->implementsInterface(PruneableInterface::class)) {
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
