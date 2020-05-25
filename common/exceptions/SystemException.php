<?php
namespace common\exceptions;

use common\traits\ApiResponse;
use yii\web\ErrorHandler;
use yii\web\Response;

class SystemException extends ErrorHandler
{
    use ApiResponse;

    protected function renderException($exception)
    {
        if (\Yii::$app->params['enableCustomCatchException']) {
            $response = \Yii::$app->response;
            $response->format = Response::FORMAT_JSON;
            $response->content = json_encode($this->error($exception->getCode(), $exception->getMessage()));
            $response->send();
        } else {
            parent::renderException($exception); // TODO: Change the autogenerated stub
        }
    }
}