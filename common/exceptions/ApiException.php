<?php

namespace common\exceptions;


use Throwable;

class ApiException extends BaseException
{
    const PARAM_ERROR = 10000;

    public function getExceptionMessage()
    {
        return [
            self::PARAM_ERROR => '参数错误',
        ];
    }

}