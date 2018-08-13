<?php
namespace app\modules\api\controllers;
use yii\web\Controller;
use Yii;
class CommonController extends Controller {
    public $callback = null;
    public function init(){
        parent::init();
        $this->callback = Yii::$app->request->get('callback',null);
        Yii::$app->response->format = $this->callback ? \yii\web\Response::FORMAT_JSONP : \yii\web\Response::FORMAT_JSON;
        if(Yii::$app->request->get('api_access_key') != '233fb47265250cb7d8356f2089941433'){
            $result =  $this->result(1,[],'Access denied!');
            echo json_encode($result);
            exit;
        }
    }

    public function result($code=0,$data = [],$msg=''){
        if($this->callback){
            return array(
                'callback' => $this->callback,
                'data' => [
                    'code' => $code,
                    'message' => $msg,
                    'data'=>$data
                ]
            );
        }else{
            return array(
                'code' => $code,
                'message' => $msg,
                'data'=>$data
            );
        }
    }
}
