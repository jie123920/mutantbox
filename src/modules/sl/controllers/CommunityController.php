<?php
namespace app\modules\sl\controllers;
use \app\modules\home\controllers\CommonController;
class CommunityController extends CommonController {
    public $layout = '@module/views/'.GULP.'/public/main.html';
    public function init()
    {
        parent::init();
    }
    public function actionIndex() {
        $this->view->params['meta_title'] = 'Survivor Legacy Official Site-Survivor Legacy Download-Survivor Legacy gameplay | Mutantbox';
        $this->view->params['keyword'] = "survivor legacy login,survivor legacy gameplay,mutantbox";
        $this->view->params['description'] = "Login and download Survivor Legacy for free! From Mutantbox.";
        $this->isLogin();
        $furl = \yii::$app->params['MY_URL']['SL_FORUM'];
        if( $this->is_login ){
            redirectForum($furl);
        }
        return $this->render('index.html', []);
    }
    
}