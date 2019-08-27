<?php
/**
 * File header placeholder
 */

namespace React\Locker;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class InMemoryAdapter
 */
final class InMemoryAdapter extends DeferredCollectionAdapter
{
    /**
     * Locked deferred
     *
     * @var Deferred[]
     */
    private $currentDeferred = [];

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
        if (!array_key_exists($resourceID, $this->currentDeferred)) {
            $nextDeferred = $this->shiftDeferred($resourceID);
            $this->currentDeferred[$resourceID] = $nextDeferred;
            $nextDeferred->resolve();
        }

        return new FulfilledPromise();
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
        unset($this->currentDeferred[$resourceID]);

        if (
            array_key_exists($resourceID, $this->deferreds) &&
            count($this->deferreds[$resourceID]) > 0
        ) {
            $nextDeferred = $this->shiftDeferred($resourceID);
            $this->currentDeferred[$resourceID] = $nextDeferred;
            $nextDeferred->resolve();
        }
    }
}