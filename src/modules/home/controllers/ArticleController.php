<?php
namespace app\modules\home\controllers;
use yii\data\Pagination;
use app\modules\home\models\News_Multi_Language;
use app\modules\home\models\ArticleCategory;
class ArticleController extends CommonController
{
    public $defaultAction = 'index';
    public function init()
    {
        parent::init();
        $this->view->params['meta_title']= \YII::t('common','TitFreeArmyGames');
        $this->view->params['keyword']= "liberators review,liberators events,wargames,ww2 games,mutantbox";;
        $this->view->params['description']= "Read all latest news about liberators from mutantbox.com.  All liberators review and liberators events, gaming industry news and more.More details click here! ";
    }

    //新闻列表
    public function actionIndex()
    {
        $map['tid']      = 1;
        $map['language'] = LANG_SET;
        //每页记录
        $page       = \Yii::$app->request->get('p',1);

        $article     = new News_Multi_Language;
        $count       = $article->getCount(NULL,false,LANG_SET,1);
        if($count<1){
            $count       = $article->getCount(NULL,false,'en-us');
        }
        $pages = new Pagination(['totalCount' =>$count[0]['num'], 'pageSize' => 10,'route'=>'/news']);


        if (\Yii::$app->request->isAjax) {
            $list = $this->page($page, "ajax");
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $list;
        } else {
            $list = $this->page($page);
            return $this->render('index.html', [
                'article_list'=>$list,
                'pages'=>$pages
            ]);
        }
    }

    //新闻详细页
    public function actionArticle()
    {
        $map['pid']      = \Yii::$app->request->get('id',-1);
        $map['language'] = LANG_SET;

        $article = (new \yii\db\Query())
            ->select('*')
            ->from(News_Multi_Language::tableName())
            ->where($map)
            ->one();
        if (empty($article)) {
            $map['language'] = 'en-us';
            $article = (new \yii\db\Query())
                ->select('*')
                ->from(News_Multi_Language::tableName())
                ->where($map)
                ->one();
            if (empty($article)) {
                return $this->redirect(['/404']);
            }
        }
        $this->view->params['meta_title']= $article['title'];
        $this->view->params['keyword']= $article['title'];
        $this->view->params['description']= mb_substr(strip_tags($article['remark']), 0, 160, 'utf8');
        //所有分类
        $ArticleCategory = new ArticleCategory;
        $cat_list = (new \yii\db\Query())
            ->select('*')
            ->from($ArticleCategory::tableName())
            ->all();

        $arrPids = $this->getParents($cat_list, $article['fid']);
        return $this->render('article.html', [
            'article'=>$article,
            'arrPids'=>$arrPids,
        ]);
    }

    public function actionTermsofuse(){
        $map['pid']      = 2;
        $map['language'] = 'en-us';

        $article = (new \yii\db\Query())
            ->select('*')
            ->from(News_Multi_Language::tableName())
            ->where($map)
            ->one();
        if (!$article) {
            return $this->redirect(['/404']);
        }
        //所有分类
        $ArticleCategory = new ArticleCategory;
        $cat_list = (new \yii\db\Query())
            ->select('*')
            ->from($ArticleCategory::tableName())
            ->all();

        $arrPids = $this->getParents($cat_list, $article['fid']);

        $this->view->params['meta_title']= $article['title'];
        $this->view->params['keyword']= $article['title'];
        $this->view->params['description']= mb_substr(strip_tags($article['remark']), 0, 160, 'utf8');

        return $this->render('article.html', [
            'article'=>$article,
            'arrPids'=>$arrPids,
        ]);
    }

    public function actionPrivacypolicy(){
        $this->view->params['meta_title']= 'PRIVACY POLICY';
        $this->view->params['keyword']= 'PRIVACY POLICY';
        $this->view->params['description']= 'The effective date of this Privacy Policy (the "Policy") is July 31st, 2015.';

        return $this->render('privacypolicy.html', [
        ]);
    }


    public function page($page, $type = "")
    {
        $article = new News_Multi_Language;
        $list = $article->getArticleList(NULL,10,false,intval($page-1)*10,LANG_SET,1);
        if(empty($list)){
            $list = $article->getArticleList(NULL,10,false,intval($page-1)*10,'en-us',1);
        }
        if ($type == "ajax") {
            $list = News_Multi_Language::articleAjax($list);
        } else {
            $list = News_Multi_Language::artFormatting($list);
        }
        return $list;
    }

}
