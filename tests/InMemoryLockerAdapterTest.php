<?php

namespace React\Locker\Tests;

use React\EventLoop\LoopInterface;
use React\Locker\InMemoryAdapter;
use React\Locker\LockerAdapter;

/**
 * Class InMemoryLockerAdapterTest
 */
class InMemoryLockerAdapterTest extends LockerAdapterTest
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
        return new InMemoryAdapter($loop);
    }
}