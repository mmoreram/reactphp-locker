<?php
/**
 * File header placeholder
 */

namespace React\Locker;

use Clue\React\Redis\Client;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Class RedisAdapter
 */
final class RedisAdapter extends DeferredCollectionAdapter
{
    /**
     * Client
     *
     * @var Client
     */
    private $client;

    /**
     * Subscriber Client
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
        parent::__construct($loop);

        $this->client = $client;
        $this->client2 = $client2;
        $channelExp = '*.channel';
        $client2->psubscribe($channelExp);
        $client2->on('pmessage', function ($currentChannelExp, $channel, $payload) use ($channelExp) {

            if ($channelExp !== $currentChannelExp) {
                return;
            }

            $resourceID = str_replace('.channel', '', $channel);
            $deferred = $this->getDeferred($payload, $resourceID, true);

            if (!is_null($deferred)) {
                $deferred->resolve();
            }
        });
    }

    /**
     * On enqueue action.
     *
     * Timeout value are in milliseconds.
     *
     * @param string $resourceID
     * @param Locker $locker
     * @param int $timeoutMilliseconds
     * @param Deferred $deferred
     *
     * @return PromiseInterface
     */
    protected function onEnqueue(
        string $resourceID,
        Locker $locker,
        int $timeoutMilliseconds,
        Deferred $deferred
    ): PromiseInterface
    {
        return $this
            ->client
            ->eval('
                    local resource = ARGV[1];
                    local locker = ARGV[2];
                    local timeout = tonumber(ARGV[3]);
                    
                    local registry = resource .. ".registry";
                    local combined = resource .. "~~" .. locker .. ".combined";
                    local ownerKey = resource .. ".owner";
                    
                    local list = resource .. ".list";
                    local channel = resource .. ".channel";
                    
                    local owner = redis.call("get", ownerKey);
                    
                    if (type(owner) == "string") then
                        redis.call("hset", registry, locker, "1");
                        redis.call("rpush", list, locker);
                        redis.call("set", combined, timeout);

                        if (timeout > 0) then
                            redis.call("pexpire", combined, timeout);
                        end
                    else
                        redis.call("set", ownerKey, locker);
                        redis.call("publish", channel, locker);
                    end
                ', 3, 'resource', 'locker', 'timeout', $resourceID, $locker->ID(), $timeoutMilliseconds
            );
    }

    /**
     * Assign next owner
     *
     * @param string $resourceID
     * @param Locker $locker
     *
     * @return PromiseInterface|mixed
     */
    protected function assignNextOwner(
        string $resourceID,
        Locker $locker
    )
    {
        return $this
            ->client
            ->eval('
                    local resource = ARGV[1];
                    local locker = ARGV[2];
                    
                    local registry = resource .. ".registry";
                    local combined = resource .. "~~" .. locker .. ".combined";
                    local ownerKey = resource .. ".owner";
                    
                    local list = resource .. ".list";
                    local channel = resource .. ".channel";
                    
                    redis.call("del", ownerKey);
                    
                    local nextLocker = nil;
                    local nextCombined = nil;
                    local missing = 0;
                    local ok = false;
                        
                    repeat
                        nextLocker = redis.call("lpop", list);
                        if (type(nextLocker) == "string") then
                            nextCombined = resource .. "~~" .. nextLocker .. ".combined";
                            
                            if (redis.call("hexists", registry, nextLocker) == 1) then
                                if (redis.call("exists", nextCombined) == 1) then
                                    ok = true;
                                else
                                    redis.call("hdel", registry, nextLocker);
                                end
                            elseif (redis.call("llen", list) == 0) then
                                ok = true;
                                nextLocker = nil
                            end
                        else
                            ok = true;
                        end
                    until(ok == true)
                    
                    if (type(nextLocker) == "string") then
                    
                        redis.call("hdel", registry, nextLocker);
                        redis.call("del", nextCombined);
                        redis.call("set", ownerKey, nextLocker);
                        redis.call("publish", channel, nextLocker);
                    end
                ', 2, 'resource', 'locker', $resourceID, $locker->ID()
            );
    }
}
