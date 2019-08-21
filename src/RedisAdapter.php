<?php
/**
 * File header placeholder
 */

namespace React\Locker;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class RedisAdapter
 */
class RedisAdapter implements LockerAdapter
{
    /**
     * Loop
     *
     * @var LoopInterface
     */
    private $loop;

    /**
     * Client
     *
     * @var Client
     */
    private $client;

    /**
     * Client2
     *
     * @var Client
     */
    private $client2;

    /**
     * InMemoryAdapter constructor.
     *
     * @param LoopInterface $loop
     * @param Client $client
     * @param Client $client2
     */
    public function __construct(
        LoopInterface $loop,
        Client $client,
        Client $client2
    )
    {
        $this->loop = $loop;
        $this->client = $client;
        $this->client2 = $client2;
    }

    /**
     * Create by credentials
     *
     * @param LoopInterface $loop
     * @param Factory $factory
     * @param string $target
     *
     * @return RedisAdapter
     */
    public static function createByCredentials(
        LoopInterface $loop,
        Factory $factory,
        string $target
    ) : RedisAdapter
    {
        return new self(
            $loop,
            $factory->createLazyClient($target),
            $factory->createLazyClient($target)
        );
    }

    /**
     * Enqueue
     *
     * @param Locker $locker
     * @param string $resourceID
     * @param int $timeout
     *
     * @return PromiseInterface
     */
    public function enqueue(
        Locker $locker,
        string $resourceID,
        int $timeout
    ) : PromiseInterface
    {
        return $this
            ->client2
            ->eval('
                local resource = ARGV[1];
                local locker = ARGV[2];
                local hash = resource .. ".hash";
                local channel = resource .. ".channel";
                local len = redis.call("hlen", hash);
                redis.call("hset", hash, locker, 1);
                local enqueued = 0;
                if (len == 0) then
                    enqueued = redis.call("lpush", channel, "hola");
                end
                return enqueued;
            ', 2, 'resource', 'locker', $resourceID, $locker->ID())
            ->then(function($enqueued) use ($resourceID, $timeout) {

                return $this
                    ->client
                    ->blpop($resourceID . '.channel', (int) $timeout)
                    ->then(function($value) {
                        if (is_null($value)) {
                            throw new TimeoutException;
                        }
                    });
            });
    }

    /**
     * Release
     *
     * @param Locker $locker
     * @param string $resourceID
     *
     * @return PromiseInterface
     */
    public function release(
        Locker $locker,
        string $resourceID
    ) : PromiseInterface
    {
        if ($locker->owns()) {

            return $this
                ->client2
                ->eval('
                    local resource = ARGV[1];
                    local locker = ARGV[2];
                    local hash = resource .. ".hash";
                    local channel = resource .. ".channel";
                    redis.call("hdel", hash, locker);
                    local len = redis.call("hlen", hash);
                    local enqueued = 0;
                    if (len > 0) then
                        enqueued = redis.call("lpush", channel, "hola2");
                    end
                    return enqueued;
                ', 2, 'resource', 'locker', $resourceID, $locker->ID());
        }

        return new FulfilledPromise();
    }
}
