<?php


namespace backend\controllers;

use backend\models\AdminRoleModel;
use backend\models\PermissionModel;
use backend\models\RolePermissionModel;
use common\exceptions\ApiException;

class PermissionController extends BaseController
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

        $query = PermissionModel::find();

        //是否分页获取
        if (!$validate->all) {
            $query = $query->offset(($validate->page - 1) * $validate->pageSize)
                ->limit($validate->pageSize);
        }

        $permissions = $query
            ->with(['roles'])
            ->asArray()
            ->all();

        $count = PermissionModel::find()->count();
        $results = [
            'page' => $validate->page,
            'pageSize' => $validate->all ? $count : $validate->pageSize,
            'count' => $count,
            'permissions' => $permissions,
        ];
        return $this->success('success', $results);
    }

    public function actionAdd()
    {
        $data = $this->getPost([
            'name' => '',
            'permission' => '',
        ]);

        $validate = $this->validateData($data, [
            [['name', 'permission'], 'required'],
            ['name', 'string'],
            ['permission', 'string'],
        ]);

        if (PermissionModel::find()->where(['name' => $validate->name])->count()) {
            throw new ApiException(ApiException::PERMISSION_NAME_EXIST_ERROR);
        }

        if (PermissionModel::find()->where(['permission' => $validate->permission])->count()) {
            throw new ApiException(ApiException::PERMISSION_EXIST_ERROR);
        }

        $model = new PermissionModel();
        $model->name = $validate->name;
        $model->permission = $validate->permission;
        if ($model->save()) {
            return $this->success('success');
        }

        throw new ApiException(ApiException::ADD_PERMISSION_ERROR);
    }

    public function actionUpdate()
    {
        $data = $this->getPost([
            'id' => '',
            'name' => '',
            'permission' => '',
        ]);

        $validate = $this->validateData($data, [
            [['id', 'name', 'permission'], 'required'],
            ['id', 'integer'],
            ['name', 'string'],
            ['permission', 'string'],
        ]);

        $model = PermissionModel::findOne($validate->id);
        if (!$model) {
            throw new ApiException(ApiException::PERMISSION_NOT_EXIST_ERROR);
        }

        if (PermissionModel::find()->where(['name' => $validate->name])->andWhere(['<>', 'id', $validate->id])->count()) {
            throw new ApiException(ApiException::PERMISSION_NAME_EXIST_ERROR);
        }

        if (PermissionModel::find()->where(['permission' => $validate->permission])->andWhere(['<>', 'id', $validate->id])->count()) {
            throw new ApiException(ApiException::PERMISSION_EXIST_ERROR);
        }

        $model->name = $validate->name;
        $model->permission = $validate->permission;
        if ($model->save()) {
            return $this->success('success');
        }

        throw new ApiException(ApiException::UPDATE_PERMISSION_ERROR);

    }

    public function actionDelete()
    {
        $data = $this->getPost([
            'id' => '',
        ]);

        $validate = $this->validateData($data, [
            ['id', 'integer'],
            ['id', 'required'],
        ]);

        $transaction = PermissionModel::getDb()->beginTransaction();
        try {
            //删除关系
            RolePermissionModel::deleteAll(['permission_id' => $validate->id]);
            //删除记录
            PermissionModel::deleteAll(['id' => $validate->id]);

            $transaction->commit();
            return $this->success('success');
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw new ApiException(ApiException::DEL_ADMIN_ERROR, $exception->getMessage());
        }

    }

    public function actionShow()
    {
        $data = $this->getPost([
            'id' => '',
        ]);

        $validate = $this->validateData($data, [
            ['id', 'integer'],
            ['id', 'required'],
        ]);

        $model = PermissionModel::find()
            ->where(['id' => $validate->id])
            ->with(['roles'])
            ->asArray()
            ->one();
        if (!$model) {
            throw new ApiException(ApiException::PERMISSION_NOT_EXIST_ERROR);
        }

        return $this->success('success', ['permission' => $model]);
    }
}