<?php
/**
 * Created by PhpStorm.
 * User: danielshen
 * Date: 2019/7/10
 * Time: 17:02
 */

namespace Shencongcong\Mysql\Exceptions;

class HttpException extends Exception
{
    public $raw = [];

    public function __construct(string $message = "", int $code = 0,array $raw)
    {
        parent::__construct($message, intval($code));

        $this->raw = $raw;
    }
}