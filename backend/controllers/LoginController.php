<?php


namespace backend\controllers;

use backend\models\AdminModel;
use common\exceptions\ApiException;
use Yii;
use yii\base\DynamicModel;
use yii\web\Controller;
use common\traits\Token;
use common\traits\ApiResponse;

class LoginController extends Controller
{
    use ApiResponse, Token;

    public function actionLogin()
    {
        $request = Yii::$app->request;
        $data = [
            'username' => $request->post('username'),
            'password' => $request->post('password'),
        ];

        $model = DynamicModel::validateData($data, [
            [['username', 'password'], 'required'],
        ]);

        if ($model->hasErrors()) {
            $errors = $model->getFirstErrors();
            throw new ApiException(ApiException::PARAM_ERROR, array_shift($errors));
        }

        $admin = AdminModel::find()
            ->where(['username' => $model->username])
            ->one();

        if (!$admin) {
            throw new ApiException(ApiException::ADMIN_NOT_EXIST_ERROR);
        }

        if (!password_verify($model->password, $admin->password)) {
            throw new ApiException(ApiException::ADMIN_PASSWORD_ERROR);
        }

        $data = [
            'id' => $admin->id,
            'username' => $admin->username,
        ];
        $data['token'] = self::generateToken($data);
        return $this->success('success', $data);
    }
}