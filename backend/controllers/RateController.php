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

//        $validate = $this->validateData($data, [
//            [['start', 'end'], 'date'],
//        ]);

        $results = DayRateModel::find()
            ->where(['>=', 'day', $data['start']])
            ->andWhere(['<=', 'day', $data['end']])
            ->asArray()
            ->all();

        return $this->success('success', ['rates' => $results]);
    }
}