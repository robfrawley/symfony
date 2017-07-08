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

use Symfony\Component\Cache\Simple\PhpFilesCache;

/**
 * @group time-sensitive
 */
class PhpFilesCacheTest extends CacheTestCase
{
    protected $skippedTests = array(
        'testDefaultLifeTime' => 'PhpFilesCache does not allow configuring a default lifetime.',
    );

    public function createSimpleCache()
    {
        if (!PhpFilesCache::isSupported()) {
            $this->markTestSkipped('OPcache extension is not enabled.');
        }

        return new PhpFilesCache('sf-cache');
    }

    public function testPrune()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $cache = $this->createSimpleCache();

        $isHit = function ($name) use ($cache) {
            $getFileMethod = (new \ReflectionObject($cache))->getMethod('getFile');
            $getFileMethod->setAccessible(true);

            return $cache->has($name) && file_exists($getFileMethod->invoke($cache, $name));
        };

        $doSet = function ($name, $value, \DateInterval $expiresAfter = null) use ($cache) {
            $cache->set($name, $value, $expiresAfter);
        };

        $doSet('foo', 'foo-val');
        $doSet('bar', 'bar-val', new \DateInterval('PT20S'));
        $doSet('baz', 'baz-val', new \DateInterval('PT40S'));
        $doSet('qux', 'qux-val', new \DateInterval('PT80S'));

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
    }
}
