<?php


namespace common\traits;

use common\exceptions\ApiException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\ValidationData;

trait Token
{
    /**
     * 生成jwt
     * @param array $data
     * @param int $expire
     * @param string $key
     * @return string
     */
    public static function generateToken(array $data, $key = 'data', int $expire = 0)
    {
        $config = \Yii::$app->params['token'];
        $signer = new Sha256();
        $time = time();
        $expire = empty($expire) ? $config['expiration'] : $expire;

        $builder = new Builder();
        $token = $builder->issuedBy($config['issuer'])
            ->permittedFor($config['audience'])
            ->identifiedBy(uniqid(), true)
            ->issuedAt($time)
            ->canOnlyBeUsedAfter($time)
            ->expiresAt($time + $expire)
            ->withClaim($key, $data)
            ->getToken($signer, new Key($config['key']));

        return base64_encode($token);
    }

    /**
     * 验证jwt
     * @param string $token
     * @return bool
     * @throws ApiException
     */
    public static function verifyToken(string $token)
    {
        try {
            $token = (new Parser())->parse(base64_decode($token));
        } catch (\Exception $e) {
            throw new ApiException(ApiException::TOKEN_PARSE_ERROR);
        }
        $signer = new Sha256();

        //验证是否过期(默认使用当前时间验证)
        $data = new ValidationData();
        if (!$token->validate($data)) {
            throw new ApiException(ApiException::TOKEN_VALIDATE_ERROR);
        }

        //验证是否被修改过
        $key = $config = \Yii::$app->params['token']['key'];;
        if (!$token->verify($signer, $key)) {
            throw new ApiException(ApiException::TOKEN_VERIFY_ERROR);
        }

        return true;
    }

    /**
     * 解析jwt，获取数据
     * @param string $token
     * @param string $key
     * @return array|mixed
     * @throws ApiException
     */
    public static function getTokenData(string $token, string $key = 'data')
    {
        try {
            $token = (new Parser())->parse(base64_decode($token));
        } catch (\Exception $e) {
            throw new ApiException(ApiException::TOKEN_PARSE_ERROR);
        }

        if ($key) {
            return $token->getClaim($key);
        }

        return $token->getClaims();
    }
}
