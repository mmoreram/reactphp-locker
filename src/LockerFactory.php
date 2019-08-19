<?php
/**
 * File header placeholder
 */

namespace React\Locker;

/**
 * Class LockerFactory
 */
class LockerFactory
{
    /**
     * Create locker
     *
     * @param LockerAdapter $adapter
     * @param string $resourceID
     *
     * @return Locker
     */
    public static function create(
        LockerAdapter $adapter,
        string $resourceID
    ) : Locker
    {
        return new Locker(
            $adapter,
            $resourceID
        );
    }
}