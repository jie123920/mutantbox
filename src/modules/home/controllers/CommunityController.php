<?php
namespace app\modules\home\controllers;
class CommunityController extends CommonController {
    public $defaultAction = 'index';
    public function init()
    {
        parent::init();
    }
    public function actionIndex() {
        $this->isLogin();
        $this->view->params['meta_title'] = \YII::t('common','TitOfficialLiberatorsGamesCommunity');
        $this->view->params['keyword'] = "OFFICIAL MUTANTBOX Games Community,MUTANTBOX Forums,Mutantbox";
        $this->view->params['description'] = "Join the discussion at MUTANTBOX' official forum. Have questions to discuss with other players? Click here to enter the MUTANTBOX community.";
        return $this->render('index.html', [
        ]);

    }
    
}