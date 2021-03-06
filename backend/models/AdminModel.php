<?php


namespace backend\models;


class AdminModel extends BaseModel
{
    public static function tableName()
    {
        return 't_admin'; // TODO: Change the autogenerated stub
    }

    /**
     * 获取多对多关联的角色
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getRoles()
    {
        return $this->hasMany(RoleModel::className(), ['id' => 'role_id'])
            ->viaTable('t_admin_role', ['admin_id' => 'id']);
    }

    /**
     * 根据上面获取`getRoles`角色方法，获取角色与权限关联
     * @return \yii\db\ActiveQuery
     */
    public function getRolePermissions()
    {
        return $this->hasMany(RolePermissionModel::className(), ['role_id' => 'id'])
            ->via('roles');
    }

    /**
     * 根据上面获取`getRolePermissions`角色与权限关联关系方法，获取权限
     * @return \yii\db\ActiveQuery
     */
    public function getPermissions()
    {
        return $this->hasMany(PermissionModel::className(), ['id' => 'permission_id'])
            ->via('rolePermissions');
    }

}