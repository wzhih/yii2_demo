<?php
namespace console\models;

use yii\db\ActiveRecord;

class UserLoginLogModel extends ActiveRecord
{
    public static function tableName()
    {
        return 'user_login_log';
    }
}