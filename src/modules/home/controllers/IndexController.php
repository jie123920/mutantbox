<?php

namespace app\modules\home\controllers;
use app\helpers\myhelper;
use app\modules\liberators\models\Video;
use \app\modules\home\models\News_Multi_Language;
class IndexController extends CommonController
{
    public $defaultAction = 'index';
    public function init() {
        parent::init();
        $this->view->params['meta_title'] = \yii::t('common','TitLiberators');
        $this->view->params['keyword'] = "liberators login,liberators gameplay,best strategy games,mutantbox";
        $this->view->params['description'] = "Official site about mutantbox liberators.Enter mutantbox.com to find real time war strategy games! Play best free strategy online games with mutantbox !";
    }

    public function actionIndex()
    {
        $code = \YII::$app->request->get('code','');
        $email = \YII::$app->request->get('email','');
        $get_password_code  = false;
        if (!empty($code) && !empty($email)) {
            $verify = myhelper::verify_resetpwd_code($email,$code);
            if ($verify) {
                $get_password_code = true;
                $get_password_js = '<script>showDialog("#reset_pwd")</script>';
                $this->view->params['get_password_js']= $get_password_js;
            } else {
                $get_password_js = '<script>layer_alert("Please request another password recovery email.",1,"/index/index");</script>';
                $this->view->params['get_password_js']= $get_password_js;
            }
        }
        $this->view->params['get_password_code']= $get_password_code;
        $this->view->params['email']= $email;
        $videoModel = new Video;
        $videoList  = $videoModel->getVideoList(1);

        $articleModel = new News_Multi_Language;
        $newsList  = $articleModel->getArticleList(NULL,3,true,0,LANG_SET,1);//TODO  26
        if(empty($newsList)) $articleModel->getArticleList(NULL,3,true,0,'en-us',1);//TODO  26
        return $this->render('index.html', [
            'videoList'=>$videoList,
            'newsList'=>$newsList
        ]);
    }
}
