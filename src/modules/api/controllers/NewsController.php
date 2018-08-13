<?php

namespace app\modules\api\controllers;
use Yii;
use app\helpers\myhelper;
use \app\modules\home\models\News_Multi_Language;
class NewsController extends CommonController
{
    public $defaultAction = 'list';
    public function init() {
        parent::init();
    }

    public function actionList(){
        $fid  = \Yii::$app->request->get('fid',null);
        $page = \Yii::$app->request->get('page',1);
        $per_page = \Yii::$app->request->get('per_page',10);
        $lang = \Yii::$app->request->get('lang','en-us');
        $is_hot = \Yii::$app->request->get('is_hot',false);
        $platform = \Yii::$app->request->get('platform',null);

        $article     = new News_Multi_Language;
        $articleList = $article->getArticleList($fid,$per_page,$is_hot,intval($page-1)*$per_page,$lang, $platform);
        $count       = $article->getCount($fid,$is_hot,$lang,$platform);
        if($count['0']['num']<1){
            $articleList = $article->getArticleList($fid,$per_page,$is_hot,intval($page-1)*$per_page,'en-us',$platform);
            $count       = $article->getCount($fid,$is_hot,'en-us',$platform);
        }

        $data = [
          'articleList'=>$articleList,
          'count'=>$count[0]['num']
        ];
        return $this->result(0,$data,'ok');
    }



    public function actionInfo(){
        $pid = \Yii::$app->request->get('id',-1);
        $lang = \Yii::$app->request->get('lang','en-us');
        $article_obj     = new News_Multi_Language;
        $article = $article_obj->getInfoById($pid,$lang);
        if (empty($article)) {
            $article = $article_obj->getInfoById($pid,'en-us');
        }
        return $this->result(0,$article,'ok');
    }

    /**
     * 批量获取
     */
    public function actionBatchFaqList()
    {
        $faq_id              = \YII::$app->request->get("id");
        $keyword             = \YII::$app->request->get("keyword");
        $page['page']        = \YII::$app->request->get('p',1);
        $page['page_count']  = \YII::$app->request->get('pageSize',1);
        $where['s.language'] = \YII::$app->request->get('lang','en-us');
        $where['c.platform'] = \YII::$app->request->get('platform', 20);
        $where['s.display']  = \YII::$app->request->get('display', 0);
        $andWhere = [];

        if (!empty($faq_id)) {
            $where['s.fid'] = $faq_id;
        }

        if (!empty($keyword)) {
            $andWhere = array("LIKE", 's.title', $keyword);
        }

        $list = $this->page($where, $page, $andWhere);
        return $this->result(0,$list,'ok');
    }

    public function actionView()
    {
        $pid      = \YII::$app->request->get('id');
        $language = \YII::$app->request->get('lang','en-us');
        $article_obj     = new News_Multi_Language;

        $data = $article_obj->getFaqById($pid, $language);

        return $this->result(0, $data,'ok');
    }

    public function page($where = array(), $page = array(),$andWhere=[])
    {
        $simpleArticleModel = new News_Multi_Language;
        $list = $simpleArticleModel::getlist_($where, 's.create_time DESC',($page['page']-1)*$page['page_count'],$page['page_count'],$andWhere);
        if(empty($list)){//DEFAULT EN-US
            $where['s.language'] = 'en-us';
            $list = $simpleArticleModel::getlist_($where, 's.create_time DESC',($page['page']-1)*$page['page_count'],$page['page_count'],$andWhere);
        }
        return $simpleArticleModel->artFormatting($list);
    }

}
