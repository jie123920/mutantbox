<?php
namespace app\modules\sl\controllers;
use \app\modules\home\controllers\CommonController;
use \app\modules\home\models\News_Multi_Language;
use \app\modules\liberators\models\Video;
use \app\modules\sl\models\Screenshot;

class IndexController extends CommonController
{
    public $layout = '@module/views/'.GULP.'/public/main.html';
    public function init()
    {
        parent::init();
        $this->view->params['meta_title'] = 'Survivor Legacy Official Site-Survivor Legacy Download-Survivor Legacy gameplay | Mutantbox';
        $this->view->params['keyword'] = "survivor legacy login,survivor legacy gameplay,mutantbox";
        $this->view->params['description'] = "Login and download Survivor Legacy for free! From Mutantbox.";
    }

    public function actionIndex()
    {
        $videoModel = new Video;
        $videoList  = $videoModel->getVideoList(26);
        $firstvideo = [];
        if($videoList){
            foreach ($videoList as $v){
                $firstvideo = $v;
                break;
            }
        }

        $Screenshot = new Screenshot;
        $sList  = $Screenshot->getList();

        $articleModel = new News_Multi_Language;
        $newsList  = $articleModel->getArticleList(NULL,4,true,0,LANG_SET,26);
        if(empty($newsList)){
            $newsList  = $articleModel->getArticleList(NULL,4,true,0,'en-us',26);
        }
        $userInfo  = $this->is_login ? $this->user_info : [];
        return $this->render('index.html', [
            'isLogined'       => $this->is_login,
            'firstvideo'     =>$firstvideo,
            'sList'=>$sList,
            'newsList'=>$newsList
        ]);
    }

}
