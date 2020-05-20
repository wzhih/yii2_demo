<?php


namespace backend\controllers;

use common\traits\ApiResponse;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;

class BaseController extends Controller
{
    use ApiResponse;

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'index' => ['get', 'post'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            return true;
        }

        return false;
    }

    public function actionIndex()
    {
        return $this->success('success');
    }

}