<?php
namespace app\modules\liberators\controllers;
use \app\modules\home\controllers\CommonController;
class CommunityController extends CommonController {
    public function init()
    {
        parent::init();
    }
    public function actionIndex() {
        $this->view->params['meta_title'] = \yii::t('common', 'TitOfficialLiberatorsGamesCommunity');
        $this->view->params['keyword'] = "OFFICIAL Liberators Games Community,Liberators Forums,Mutantbox";
        $this->view->params['description'] = "Join the discussion at Liberators' official forum. Have questions to discuss with other players? Click here to enter the Liberators community.";
        $this->isLogin();
//        $furl = \yii::$app->params['MY_URL']['FORUM'];
//        if( $this->is_login ){
//            redirectForum($furl);
//        }
        return $this->render('index.html', []);
    }
    
}