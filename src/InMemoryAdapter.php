<?php
/**
 * File header placeholder
 */

namespace React\Locker;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use SplQueue;

/**
 * Class InMemoryAdapter
 */
class InMemoryAdapter implements LockerAdapter
{
    /**
     * Loop
     *
     * @var LoopInterface
     */
    private $loop;

    /**
     * Deferreds
     *
     * @var array
     */
    private $deferreds = [];

    /**
     * InMemoryAdapter constructor.
     *
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Enqueue
     *
     * @param Locker $locker
     * @param string $resourceID
     * @param float $timeout
     *
     * @return PromiseInterface
     */
    public function enqueue(
        Locker $locker,
        string $resourceID,
        float $timeout
    ) : PromiseInterface
    {
        $deferred = new Deferred();
        $promise = $deferred->promise();

        if ($timeout > 0) {
            $this
                ->loop
                ->addTimer($timeout, function () use ($deferred, $resourceID) {
                    unset($this->deferreds[$resourceID][spl_object_hash($deferred)]);
                    $deferred->reject();
                });
        }

        if (!isset($this->deferreds[$resourceID])) {
            $this->deferreds[$resourceID] = [];
            $deferred->resolve();

            return $promise;
        }

        $this->deferreds[$resourceID][spl_object_hash($deferred)] = $deferred;

        return $deferred
            ->promise()
            ->then(function() use ($locker) {
                return $locker;
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
            $this->assignNextOwner($resourceID);
        }

        return new FulfilledPromise();
    }

    /**
     * Assign next owner
     *
     * @param string $resourceID
     */
    private function assignNextOwner(string $resourceID)
    {
        if (
            $this->deferreds[$resourceID] &&
            count($this->deferreds[$resourceID]) > 0
        ) {
            $nextDeferred = array_shift($this->deferreds[$resourceID]);
            $nextDeferred->resolve();
        }
    }
}