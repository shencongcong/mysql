<?php
/**
 * Created by PhpStorm.
 * User: danielshen
 * Date: 2019/7/10
 * Time: 15:22
 */

namespace Shencongcong\Mysql\Exceptions;

class GatewayErrorException extends Exception
{

    public function __construct(string $message = "", int $code = 0)
    {
        parent::__construct($message, intval($code));
    }
}