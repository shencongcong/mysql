<h1 align="center"> mysql </h1>

<p align="center"> a esay method operating mysql.</p>


## 环境需求

- PHP >= 5.6

## Installing

```shell
$ composer require shencongcong/mysql -vvv
```

## Usage

```php
use Shencongcong\Mysql\Mysql

$config = [
          'host'     => '10.0.0.180',
          'database' => 'db',
          'user'     => 'root',
          'password' => 'root',
          'port'     => '3306',
         ],

```

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/shencongcong/mysql/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/shencongcong/mysql/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT