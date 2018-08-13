<?php

namespace app\modules\home\controllers;
use app\modules\home\models\News_Multi_Language;
use app\modules\home\models\ArticleCategory;
class FaqController extends CommonController
{
    public function init()
    {
        parent::init();
        $this->view->params['meta_title']  = \YII::t('common','TitLiberatorsOnline');
        $this->view->params['keyword']  = "liberators online,liberators play now,play liberators free,mutantbox";
        $this->view->params['description'] = "Have a question about play liberators free? Want to suggest an idea about playing liberators ? Submit your query and contact the mutantbox.com support team. ";
    }

    public function actionDetails()
    {

        $where['pid']      = \YII::$app->request->get('id');
        $where['language'] = LANG_SET;
        $article = (new \yii\db\Query())
            ->select('*')
            ->from(News_Multi_Language::tableName())
            ->where($where)
            ->one();
        if (empty($article)) {
            $where['language'] = 'en-us';
            $article = (new \yii\db\Query())
                ->select('*')
                ->from(News_Multi_Language::tableName())
                ->where($where)
                ->one();
            if (empty($article)) {
                return $this->redirect(['/404']);
            }
        }
        //所有分类
        $ArticleCategory = new ArticleCategory;
        $cat_list = (new \yii\db\Query())
            ->select('*')
            ->from($ArticleCategory::tableName())
            ->all();

        $arrPids = $this->getParents($cat_list, $article['fid']);
        return $this->render('details.html', [
            'article'=>$article,
            'arrPids'=>$arrPids,
        ]);
    }

    public function actionFaqlist()
    {
        $faq_id              = \YII::$app->request->get("id");
        $keyword             = \YII::$app->request->get("keyword");
        $page['page']        = \YII::$app->request->get('p');
        $page['page_count']  = 4;
        $where['s.language'] = LANG_SET;
        if (!empty($faq_id) || !empty($keyword)) {
            if (!empty($faq_id)) {
                $where['s.fid'] = $faq_id;
            }
            $andWhere = [];
            if (!empty($keyword)) {
                $andWhere = array("LIKE",'s.title', $keyword);
            }
            if (\YII::$app->request->isAjax) {
                $list = $this->page($where, $page, "ajax",$andWhere);
                \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return $list;
            } else {
                $list = $this->page($where, $page,'',$andWhere);
                return $this->render('faqlist.html', [
                    'article_list'=>$list,
                ]);
            }
        } else {
            return $this->redirect(['/404']);
        }
    }

    public function page($where = array(), $page = array(), $type = "",$andWhere=[])
    {
        $simpleArticleModel = new News_Multi_Language;
        $list = $simpleArticleModel::getlist($where, 's.create_time DESC',($page['page']-1)*$page['page_count'],$page['page_count'],$andWhere);
        if(empty($list)){//DEFAULT EN-US
            $where['s.language'] = 'en-us';
            $list = $simpleArticleModel::getlist($where, 's.create_time DESC',($page['page']-1)*$page['page_count'],$page['page_count'],$andWhere);
        }
        if ($type == "ajax") {
            $list = $simpleArticleModel->articleAjax($list);
        } else {
            $list = $simpleArticleModel->artFormatting($list);
        }
        return $list;
    }

}
