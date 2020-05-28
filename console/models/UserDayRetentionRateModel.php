<?php
namespace console\models;

use yii\db\ActiveRecord;

class UserDayRetentionRateModel extends ActiveRecord
{
    public static function tableName()
    {
        return 'user_day_retention_rate';
    }
}