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

use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

/**
 * @group time-sensitive
 */
class PhpFilesAdapterTest extends AdapterTestCase
{
    protected $skippedTests = array(
        'testDefaultLifeTime' => 'PhpFilesAdapter does not allow configuring a default lifetime.',
    );

    public function createCachePool()
    {
        if (!PhpFilesAdapter::isSupported()) {
            $this->markTestSkipped('OPcache extension is not enabled.');
        }

        return new PhpFilesAdapter('sf-cache');
    }

    public static function tearDownAfterClass()
    {
        FilesystemAdapterTest::rmdir(sys_get_temp_dir().'/symfony-cache');
    }

    public function testPrune()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $cache = $this->createCachePool();

        $isHit = function ($name) use ($cache) {
            $getFileMethod = (new \ReflectionObject($cache))->getMethod('getFile');
            $getFileMethod->setAccessible(true);

            return $cache->getItem($name)->isHit() && file_exists($getFileMethod->invoke($cache, $name));
        };

        $doSet = function ($name, $value, \DateInterval $expiresAfter = null) use ($cache) {
            $item = $cache->getItem($name);
            $item->set($value);

            if ($expiresAfter) {
                $item->expiresAfter($expiresAfter);
            }

            $cache->save($item);
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
