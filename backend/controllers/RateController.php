<?php


namespace backend\controllers;


use backend\models\DayRateModel;
use Carbon\Carbon;

class RateController extends BaseController
{
    /**
     * 获取用户日留存数据
     */
    public function actionDayRate()
    {
        $data = $this->getPost([
            'start' => Carbon::today()->subDays(7)->toDateString(),
            'end' => Carbon::today()->toDateString(),
        ]);

        $validate = $this->validateData($data, [
            [['start', 'end'], 'date', 'format' => 'php:Y-m-d'],
        ]);

        $results = DayRateModel::find()
            ->where(['>=', 'day', $validate->start])
            ->andWhere(['<=', 'day', $validate->end])
            ->asArray()
            ->all();

        return $this->success('success', ['rates' => $results]);
    }
}