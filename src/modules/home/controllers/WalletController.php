<?php

namespace app\modules\home\controllers;


use app\models\User;
use Ucenter\Ucenter;

class WalletController extends CommonController
{
    //設置支付密碼
    public function actionSetPaypwd()
    {
        $token = \Yii::$app->request->get('token', '');
        //判断token是否合法
        $model = new Ucenter();
        $data = $model->Auth()->verifyToken($token);
        if (!$data) {
            echo "<script>alert('error');</script>";
            return false;
        }
        //判断是否存在支付密码
        $res = $model->User()->paypwdExist($data);
        if($res){
            $this->redirect('/wallet/change-paypwd?token='.$token);
        }

        $this->layout = false;
        return $this->render('/public/ct-setpwd.html', [
        ]);
    }

    //修改支付密碼
    public function actionChangePaypwd()
    {
        $token = \Yii::$app->request->get('token', '');
        $model = new Ucenter();
        $data = $model->Auth()->verifyToken($token);
        if (!$data) {
            echo "<script>alert('error');</script>";
            return false;
        }
        //判断是否存在支付密码
        $res = $model->User()->paypwdExist($data);
        if(!$res){
            $this->redirect('/wallet/set-paypwd?token='.$token);
        }

        $this->layout = false;
        return $this->render('/public/ct-changepwd.html', [
        ]);
    }

    //找回支付密碼
    public function actionGetPaypwd()
    {
        $token = \Yii::$app->request->get('token', '');
        $model = new Ucenter();
        $data = $model->Auth()->verifyToken($token);
        if (!$data) {
            echo "<script>alert('error');</script>";
            return false;
        }
        //判断是否存在支付密码
        $res = $model->User()->paypwdExist($data);
        if(!$res){
            $this->redirect('/wallet/set-paypwd?token='.$token);
        }

        $this->layout = false;
        return $this->render('/public/ct-getpwd.html', [
            'email' => strpos($data['account'],'@') ? $data['account'] : ''
        ]);
    }


}