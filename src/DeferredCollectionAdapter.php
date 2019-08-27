<?php


namespace React\Locker;


use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class DeferredCollectionAdapter
 */
abstract class DeferredCollectionAdapter implements LockerAdapter
{
    /**
     * Loop
     *
     * @var LoopInterface
     */
    protected $loop;

    /**
     * Deferreds
     *
     * @var array
     */
    protected $deferreds = [];

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
     * Enqueue.
     *
     * Timeout value must be defined in milliseconds.
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
        $deferred = new Deferred();
        $promise = $deferred->promise();

        if ($timeout > 0) {

            $this
                ->loop
                ->addTimer($timeout/1000, function () use ($deferred, $resourceID, $locker) {
                    unset($this->deferreds[$resourceID][$locker->ID()]);
                    $deferred->reject(new TimeoutException());
                });
        }

        if (!array_key_exists($resourceID, $this->deferreds)) {
            $this->deferreds[$resourceID] = [];
        }

        $this->deferreds[$resourceID][$locker->ID()] = $deferred;

        return $this
            ->onEnqueue($resourceID, $locker, $timeout, $deferred)
            ->then(function() use ($promise) {
                return $promise;
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
        $promise = new FulfilledPromise($resourceID);
        if ($locker->owns()) {
            $promise = $promise
                ->then(function() use ($locker, $resourceID) {

                    return $this->assignNextOwner($resourceID, $locker);
                })
                ->then(function($_) {
                    // Do not return anything
                });
        }

        return $promise;
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
    abstract protected function onEnqueue(
        string $resourceID,
        Locker $locker,
        int $timeoutMilliseconds,
        Deferred $deferred
    ): PromiseInterface;


    /**
     * Assign next owner
     *
     * @param string $resourceID
     * @param Locker $locker
     *
     * @return PromiseInterface|mixed
     */
    abstract protected function assignNextOwner(
        string $resourceID,
        Locker $locker
    );

    /**
     * Deferreds
     */

    /**
     * Set deferred
     *
     * @param Locker $locker
     * @param string $resourceID
     * @param Deferred $deferred
     */
    protected function setDeferred(
        Locker $locker,
        string $resourceID,
        Deferred $deferred
    ) {
        if (!is_array($this->deferreds[$resourceID])) {
            $this->deferreds[$resourceID] = [];
        }

        $this->deferreds[$resourceID][$locker->ID()] = $deferred;
    }

    /**
     * Get deferred
     *
     * @param string $lockerID
     * @param string $resourceID
     * @param bool $delete
     *
     * @return Deferred|null
     */
    protected function getDeferred(
        string $lockerID,
        string $resourceID,
        bool $delete = false
    ) : ? Deferred {

        if (
            !array_key_exists($resourceID, $this->deferreds) ||
            !array_key_exists($lockerID, $this->deferreds[$resourceID])
        ) {
            return null;
        }

        $deferred = $this->deferreds[$resourceID][$lockerID];
        if ($delete) {
            unset($this->deferreds[$resourceID][$lockerID]);
        }

        return $deferred;
    }

    /**
     * Shift deferred
     *
     * @param string $resourceID
     *
     * @return Deferred
     */
    protected function shiftDeferred(string $resourceID) : Deferred
    {
        return array_shift($this->deferreds[$resourceID]);;
    }
}