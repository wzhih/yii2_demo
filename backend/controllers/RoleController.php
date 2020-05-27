<?php


namespace backend\controllers;

use Yii;
use common\exceptions\ApiException;
use backend\models\AdminRoleModel;
use backend\models\RoleModel;
use backend\models\RolePermissionModel;

class RoleController extends BaseController
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

        $query = RoleModel::find();

        //是否分页获取
        if (!$validate->all) {
            $query = $query->offset(($validate->page - 1) * $validate->pageSize)
                ->limit($validate->pageSize);
        }

        $roles = $query
            ->with(['permissions'])
            ->asArray()
            ->all();

        $count = RoleModel::find()->count();
        $results = [
            'page' => $validate->page,
            'pageSize' => $validate->all ? $count : $validate->pageSize,
            'count' => $count,
            'roles' => $roles,
        ];
        return $this->success('success', $results);
    }

    public function actionAdd()
    {
        $data = $this->getPost([
            'name' => '',
            'permissions' => [],
        ]);

        $validate = $this->validateData($data, [
            ['username', 'required'],
            ['name', 'string'],
            ['permissions', 'default', 'value' => []],
        ]);

        if (RoleModel::find()->where(['name' => $validate->name])->count()) {
            throw new ApiException(ApiException::ROLE_NAME_EXIST_ERROR);
        }

        $model = new RoleModel();
        $model->name = $validate->name;

        $transaction = RoleModel::getDb()->beginTransaction();
        try {
            if ($model->save()) {
                if (!empty($validate->permissions)) {
                    $data = [];
                    foreach ($validate->permissions as $permission) {
                        $data[] = [$model->id, $permission];
                    }
                    RolePermissionModel::getDb()
                        ->createCommand()
                        ->batchInsert('t_role_permission', ['role_id', 'permission_id'], $data)
                        ->execute();
                }
            }

            $transaction->commit();
            return $this->success('success');
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw new ApiException(ApiException::ADD_ROLE_ERROR, $exception->getMessage());
        }

    }

    public function actionUpdate()
    {
        $data = $this->getPost([
            'id' => '',
            'name' => '',
            'permissions' => [],
        ]);

        $validate = $this->validateData($data, [
            [['id', 'name'], 'required'],
            ['id', 'integer'],
            ['name', 'string'],
            ['permissions', 'default', 'value' => []],
        ]);

        $model = RoleModel::findOne($validate->id);
        if (!$model) {
            throw new ApiException(ApiException::ROLE_NOT_EXIST_ERROR);
        }

        //admin角色不可修改
        if ($model->name == 'admin') {
            return $this->success('success');
        }

        $exist = RoleModel::find()
            ->where(['name' => $validate->name])
            ->andWhere(['<>', 'id', $validate->id])
            ->count();
        if ($exist) {
            throw new ApiException(ApiException::ROLE_NAME_EXIST_ERROR);
        }

        //非admin角色
        $model->name = $validate->name;
        $transaction = RoleModel::getDb()->beginTransaction();
        try {
            if ($model->save()) {
                //删除旧关联关系
                RolePermissionModel::deleteAll(['role_id' => $model->id]);
                //添加新关联关系
                if (!empty($validate->permissions)) {
                    $data = [];
                    foreach ($validate->permissions as $permission) {
                        $data[] = [$model->id, $permission];
                    }
                    RolePermissionModel::getDb()
                        ->createCommand()
                        ->batchInsert('t_role_permission', ['role_id', 'permission_id'], $data)
                        ->execute();
                }
            }

            $transaction->commit();
            return $this->success('success');
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw new ApiException(ApiException::UPDATE_ADMIN_ERROR, $exception->getMessage());
        }

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

        $transaction = RoleModel::getDb()->beginTransaction();
        try {
            //删除用户-角色关系
            AdminRoleModel::deleteAll(['role_id' => $validate->id]);
            //删除角色-权限关系
            RolePermissionModel::deleteAll(['role_id' => $validate->id]);
            //删除记录
            RoleModel::deleteAll(['id' => $validate->id]);

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

        $model = RoleModel::find()
            ->where(['id' => $validate->id])
            ->with(['permissions'])
            ->asArray()
            ->one();
        if (!$model) {
            throw new ApiException(ApiException::ROLE_NOT_EXIST_ERROR);
        }

        return $this->success('success', ['role' => $model]);
    }
}