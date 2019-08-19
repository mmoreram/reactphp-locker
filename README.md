# Locker

Async locker for [ReactPHP](https://reactphp.org/)

**Table of Contents**
- [Quickstart example](#quickstart-example)
- [Adapters](#adapters)
    - [InMemory](#inmemory)
    
## Quickstart example

Here is a simple in-memory resource lock

```php
$loop = React\EventLoop\Factory::create();
$adapter = new InMemoryAdapter($loop);
$locker = LockerFactort::create($adapter, 'res1');

$promise = $locker
    ->acquire(function(Locker $locker) {
        // Do whatever
    })
    ->release();
```