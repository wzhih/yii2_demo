<?php
namespace common\traits;

use yii\web\Response;

trait ApiResponse {
    public $code = 0;
    public $message = '';
    public $data = [];

    public function success(string $message = '', array $data = [])
    {
        return $this->response(0, $message, $data);
    }

    public function error(int $code, string $message, array $data = [])
    {
        if ($code == 0) {
            $code = -1;
        }
        return $this->response($code, $message, $data);
    }

    public function response(int $code, string $message, array $data = [])
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => (object) $data,
        ];
    }

}