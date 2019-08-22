# Locker

Async locker for [ReactPHP](https://reactphp.org/)

**Table of Contents**
- [Quickstart example](#quickstart-example)
- [Timeout](#timeout)
- [Adapters](#adapters)
    - [InMemory](#inmemory-adapter)
    - [Redis](#redis-adapter)
    
## Quickstart example

ReactPHP lockers are a bit different. Because they are built on top a
non-blocking and asynchronous library, they have the power of being really
reactive. That means that the performance increases so much, and no pulling is
done in any locker adapter.

This is a small example that would properly work if all the resource lockers are
alive in the same PHP thread.

```php
$loop = React\EventLoop\Factory::create();
$adapter = new InMemoryAdapter($loop);
$locker = LockerFactory::create($adapter, 'res1');

$promise = $locker
    ->acquire(function(Locker $locker) {
        // Do whatever
    })
    ->release();
```

Only one requester will have the possibility to work with the same resource at
the same time, and all other resource clients will be just waiting, without
asking the resource once and again.

## Timeout

Your resource clients can easily forget about them by using some timeout. If the
resource is not a must, or simply you want to throw an exception when the
resource is not been available for a while, then this is the place for this.

```php
$promise = $locker
    ->acquire(function(Locker $locker, 10) {
        
        // Do whatever during the next 10 seconds
    }, function(TimeoutException $exception) {
        
        // After 10 seconds, nothing to do here
    })
    ->release();
```

## Adapters

You can select different adapters, depending on the tecnologies you work with,
but as well, depending on the scope of your resource. It has no sense to work
with a in-memory resource if your scope is a cluster of servers.

### InMemory Adapter

The scope is the PHP thread


```php
$adapter = new InMemoryAdapter($loop);
$locker = LockerFactory::create($adapter, 'res1');
```

### Redis Adapter

This adapter can work really well for a cluster of services. Because of the
way this adapter works and the behavior of the Redis library implementation for
ReactPHP, this adapter needs two connections to work properly.

```php
$Factory = new Factory();
$client1 = $factory->createLazyClient('localhost');
$client2 = $factory->createLazyClient('localhost');

$adapter = new RedisAdapter($loop, $client1, $client2);
$locker = LockerFactory::create($adapter, 'res1');
```

If you manually have to create the clients, then you can use the built-in
factory method to build the adapter.

```php
$adapter = RedisAdapter::createByCredentials($loop, new Factory($loop), 'localhost');
$locker = LockerFactory::create($adapter, 'res1');
```
