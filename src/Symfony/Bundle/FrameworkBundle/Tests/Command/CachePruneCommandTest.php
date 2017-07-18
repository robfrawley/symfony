<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Command\CachePoolPruneCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\KernelInterface;

class CachePruneCommandTest extends TestCase
{
    public function testCommandWithNoArgument()
    {
        $tester = $this->getCommandTester($this->getKernel(), $this->getDefaultServiceLocator(), $this->getDefaultPoolIds());
        $tester->execute(array());
    }

    public function testCommandWithOneArgument()
    {
        $tester = $this->getCommandTester($this->getKernel(), $this->getDefaultServiceLocator(), $this->getDefaultPoolIds());
        $tester->execute(array('pools' => array('foo_pool')));
    }

    public function testCommandWithTwoArguments()
    {
        $tester = $this->getCommandTester($this->getKernel(), $this->getDefaultServiceLocator(), $this->getDefaultPoolIds());
        $tester->execute(array('pools' => array('foo_pool', 'bar_pool')));
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\InvalidArgumentException
     * @expectedExceptionMessage The "my_invalid_pool" pool does not exist or is not pruneable.
     */
    public function testCommandThrowsWithInvalidArgument1()
    {
        $tester = $this->getCommandTester($this->getKernel(), $this->getDefaultServiceLocator(false), $this->getDefaultPoolIds());
        $tester->execute(array('pools' => array('my_invalid_pool')));
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\InvalidArgumentException
     * @expectedExceptionMessage The "my_invalid_pool" pool does not exist or is not pruneable.
     */
    public function testCommandThrowsWithInvalidArgument2()
    {
        $tester = $this->getCommandTester($this->getKernel(), $this->getEmptyServiceLocator(), array());
        $tester->execute(array('pools' => array('my_invalid_pool')));
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionMessage No pruneable cache pools found.
     */
    public function testCommandThrowsWithNoPools()
    {
        $tester = $this->getCommandTester($this->getKernel(), $this->getEmptyServiceLocator(), array());
        $tester->execute(array());
    }

    /**
     * @param bool $expectsPrune
     *
     * @return ServiceLocator
     */
    private function getDefaultServiceLocator($expectsPrune = true)
    {
        $poolIds = $this->getDefaultPoolIds();
        $locator = new ServiceLocator(array_combine($poolIds, array_map(function () use ($expectsPrune) {
            return function () use ($expectsPrune) {
                return $this->getPruneable($expectsPrune);
            };
        }, $poolIds)));

        return $locator;
    }

    /**
     * @return string[]
     */
    private function getDefaultPoolIds()
    {
        return array(
            'foo_pool',
            'bar_pool',
        );
    }

    /**
     * @return ServiceLocator
     */
    private function getEmptyServiceLocator()
    {
        return new ServiceLocator(array());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|KernelInterface
     */
    private function getKernel()
    {
        $container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->getMock();

        $kernel = $this
            ->getMockBuilder(KernelInterface::class)
            ->getMock();

        $kernel
            ->expects($this->any())
            ->method('getContainer')
            ->willReturn($container);

        $kernel
            ->expects($this->once())
            ->method('getBundles')
            ->willReturn(array());

        return $kernel;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PruneableInterface
     */
    private function getPruneable($expectsPrune = true)
    {
        $pruneable = $this
            ->getMockBuilder(PruneableInterface::class)
            ->getMock();

        if (true === $expectsPrune) {
            $pruneable
                ->expects($this->atLeastOnce())
                ->method('prune');
        }

        return $pruneable;
    }

    /**
     * @param KernelInterface $kernel
     * @param ServiceLocator  $locator
     * @param array           $poolIds
     *
     * @return CommandTester
     */
    private function getCommandTester(KernelInterface $kernel, ServiceLocator $locator, array $poolIds)
    {
        $application = new Application($kernel);
        $application->add(new CachePoolPruneCommand($locator, $poolIds));

        return new CommandTester($application->find('cache:pool:prune'));
    }
}
