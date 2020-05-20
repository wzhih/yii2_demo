<?php

namespace common\exceptions;


use Throwable;

abstract class BaseException extends \Exception
{
    const CODE_UNDEFINED = 1000;

    public $baseExceptionMessages = [
        self::CODE_UNDEFINED => '错误码未定义',
    ];

    abstract public function getExceptionMessage();

    public function __construct(int $code, string $message = null, Throwable $previous = null)
    {
        $exceptionMessages = $this->baseExceptionMessages + $this->getExceptionMessage();

        //错误码未定义，正常输出code,修改message
        if (!array_key_exists($code, $exceptionMessages)) {
            $message = $exceptionMessages[self::CODE_UNDEFINED];
        }

        $message = $message ?? $exceptionMessages[$code];

        parent::__construct($message, $code, $previous);
    }
}