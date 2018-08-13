<?php

namespace app\modules\api\controllers;
use Yii;
use app\helpers\myhelper;
use \app\modules\home\models\Screenshot;

class ScreenshotController extends CommonController
{
    public $defaultAction = 'list';
    public function init() {
        parent::init();
    }

    //获取轮播图像接口
    public function actionList()
    {
        $tid  = \Yii::$app->request->get('tid');
        $type = \Yii::$app->request->get('type',1);
        $screenshot_model = new Screenshot;
        $list = $screenshot_model->getList($tid, $type);
        return $this->result(0,$list,'ok');
    }

}
