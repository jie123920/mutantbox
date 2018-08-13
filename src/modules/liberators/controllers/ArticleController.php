<?php
namespace app\modules\liberators\controllers;
use \app\modules\home\controllers\CommonController;
use \app\modules\home\models\News_Multi_Language;
use yii\data\Pagination;
class ArticleController extends CommonController
{
    public function init()
    {
        parent::init();
    }

    public function actionIndex()
    {
        $this->view->params['meta_title'] = \yii::t('common', 'TitLiberatorsReview');
        $this->view->params['keyword'] = "liberators review,liberators events,wargames,ww2 games,mutantbox";
        $this->view->params['description'] = "Read all latest news about liberators from mutantbox.com.  All liberators review and liberators events, gaming industry news and more.More details click here! ";

        $fid         = \Yii::$app->request->get('fid',null);
        $article     = new News_Multi_Language;
        $articleList = $article->getArticleList($fid,10,false,intval(\Yii::$app->request->get('page',1)-1)*10, LANG_SET, 4);
        $count       = $article->getCount($fid,false, LANG_SET, 4);
        if($count['0']['num']<1){
            $articleList = $article->getArticleList($fid,10,false,intval(\Yii::$app->request->get('page',1)-1)*10,'en-us', 4);
            $count       = $article->getCount($fid,false,'en-us', 4);
        }
        $pages       = new Pagination(['totalCount' =>$count[0]['num'], 'pageSize' => 10,'route'=>'/news']);
        return $this->render('newslist.html', [
            'articlerList'=>$articleList,
            'page'=>$pages,
        ]);
    }

    public function actionArticle()
    {
        $pid = \Yii::$app->request->get('id',-1);
        $article_obj     = new News_Multi_Language;
        $article = $article_obj->getInfoById($pid);
        if (empty($article)) {
            $article = $article_obj->getInfoById($pid,'en-us');
            if (empty($article)) {
                return $this->redirect(['/404']);
            }
        }
        $this->view->params['meta_title'] = $article['title'] . \yii::t('common', 'TitMutantbox');
        $this->view->params['keyword'] = $article['title'];
        $this->view->params['description'] = mb_substr(strip_tags(htmlspecialchars_decode($article['message'])), 0, 160, 'utf8');
        return $this->render('newsdetail.html', [
            'article'=>$article,
        ]);
    }

    public function actionGuide()
    {
        $this->view->params['meta_title'] = \yii::t('common', 'TitLiberatorsReview');
        $this->view->params['keyword'] = "liberators review,liberators events,wargames,ww2 games,mutantbox";
        $this->view->params['description'] = "Read all latest news about liberators from mutantbox.com.  All liberators review and liberators events, gaming industry news and more.More details click here! ";

        $article     = new News_Multi_Language;
        $articleList = $article->getArticleList('12,13,22',10,false,intval(\Yii::$app->request->get('page',1)-1)*10, LANG_SET, 4);
        $count       = $article->getCount('12,13,22',false);
        if($count<1){//为空的时候 默认英语
            $articleList = $article->getArticleList('12,13,22',10,false,intval(\Yii::$app->request->get('page',1)-1)*10,'en-us', 4);
            $count       = $article->getCount('12,13,22',false,'en-us');
        }
        $pages       = new Pagination(['totalCount' =>$count, 'pageSize' => 10]);
        return $this->render('newslist.html', [
            'articlerList'=>$articleList,
            'page'=>$pages,
        ]);
    }

}
