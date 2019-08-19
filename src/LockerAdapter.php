<?php
/**
 * File header placeholder
 */

namespace React\Locker;

use React\Promise\PromiseInterface;

/**
 * Interface LockerAdapter
 */
interface LockerAdapter
{
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
    ) : PromiseInterface;

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
    ) : PromiseInterface;
}