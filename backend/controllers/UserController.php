<?php


namespace backend\controllers;

use Yii;
use yii\base\DynamicModel;
use backend\models\AdminModel;
use common\exceptions\ApiException;

class UserController extends BaseController
{
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $data = [
            'page' => $request->post('page'),
            'pageSize' => $request->post('pageSize'),
            'all' => $request->post('all', false),
        ];

        $model = DynamicModel::validateData($data, [
            ['page', 'integer', 'min' => 1],
            ['page', 'default', 'value' => 1],
            ['pageSize', 'integer', 'min' => 1],
            ['pageSize', 'default', 'value' => 10],
            ['all', 'boolean'],
        ]);

        if ($model->hasErrors()) {
            $errors = $model->getFirstErrors();
            throw new ApiException(ApiException::PARAM_ERROR, array_shift($errors));
        }

        $query = AdminModel::find();

        //是否分页获取
        if (!$model->all) {
            $query = $query->offset($model->page - 1)
                ->limit($model->pageSize);
        }

        $result = $query
            ->select(['id', 'username', 'created_at', 'updated_at'])
            ->with(['roles'])
            ->asArray()
            ->all();
        return $this->success('success', ['users' => $result]);
    }
}