<?php


namespace backend\controllers;

use backend\models\AdminModel;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use common\traits\Token;
use common\traits\ApiResponse;
use common\exceptions\ApiException;

class BaseController extends Controller
{
    use ApiResponse, Token;

    public $adminModel;

    //不需要验证token的控制器
    public $noNeedAuthController = [
        'login',
    ];

    //不需要验证token的控制器操作
    public $noNeedAuthAction = [
        'base' => ['index',],
    ];

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
            $controller = $action->controller;

            if (in_array($controller->id, $this->noNeedAuthController)) {
                return true;
            }

            if (isset($this->noNeedAuthAction[$controller->id]) && in_array($action->id, $this->noNeedAuthAction[$controller->id])) {
                return true;
            }

            $request = Yii::$app->request;
            $token = $request->getHeaders()->get('X-Token');
            if (empty($token)) {
                throw new ApiException(ApiException::NO_TOKEN_ERROR);
            }

            //验证token
            self::verifyToken($token);
            $data = self::getTokenData($token);

            $this->adminModel = AdminModel::findOne($data->id);
            if (!$this->adminModel) {
                throw new ApiException(ApiException::ADMIN_NOT_EXIST_ERROR);
            }

            //拥有admin角色的用户具有所有权限
            $roles = array_column($this->adminModel->roles, 'name');
            if (in_array('admin', $roles)) {
                return true;
            }

            //验证权限
            $permission = $controller->id . '/' . $action->id;
            $permissions = array_column($this->adminModel->permissions, 'permission');
            if (in_array($permission, $permissions)) {
                return true;
            }

            throw new ApiException(ApiException::NOT_PERMISSION_ERROR);
        }

        return false;
    }

    public function actionIndex()
    {
        return $this->success('success');
    }

}