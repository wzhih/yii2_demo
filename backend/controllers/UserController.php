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
        $data = $this->getPost([
            'page' => 1,
            'pageSize' => 10,
            'all' => false,
        ]);

        $validate = $this->validateData($data, [
            ['page', 'integer', 'min' => 1],
            ['pageSize', 'integer', 'min' => 1],
            ['all', 'boolean'],
        ]);

        $query = AdminModel::find();

        //是否分页获取
        if (!$validate->all) {
            $query = $query->offset($validate->page - 1)
                ->limit($validate->pageSize);
        }

        $result = $query
            ->select(['id', 'username', 'created_at', 'updated_at'])
            ->with(['roles'])
            ->asArray()
            ->all();
        return $this->success('success', ['users' => $result]);
    }

    public function actionAdd()
    {

    }


}