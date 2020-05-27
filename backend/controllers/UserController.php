<?php


namespace backend\controllers;

use backend\models\AdminRoleModel;
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
            $query = $query->offset(($validate->page - 1) * $validate->pageSize)
                ->limit($validate->pageSize);
        }

        $users = $query
            ->select(['id', 'username', 'created_at', 'updated_at'])
            ->with(['roles'])
            ->asArray()
            ->all();

        $count = AdminModel::find()->count();
        $results = [
            'page' => $validate->page,
            'pageSize' => $validate->all ? $count : $validate->pageSize,
            'count' => $count,
            'users' => $users,
        ];
        return $this->success('success', $results);
    }

    public function actionAdd()
    {
        $data = $this->getPost([
            'username' => '',
            'password' => '',
            'roles' => [],
        ]);

        $validate = $this->validateData($data, [
            [['username', 'password'], 'required'],
            ['username', 'string'],
            ['password', 'string'],
            ['roles', 'default', 'value' => []],
        ]);

        if (AdminModel::find()->where(['username' => $validate->username])->count()) {
            throw new ApiException(ApiException::ADMIN_USERNAME_EXIST_ERROR);
        }

        $model = new AdminModel();
        $model->username = $validate->username;
        $model->password = password_hash($validate->password, PASSWORD_DEFAULT);

        $transaction = AdminModel::getDb()->beginTransaction();
        try {
            if ($model->save()) {
                if (!empty($validate->roles)) {
                    $data = [];
                    foreach ($validate->roles as $role) {
                        $data[] = [$model->id, $role];
                    }
                    AdminRoleModel::getDb()
                        ->createCommand()
                        ->batchInsert('t_admin_role', ['admin_id', 'role_id'], $data)
                        ->execute();
                }
            }

            $transaction->commit();
            return $this->success('success');
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw new ApiException(ApiException::ADD_ADMIN_ERROR, $exception->getMessage());
        }

    }

    public function actionUpdate()
    {
        $data = $this->getPost([
            'id' => '',
            'username' => '',
            'password' => '',
            'roles' => [],
        ]);

        $validate = $this->validateData($data, [
            [['id', 'username'], 'required'],
            ['id', 'integer'],
            ['username', 'string'],
            ['password', 'string'],
            ['roles', 'default', 'value' => []],
        ]);

        $model = AdminModel::findOne($validate->id);
        if (!$model) {
            throw new ApiException(ApiException::ADMIN_NOT_EXIST_ERROR);
        }

        $exist = AdminModel::find()
            ->where(['username' => $validate->username])
            ->andWhere(['<>', 'id', $validate->id])
            ->count();
        if ($exist) {
            throw new ApiException(ApiException::ADMIN_USERNAME_EXIST_ERROR);
        }

        //有传密码，才修改
        if ($validate->password) {
            $model->password = password_hash($validate->password, PASSWORD_DEFAULT);
        }

        //admin用户只可以修改密码
        if ($model->username == 'admin') {
            if ($model->save()) {
                return $this->success('success');
            }
        }

        //非admin用户
        $model->username = $validate->username;
        $transaction = AdminModel::getDb()->beginTransaction();
        try {
            if ($model->save()) {
                //删除旧关联关系
                AdminRoleModel::deleteAll(['admin_id' => $model->id]);
                //添加新关联关系
                if (!empty($validate->roles)) {
                    $data = [];
                    foreach ($validate->roles as $role) {
                        $data[] = [$model->id, $role];
                    }
                    AdminRoleModel::getDb()
                        ->createCommand()
                        ->batchInsert('t_admin_role', ['admin_id', 'role_id'], $data)
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

        $transaction = AdminModel::getDb()->beginTransaction();
        try {
            //删除关系
            AdminRoleModel::deleteAll(['admin_id' => $validate->id]);
            //删除记录
            AdminModel::deleteAll(['id' => $validate->id]);

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

        $model = AdminModel::find()
            ->select(['id', 'username', 'created_at', 'updated_at'])
            ->where(['id' => $validate->id])
            ->with(['roles', 'permissions'])
            ->asArray()
            ->one();
        if (!$model) {
            throw new ApiException(ApiException::ADMIN_NOT_EXIST_ERROR);
        }

        return $this->success('success', ['user' => $model]);
    }
}