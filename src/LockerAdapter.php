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