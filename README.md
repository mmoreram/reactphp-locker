# Locker

Async locker for [ReactPHP](https://reactphp.org/)

[![CircleCI](https://circleci.com/gh/mmoreram/reactphp-locker.svg?style=svg)](https://circleci.com/gh/mmoreram/reactphp-locker)

**Table of Contents**
- [Quickstart example](#quickstart-example)
- [Enqueue Timeout](#enqueue-timeout)
- [Adapters](#adapters)
    - [InMemory](#inmemory-adapter)
    - [Redis](#redis-adapter)
- [Install](#install)
- [Tests](#tests)
- [License](#license)
    
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
    ->enqueue()
    ->then(function(Locker $locker) {
        // Do whatever
    })
    ->release();
```

Only one requester will have the possibility to work with the same resource at
the same time, and all other resource clients will be just waiting, without
asking the resource once and again.

## Enqueue Timeout

Your resource clients can easily forget about them by using some timeout. If the
resource is not a must, or simply you want to throw an exception when the
resource is not been available for a while, then this is the place for this.

```php
$promise = $locker
    ->enqueue(10)
    ->then(function(Locker $locker) {
        // Do whatever
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

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
$ composer require mmoreram/react-locker:dev-master
```

This library requires PHP7.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

## License

This project is released under the permissive [MIT license](LICENSE).