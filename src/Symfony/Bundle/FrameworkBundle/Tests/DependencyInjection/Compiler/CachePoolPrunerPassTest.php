<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\CachePoolPrunerPass;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CachePoolPrunerPassTest extends TestCase
{
    public function testCompilerPassReplacesCommandArgument()
    {
        $container = new ContainerBuilder();
        $container->register('cache.command.pool_prune')->addArgument(array())->addArgument(array());
        $container->register('cache.command.pool_prune_locator')->addArgument(array());
        $container->register('pool.foo', FilesystemAdapter::class)->addTag('cache.pool');
        $container->register('pool.bar', PhpFilesAdapter::class)->addTag('cache.pool');

        $pass = new CachePoolPrunerPass();
        $pass->process($container);

        $expected = array(
            'pool.foo',
            'pool.bar',
        );

        $locator = $container->getDefinition('cache.command.pool_prune_locator');
        $command = $container->getDefinition('cache.command.pool_prune');

        $this->assertEquals($expected, $command->getArgument(1));
        $this->assertEquals(array_combine($expected, $expected), $locator->getArgument(0));
    }

    public function testCompilerPassReplacesCommandArgumentWithDecorated()
    {
        $container = new ContainerBuilder();
        $container->register('cache.command.pool_prune')->addArgument(array())->addArgument(array());
        $container->register('cache.command.pool_prune_locator')->addArgument(array());
        $container->register('pool.foo', FilesystemAdapter::class);
        $container->setDefinition('pool.bar', new ChildDefinition('pool.foo'))->addTag('cache.pool');

        $pass = new CachePoolPrunerPass();
        $pass->process($container);

        $expected = array(
            'pool.bar',
        );

        $locator = $container->getDefinition('cache.command.pool_prune_locator');
        $command = $container->getDefinition('cache.command.pool_prune');

        $this->assertEquals($expected, $command->getArgument(1));
        $this->assertEquals(array_combine($expected, $expected), $locator->getArgument(0));
    }

    public function testCompilePassIsIgnoredIfCommandDoesNotExist()
    {
        $container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(array('hasDefinition', 'getDefinition', 'findTaggedServiceIds'))
            ->getMock();

        $container
            ->expects($this->atLeastOnce())
            ->method('hasDefinition')
            ->with('cache.command.pool_prune_locator')
            ->will($this->returnValue(false));

        $container
            ->expects($this->never())
            ->method('getDefinition');

        $container
            ->expects($this->never())
            ->method('findTaggedServiceIds');

        $pass = new CachePoolPrunerPass();
        $pass->process($container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage Class "Symfony\Bundle\FrameworkBundle\Tests\DependencyInjection\Compiler\NotFound" used for service "pool.not-found" cannot be found.
     */
    public function testCompilerPassThrowsOnInvalidDefinitionClass()
    {
        $container = new ContainerBuilder();
        $container->register('cache.command.pool_prune')->addArgument(array())->addArgument(array());
        $container->register('cache.command.pool_prune_locator')->addArgument(array());
        $container->register('pool.not-found', NotFound::class)->addTag('cache.pool');

        $pass = new CachePoolPrunerPass();
        $pass->process($container);
    }
}
