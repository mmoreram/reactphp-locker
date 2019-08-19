<?php

namespace React\Locker;

use React\Promise\PromiseInterface;

/**
 * Class Locker
 */
class Locker
{
    /**
     * Locker adapter
     *
     * @var LockerAdapter
     */
    private $adapter;

    /**
     * Owns
     *
     * @var bool
     */
    private $owns = false;

    /**
     * Resource ID
     *
     * @var string
     */
    private $resourceID;

    /**
     * ID
     *
     * @var string
     */
    private $ID;

    /**
     * Locker constructor.
     *
     * @param LockerAdapter $adapter
     * @param string $resourceID
     */
    public function __construct(
        LockerAdapter $adapter,
        string $resourceID
    )
    {
        $this->adapter = $adapter;
        $this->resourceID = $resourceID;
        $this->ID = spl_object_hash($this);
    }

    /**
     * Acquire locker
     *
     * @param float $timeout
     *
     * @return PromiseInterface
     */
    public function enqueue(float $timeout = 0.0) : PromiseInterface
    {
        return $this
            ->adapter
            ->enqueue($this, $this->resourceID, $timeout)
            ->then(function() {
                $this->owns = true;
                return $this;
            });
    }

    /**
     * Release
     *
     * @return PromiseInterface
     */
    public function release() : PromiseInterface
    {
        return $this
            ->adapter
            ->release($this, $this->resourceID)
            ->then(function() {
                $this->owns = false;
                return $this;
            });
    }

    /**
     * Owns
     *
     * @return bool
     */
    public function owns() : bool
    {
        return $this->owns;
    }

    /**
     * Get locker id
     */
    public function ID() : string
    {
        return $this->ID;
    }
}