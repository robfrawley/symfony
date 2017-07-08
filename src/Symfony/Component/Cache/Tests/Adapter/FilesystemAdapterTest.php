<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Tests\Adapter;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * @group time-sensitive
 */
class FilesystemAdapterTest extends AdapterTestCase
{
    public function createCachePool($defaultLifetime = 0)
    {
        return new FilesystemAdapter('', $defaultLifetime);
    }

    public static function tearDownAfterClass()
    {
        self::rmdir(sys_get_temp_dir().'/symfony-cache');
    }

    public static function rmdir($dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        if (!$dir || 0 !== strpos(dirname($dir), sys_get_temp_dir())) {
            throw new \Exception(__METHOD__."() operates only on subdirs of system's temp dir");
        }
        $children = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($children as $child) {
            if ($child->isDir()) {
                rmdir($child);
            } else {
                unlink($child);
            }
        }
        rmdir($dir);
    }

    public function testPrune()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $cache = $this->createCachePool();

        $isHit = function ($name) use ($cache) {
            return $cache->getItem($name)->isHit();
        };

        $doSet = function ($name, $value, \DateInterval $expiresAfter = null) use ($cache) {
            $item = $cache->getItem($name);
            $item->set($value);

            if ($expiresAfter) {
                $item->expiresAfter($expiresAfter);
            }

            $cache->save($item);
        };

        $setUp = function () use ($cache, $doSet) {
            $doSet('foo', 'foo-val');
            $doSet('bar', 'bar-val', new \DateInterval('PT20S'));
            $doSet('baz', 'baz-val', new \DateInterval('PT40S'));
            $doSet('qux', 'qux-val', new \DateInterval('PT80S'));
        };

        $setUp();

        $cache->prune();
        $this->assertTrue($isHit('foo'));
        $this->assertTrue($isHit('bar'));
        $this->assertTrue($isHit('baz'));
        $this->assertTrue($isHit('qux'));

        sleep(30);
        $cache->prune();
        $this->assertTrue($isHit('foo'));
        $this->assertFalse($isHit('bar'));
        $this->assertTrue($isHit('baz'));
        $this->assertTrue($isHit('qux'));

        sleep(30);
        $cache->prune();
        $this->assertTrue($isHit('foo'));
        $this->assertFalse($isHit('baz'));
        $this->assertTrue($isHit('qux'));

        sleep(30);
        $cache->prune();
        $this->assertTrue($isHit('foo'));
        $this->assertFalse($isHit('qux'));

        $setUp();

        $cache->prune(new \DateInterval('PT30S'));
        $this->assertTrue($isHit('foo'));
        $this->assertFalse($isHit('bar'));
        $this->assertTrue($isHit('baz'));
        $this->assertTrue($isHit('qux'));

        $cache->prune(new \DateInterval('PT60S'));
        $this->assertTrue($isHit('foo'));
        $this->assertFalse($isHit('baz'));
        $this->assertTrue($isHit('qux'));

        $cache->prune(new \DateInterval('PT90S'));
        $this->assertTrue($isHit('foo'));
        $this->assertFalse($isHit('qux'));
    }
}
