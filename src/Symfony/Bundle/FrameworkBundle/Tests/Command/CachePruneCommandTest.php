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
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\HttpKernel\KernelInterface;

class CachePruneCommandTest extends TestCase
{
    public function testCommandWithNoArgument()
    {
        $tester = $this->getCommandTester($this->getKernel(), $this->getRewindableGenerator());
        $tester->execute(array());
    }

    public function testCommandWithArgument()
    {
        $tester = $this->getCommandTester($this->getKernel(), $this->getRewindableGenerator());
        $tester->execute(array('pools' => array('my_pool')));
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\InvalidArgumentException
     * @expectedExceptionMessage The "my_invalid_pool" pool does not exist or is not pruneable.
     */
    public function testCommandThrowsWithInvalidArgument1()
    {
        $tester = $this->getCommandTester($this->getKernel(), $this->getRewindableGenerator(false));
        $tester->execute(array('pools' => array('my_invalid_pool')));
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\InvalidArgumentException
     * @expectedExceptionMessage The "my_invalid_pool" pool does not exist or is not pruneable.
     */
    public function testCommandThrowsWithInvalidArgument2()
    {
        $tester = $this->getCommandTester($this->getKernel(), $this->getEmptyRewindableGenerator());
        $tester->execute(array('pools' => array('my_invalid_pool')));
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionMessage No pruneable cache pools found.
     */
    public function testCommandThrowsWithNoPools()
    {
        $tester = $this->getCommandTester($this->getKernel(), $this->getEmptyRewindableGenerator());
        $tester->execute(array());
    }

    /**
     * @return RewindableGenerator
     */
    private function getRewindableGenerator($expectsPrune = true)
    {
        return new RewindableGenerator(function () use ($expectsPrune) {
            yield 'my_pool' => $this->getPruneable($expectsPrune);
        }, 1);
    }

    /**
     * @return RewindableGenerator
     */
    private function getEmptyRewindableGenerator()
    {
        return new RewindableGenerator(function () {
            return new \ArrayIterator(array());
        }, 1);
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
     * @param KernelInterface     $kernel
     * @param RewindableGenerator $generator
     *
     * @return CommandTester
     */
    private function getCommandTester(KernelInterface $kernel, RewindableGenerator $generator)
    {
        $application = new Application($kernel);
        $application->add(new CachePoolPruneCommand($generator));

        return new CommandTester($application->find('cache:pool:prune'));
    }
}
