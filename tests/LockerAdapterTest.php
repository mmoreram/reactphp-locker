<?php

namespace React\Locker\Tests;

use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Locker\Locker;
use React\Locker\LockerAdapter;
use React\Locker\LockerFactory;
use Clue\React\Block;
use React\Locker\TimeoutException;
use Mmoreram\React;

/**
 * Class LockerAdapterTest
 */
abstract class LockerAdapterTest extends TestCase
{
    /**
     * Get adapter
     *
     * @param LoopInterface|null $loop
     *
     * @return LockerAdapter
     */
    public abstract function getAdapter(LoopInterface $loop = null) : LockerAdapter;

    /**
     * Simple test
     *
     * @group simple
     */
    public function testSimple()
    {
        $this->executeSimple(1);
    }

    /**
     * Concurrent test
     *
     * @group concurrent
     */
    public function testConcurrent()
    {
        $this->executeSimple(2);
        $this->executeSimple(3);
        $this->executeSimple(10);
    }

    /**
     * Execute simple test
     *
     * @param int $concurrency
     */
    public function executeSimple(int $concurrency)
    {
        $loop = new StreamSelectLoop();
        $adapter = $this->getAdapter($loop);
        $finished = 0;
        $current = 0;
        $promises = [];

        for ($i=0; $i<$concurrency; $i++) {
            $locker = LockerFactory::create($adapter, 'res1');
            $promises[] = $locker
                ->enqueue()
                ->then(function(Locker $locker) use (&$finished, &$current, $loop, $i) {
                    $this->assertEquals($current, 0);
                    $current++;
                    $finished++;

                    return React\usleep(10000, $loop)
                        ->then(function() use (&$current, $locker) {
                            $this->assertEquals($current, 1);
                            $current--;

                            return $locker;
                        });
                })
                ->then(function(Locker $locker) {
                    return $locker->release();
                });
        }

        Block\awaitAll($promises, $loop);
        $this->assertEquals($current, 0);
        $this->assertEquals($finished, $concurrency);
    }

    /**
     * Test timeout
     *
     * @group timeout
     */
    public function testTimeout()
    {
        $loop = new StreamSelectLoop();
        $adapter = $this->getAdapter($loop);

        $locker = LockerFactory::create($adapter, 'res1');
        $finished = false;
        $failed = false;

        $promise1 = $locker
            ->enqueue(0)
            ->then(function(Locker $locker) use ($loop) {
                return React\sleep(3, $loop)
                    ->then(function() use ($locker) {
                        return $locker;
                    });
            })
            ->then(function(Locker $locker) {
                return $locker->release();
            });


        $promise2 = React\sleep(1, $loop)
            ->then(function() use ($locker, &$finished, &$failed) {
                $locker
                    ->enqueue(1000)
                    ->then(function(Locker $locker) use (&$finished) {

                        $finished = true;
                    }, function(TimeoutException $exception) use (&$failed) {

                        $failed = true;
                    });
            });

        Block\awaitAll([$promise1, $promise2], $loop);
        $this->assertFalse($finished);
        $this->assertTrue($failed);
    }

    /**
     * @group multi
     */
    public function testMultipleLockers()
    {
        $loop = new StreamSelectLoop();
        $adapter = $this->getAdapter($loop);

        $locker1 = LockerFactory::create($adapter, 'res1');
        $locker2 = LockerFactory::create($adapter, 'res2');
        $orderFinished = [];

        $promise1 = $locker1
            ->enqueue()
            ->then(function(Locker $locker) use ($loop) {

                return React\usleep(30000, $loop)
                    ->then(function() use ($locker) {
                        return $locker;
                    });
            })
            ->then(function(Locker $locker1) use (&$orderFinished) {
                $orderFinished[] = 1;

                return $locker1->release();
            });


        $promise2 = $locker2
            ->enqueue()
            ->then(function(Locker $locker) use ($loop) {

                return React\usleep(10000, $loop)
                    ->then(function() use ($locker) {
                        return $locker;
                    });
            })
            ->then(function(Locker $locker2) use (&$orderFinished) {
                $orderFinished[] = 2;

                return $locker2->release();
            });

        Block\awaitAll([$promise1, $promise2], $loop);
        $this->assertEquals([2, 1], $orderFinished);
    }
}