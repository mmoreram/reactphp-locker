<?php

namespace React\Locker\Tests;

use Clue\React\Buzz\Browser;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Locker\Locker;
use React\Locker\LockerAdapter;
use React\Locker\LockerFactory;
use Clue\React\Block;
use React\Locker\TimeoutException;

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
     * @group exec
     */
    public function testSimple()
    {
        $loop = new StreamSelectLoop();
        $client = new Browser($loop);
        $adapter = $this->getAdapter($loop);
        $finished = 0;
        $current = 0;
        $promises = [];
        $numberOfConcurrency = 3;

        for ($i=0; $i<$numberOfConcurrency; $i++) {
            $locker = LockerFactory::create($adapter, 'res1');
            $promises[] = $locker
                ->enqueue()
                ->then(function(Locker $locker) use (&$finished, &$current, $loop, $client) {

                    $this->assertEquals($current, 0);
                    $current++;
                    $finished++;

                    return $client
                        ->get('http://google.es')
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
        $this->assertEquals($finished, $numberOfConcurrency);
    }

    /**
     *
     */
    public function testTimeout()
    {
        $loop = new StreamSelectLoop();
        $adapter = $this->getAdapter($loop);

        $locker = LockerFactory::create($adapter, 'res1');
        $finished = false;
        $failed = false;

        $promise1 = $locker
            ->enqueue(1)
            ->then(function(Locker $locker) use ($loop) {

                Block\sleep(2, $loop);
                return $locker;
            })
            ->then(function(Locker $locker) {
                return $locker->release();
            });


        $promise2 = $locker
            ->enqueue(1)
            ->then(function(Locker $locker) use (&$finished) {

                $finished = true;
            }, function(TimeoutException $exception) use (&$failed) {
                $failed = true;
            });

        Block\awaitAll([$promise1, $promise2], $loop);
        $this->assertFalse($finished);
        $this->assertTrue($failed);
    }
}