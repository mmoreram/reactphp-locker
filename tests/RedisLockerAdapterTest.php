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
        return RedisAdapter::createByCredentials($loop, new Factory($loop), 'localhost');
    }
}