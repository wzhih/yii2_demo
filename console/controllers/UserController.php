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

    /**
     * 更新时间范围内的新注册人数
     * 更新时间范围区间 [$start, $end)
     * @param string $start
     * @param string $end
     */
    public function actionDayRegister(string $start = '', string $end = '')
    {
        //判断是否输入起始日期参数
        if (!$start) {
            //没有输入，默认取昨天
            $start = Carbon::yesterday();
        } else {
            //有输入，解析
            $start = (new Carbon($start))->startOfDay();
        }

        //判断是否输入结束日期参数
        if (!$end) {
            $end = Carbon::today();
        } else {
            //有输入，解析
            $end = (new Carbon($end))->endOfDay();
        }

        $diff = $end->diffInDays($start);
        if ($diff < 1) {
            throw new Exception('输入日期错误');
        }

        $time = Carbon::now()->getTimestamp();
        while (true) {
            //获取新注册用户数量
            $registerNum = UserModel::find()
                ->select('id')
                ->where(['>=', 'createdAt', $start->startOfDay()->getTimestamp()])
                ->andWhere(['<=', 'createdAt', $start->endOfDay()->getTimestamp()])
                ->count();

            //插入或更新对应日期的记录
            $sql = <<<SQL
INSERT INTO `t_user_day_retention_rate` ( `day`, `register_num`, `login_num`, `created_at`, `updated_at` )
VALUES
	( '{$start->toDateString()}', {$registerNum}, 0, {$time}, {$time} ) 
	ON DUPLICATE KEY UPDATE register_num = {$registerNum}, updated_at = {$time}
SQL;
            Yii::$app->db->createCommand($sql)
                ->execute();

            $start->addDay();
            if (!$start->diffInDays($end)) {
                break;
            }
        }
    }

    /**
     * 运行间隔：
     * 更新"昨日"新注册用户，在上一小时的登录用户数
     * @param string $start
     * @param string $end
     */
    public function actionHourLogin(string $now = '')
    {
        if (empty($now)) {
            $now = Carbon::now();
        } else {
            $now = new Carbon($now);
        }

        //下面统一使用`Carbon::parse($time)`去取对应时间
        $time = $now->getTimestamp();

        //当天开始时间戳
        $dayStart = Carbon::parse($time)->subHour()->startOfDay()->getTimestamp();
        //上一小时结尾时间戳
        $hourEnd = Carbon::parse($time)->subHour()->endOfHour()->getTimestamp();

        //昨天日期
        $yesterday = Carbon::parse($time)->subHour()->subDay();
        //昨天开始时间戳
        $yesterdayStart = Carbon::parse($time)->subHour()->subDay()->startOfDay()->getTimestamp();
        //昨天结束时间戳
        $yesterdayEnd = Carbon::parse($time)->subHour()->subDay()->endOfDay()->getTimestamp();

        //获取"昨日"新注册用户，在"今日"起始到上一小时结尾的登录用户数
        $sql = <<<SQL
SELECT
	COUNT( DISTINCT l.userId ) as counts
FROM
	t_user_login_log AS l
	INNER JOIN `t_user` AS u ON l.userId = u.id 
WHERE
	l.loginTime >= {$dayStart} AND l.loginTime <= {$hourEnd}
	AND u.createdAt >= {$yesterdayStart} AND u.createdAt <= {$yesterdayEnd}
SQL;

        $loginNum = Yii::$app->db->createCommand($sql)->queryScalar();

        //插入或更新对应日期的记录
        $sql = <<<SQL
INSERT INTO `t_user_day_retention_rate` ( `day`, `register_num`, `login_num`, `created_at`, `updated_at` )
VALUES
	( '{$yesterday->toDateString()}', 0, {$loginNum}, {$time}, {$time} ) 
	ON DUPLICATE KEY UPDATE login_num = {$loginNum}, updated_at = {$time}
SQL;

        Yii::$app->db->createCommand($sql)
            ->execute();

        return 0;
    }

    /**
     * 临时脚本
     */
    public function actionTempLogin(string $start)
    {
        $start = (new Carbon($start))->startOfDay();
        while (true) {
            if ($start->isToday()) {
                break;
            }

            $start = $start->addHour();
            $this->actionHourLogin($start);
        }
    }
}