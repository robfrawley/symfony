<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Tests\Simple;

use Symfony\Component\Cache\Simple\FilesystemCache;

/**
 * @group time-sensitive
 */
class FilesystemCacheTest extends CacheTestCase
{
    public function createSimpleCache($defaultLifetime = 0)
    {
        return new FilesystemCache('', $defaultLifetime);
    }

    public function testPrune()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $cache = $this->createSimpleCache();

        $isHit = function ($name) use ($cache) {
            return $cache->has($name);
        };

        $doSet = function ($name, $value, \DateInterval $expiresAfter = null) use ($cache) {
            $cache->set($name, $value, $expiresAfter);
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
