<?php

namespace console\controllers;

use Yii;
use Carbon\Carbon;
use yii\console\Controller;
use yii\console\Exception;
use console\models\UserModel;

class UserController extends Controller
{
    public function actionIndex()
    {
        echo 'timezone: ' . $this->now->timezone . "\n";
        return 0;
    }

    public function actionDayRetentionRate(string $start = '', string $end = '')
    {
        //判断是否输入起始日期参数
        if (!$start) {
            //没有输入，默认取前天
            $start = Carbon::now()->subDays(2);
        } else {
            //有输入，解析
            $start = (new Carbon($start))->startOfDay();
        }

        //判断是否输入结束日期参数
        if (!$end) {
            $end = Carbon::now()->subDays(1);
        } else {
            //有输入，解析
            $end = (new Carbon($end))->endOfDay();
        }

        $diff = $end->diffInDays($start);
        if ($diff < 1) {
            throw new Exception('输入日期错误');
        }

        while (true) {
            $this->dayRetentionRate($start);

            $start->addDay();
            if (!$start->diffInDays($end)) {
                break;
            }
        }

        return 0;
    }

    //要计算"day"的留存率，需要查询这一天新注册用户数，这些新注册用户，在隔天的登录数。再相除
    protected function dayRetentionRate(Carbon $day)
    {
        $start_time = $day->startOfDay()->getTimestamp();
        $end_time = $day->endOfDay()->getTimestamp();

        //获取"今日"新注册用户id
        $reg = UserModel::find()
            ->select('id')
            ->where(['>=', 'createdAt', $start_time])
            ->andWhere(['<=', 'createdAt', $end_time])
            ->column();

        $time = $day->timestamp;
        $count = count($reg);
        if ($count == 0) {
            //新注册数等于零，留存率直接为0%
            $sql = <<<SQL
INSERT INTO `t_user_day_retention_rate` ( `day`, `rate`, `created_at`, `updated_at` )
VALUES
	( '{$day->toDateString()}', 0, {$time}, {$time} ) 
	ON DUPLICATE KEY UPDATE rate = 0,
	updated_at = {$time}
SQL;
            Yii::$app->db->createCommand($sql)
                ->execute();
            return 0;
        }

        //user、login_log连表查询，
        //筛选条件：$start_time<=user.createdAt<=$end_time
        // and $t_start_time<=login_log.loginTime<=$t_end_time
        $tomorrow = $day->copy()->addDay();
        $t_start_time = $tomorrow->startOfDay()->getTimestamp();
        $t_end_time = $tomorrow->endOfDay()->getTimestamp();

        $sql = <<<SQL
SELECT
	COUNT( DISTINCT l.userId ) as counts
FROM
	t_user_login_log AS l
	INNER JOIN `t_user` AS u ON l.userId = u.id 
WHERE
	l.loginTime >= {$t_start_time} AND l.loginTime <= {$t_end_time}
	AND u.createdAt >= {$start_time} AND u.createdAt <= {$end_time}
SQL;
        $login_count = Yii::$app->db->createCommand($sql)->queryScalar();
        $rate = bcdiv($login_count * 100, $count, 2);

        $sql = <<<SQL
INSERT INTO `t_user_day_retention_rate` ( `day`, `rate`, `created_at`, `updated_at` )
VALUES
	( '{$day->toDateString()}', {$rate}, {$time}, {$time} ) 
	ON DUPLICATE KEY UPDATE rate = {$rate},
	updated_at = {$time}
SQL;
        Yii::$app->db->createCommand($sql)
            ->execute();
        return 0;
    }

}