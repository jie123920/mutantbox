<?php
namespace app\modules\api\controllers;
use app\modules\liberators\models\Video;

class VideoController extends CommonController
{
    public $defaultAction = 'list';
    public function init() {
        parent::init();
    }
    public function actionList(){
        $platform = \Yii::$app->request->get('platform',null);
        $videoModel = new Video;
        $videoList  = $videoModel->getVideoList($platform);
        return $this->result(0,$videoList,'ok');
    }

}
