<?php


namespace backend\controllers;

use backend\models\AdminModel;
use Yii;
use yii\base\DynamicModel;
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

    /**
     * 根据规则获取POST参数
     * @param array $rules
     * @return array
     */
    public function getPost(array $rules = [])
    {
        $request = Yii::$app->request;
        $data = [];
        foreach ($rules as $name => $default) {
            $value = empty($request->post($name)) ? $default : $request->post($name);
            if (is_array($default) && !is_array($value)) {
                $data[$name] = [];
            }

            $data[$name] = $this->trimStr($value);
        }

        return $data;
    }

    /**
     * 根据传入数据和规则，验证数据格式
     * @param array $data
     * @param array $rules
     * @return DynamicModel
     * @throws ApiException
     * @throws \yii\base\InvalidConfigException
     */
    public function validateData(array $data, array $rules)
    {
        $model = DynamicModel::validateData($data, $rules);

        if ($model->hasErrors()) {
            $errors = $model->getFirstErrors();
            throw new ApiException(ApiException::PARAM_ERROR, array_shift($errors));
        }

        return $model;
    }

    /**
     * 过滤变量前后空格和特殊字符
     * @param $data
     * @return array|string
     */
    public function trimStr($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->trimStr($value);
            }

            return $data;
        } else {
            return trim($data);
        }
    }
}