<?php
namespace app\modules\api\controllers;
use app\modules\liberators\models\PeopleInformation;
class PeopleController extends CommonController
{
    public $defaultAction = 'list';
    public function init() {
        parent::init();
    }
    public function actionList(){
       // $tid  = \Yii::$app->request->get('tid',null);

        $peopleModel   = new PeopleInformation;
        $peopleInfo    = $peopleModel->getInfoById(true); //获取默认人物信息
        $peopleInfoImg = $peopleModel->getFields('portraits,hid AS id'); //获取人物头像
        $peopleInfoImg = $this->orderInfo($peopleInfoImg, $peopleInfo['hid']);
        $data = [
            'peopleInfo'=>$peopleInfo,
            'peopleInfoImg'=>$peopleInfoImg
        ];
        return $this->result(0,$data,'ok');
    }


    public function actionInfo(){
        $id          = \Yii::$app->request->get('id',null);
        $id          = $id ? $id : true;

        $peopleModel = new PeopleInformation;
        $peopleInfo  = $peopleModel->getInfoById($id); //获取默认人物信息

        return $this->result(0,$peopleInfo,'ok');
    }


    /**
     * 随机获取信息
     */
    private function orderInfo($data, $id)
    {
        $first = array();
        foreach ($data as $k => $v) {
            if ($v['id'] == $id) {
                $first = $data[$k];
                unset($data[$k]);
            }
        }
        array_unshift($data, $first);
        return $data;
    }
}
