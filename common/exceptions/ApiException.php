<?php

namespace common\exceptions;


use Throwable;

class ApiException extends BaseException
{
    const NO_TOKEN_ERROR = 10000;
    const PARAM_ERROR = 10001;
    const TOKEN_PARSE_ERROR = 10002;
    const TOKEN_VERIFY_ERROR = 10003;
    const TOKEN_VALIDATE_ERROR = 10004;
    const ADMIN_NOT_EXIST_ERROR = 10005;
    const ADMIN_PASSWORD_ERROR = 10006;
    const NOT_PERMISSION_ERROR = 10007;
    const ADD_ADMIN_ERROR = 10008;
    const UPDATE_ADMIN_ERROR = 10009;
    const DEL_ADMIN_ERROR = 10010;
    const ADMIN_USERNAME_EXIST_ERROR = 100011;

    public function getExceptionMessage()
    {
        return [
            self::NO_TOKEN_ERROR => '请登录之后再进行该操作',
            self::PARAM_ERROR => '请求参数错误',
            self::TOKEN_PARSE_ERROR => 'token解析失败',
            self::TOKEN_VERIFY_ERROR => 'token验证错误',
            self::TOKEN_VALIDATE_ERROR => 'token已过期',
            self::ADMIN_NOT_EXIST_ERROR => '后台用户不存在',
            self::ADMIN_USERNAME_EXIST_ERROR => '该后台用户名称已存在',
            self::ADMIN_PASSWORD_ERROR => '密码错误',
            self::NOT_PERMISSION_ERROR => '权限不足',
            self::ADD_ADMIN_ERROR => '添加用户失败',
            self::UPDATE_ADMIN_ERROR => '更新用户失败',
            self::DEL_ADMIN_ERROR => '删除用户失败',
        ];
    }

}