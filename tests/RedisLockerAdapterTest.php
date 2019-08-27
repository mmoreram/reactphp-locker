<?php

namespace React\Locker\Tests;

use Clue\React\Redis\Factory;
use React\EventLoop\LoopInterface;
use React\Locker\LockerAdapter;
use React\Locker\RedisAdapter;

/**
 * Class RedisLockerAdapterTest
 */
class RedisLockerAdapterTest extends LockerAdapterTest
{
    /**
     * Get adapter
     *
     * @param LoopInterface|null $loop
     *
     * @return LockerAdapter
     */
    public function getAdapter(LoopInterface $loop = null) : LockerAdapter
    {
        $factory = new Factory($loop);
        $redis = $factory->createLazyClient('localhost');
        $redis2 = $factory->createLazyClient('localhost');

        return new RedisAdapter($loop, $redis, $redis2);
    }
}