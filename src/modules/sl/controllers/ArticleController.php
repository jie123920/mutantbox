<?php
namespace app\modules\sl\controllers;
use \app\modules\home\controllers\CommonController;
use \app\modules\home\models\News_Multi_Language;
use yii\data\Pagination;
class ArticleController extends CommonController
{
    public $layout = '@module/views/'.GULP.'/public/main.html';
    public function init()
    {
        parent::init();
        $this->view->params['meta_title'] = '"Survivor Legacy review - Survivor Legacy events | 
Post-Apocalyptic War Strategy Game | Mutantbox"';
        $this->view->params['keyword'] = "survivor legacy review,survivor legacy events,mutantbox";
        $this->view->params['description'] = "News, reviews, and special events for Survivor Legacy!  The post-apocolyptic strategy game, from Mutantbox.";
    }

    public function actionIndex()
    {
        $fid         = \Yii::$app->request->get('fid',null);
        $article     = new News_Multi_Language;
        if(\Yii::$app->request->isAjax){
            $fid         = \Yii::$app->request->post('fid',null);
            $type         = \Yii::$app->request->post('type',1);
            $articleList = $article->getArticleList($fid,10,false,intval(\Yii::$app->request->post('page',1)-1)*10,LANG_SET,26);
            $count       = $article->getCount($fid,false,LANG_SET,26);
            if($count['0']['num']<1){
                $articleList = $article->getArticleList($fid,10,false,intval(\Yii::$app->request->post('page',1)-1)*10,'en-us',26);
            }
            $str_1 = $str_2 = '';
            foreach ($articleList as $v){
                $url = PROTOCOL.'://'.$_SERVER['HTTP_HOST'].'/news/'.$v['id'].'/'.$v['urltitle'];

                $str_2 .= '<li>
                    <h2><a href="javascript:">'.$v['title'].'</a></h2>
                    <div class="sur-newstime"><time>'.date("y m d",$v['id']).' [GMT]</time><span>'.$v['name'].'</span></div>
                    <div class="sur-contwrap">
                        <div class="sur-newsarticle">
                            <p>'.htmlspecialchars_decode($v['remark']).'</p>
                        </div>
                        <div class="sur-newsshare">
                            <span>'.\YII::t('common','share').':</span>
                            <a href="javascript:void(window.open(\'http://www.facebook.com/sharer.php?u='.$url.'&t='.$v['urltitle'].'\'))"><i class="fa fa-facebook" aria-hidden="true"></i></a>
                            <a href="javascript:void(window.open(\'http://reddit.com/r/gaming/submit?url='.$url.'&title='.$v['urltitle'].'\'))"><i class="fa fa-reddit-alien" aria-hidden="true"></i></a>
                            <a href="javascript:void(window.open(\'https://twitter.com/intent/tweet?url='.$url.'&text='.$v['urltitle'].'\'))"><i class="fa fa-twitter" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </li>';


                $str_1 .=' <li>
                    <h2><a href="/news/'.$v['id'].'/'.$v['urltitle'].'">'.$v['title'].'</a></h2>
                    <div class="sur-newstime"><time>'.date('Y m d', $v['create_time']).' [GMT]</time><span>'.$v['name'].'</span></div>
                    <div class="sur-contwrap">
                        <div class="sur-newsarticle">
                            <p>'.htmlspecialchars_decode($v['remark']).'</p>
                        </div>
                        <div class="sur-newsshare">
                            <span>'.\YII::t('common','share').':</span>
                            <a href="javascript:void(window.open(\'http://www.facebook.com/sharer.php?u='.$url.'&t='.$v['urltitle'].'\'))"><i class="fa fa-facebook" aria-hidden="true"></i></a>
                            <a href="javascript:void(window.open(\'http://reddit.com/r/gaming/submit?url='.$url.'&title='.$v['urltitle'].'\'))"><i class="fa fa-reddit-alien" aria-hidden="true"></i></a>
                            <a href="javascript:void(window.open(\'https://twitter.com/intent/tweet?url='.$url.'&text='.$v['urltitle'].'\'))"><i class="fa fa-twitter" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </li>';
            }

            if($type==1){
                echo $str_1 ;
            }else{
                echo $str_2 ;
            }
            return;
        }


        $this->view->params['meta_title'] = \yii::t('common', 'TitLiberatorsReview');
        $this->view->params['keyword'] = "SURVIVOR LEGACY review,SURVIVOR LEGACY events,wargames,ww2 games,mutantbox";
        $this->view->params['description'] = "Read all latest news about SURVIVOR LEGACY from mutantbox.com.  All SURVIVOR LEGACY review and SURVIVOR LEGACY events, gaming industry news and more.More details click here! ";


        $articleList = $article->getArticleList($fid,10,false,intval(\Yii::$app->request->post('page',1)-1)*10,LANG_SET,26);
        $count       = $article->getCount($fid,false,LANG_SET,26);
        if($count['0']['num']<1){
            $articleList = $article->getArticleList($fid,10,false,intval(\Yii::$app->request->post('page',1)-1)*10,'en-us',26);
            $count       = $article->getCount($fid,false,'en-us',26);
        }
        $pages       = new Pagination(['totalCount' =>$count[0]['num'], 'pageSize' => 10,'route'=>'/news']);
        return $this->render('newslist.html', [
            'articlerList'=>$articleList,
            'page'=>$pages,
            'fid'=>$fid
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
        $articleList = $article->getArticleList('12,13,22',10,false,intval(\Yii::$app->request->get('page',1)-1)*10);
        $count       = $article->getCount('12,13,22',false);
        if($count<1){//为空的时候 默认英语
            $articleList = $article->getArticleList('12,13,22',10,false,intval(\Yii::$app->request->get('page',1)-1)*10,'en-us');
            $count       = $article->getCount('12,13,22',false,'en-us');
        }
        $pages       = new Pagination(['totalCount' =>$count, 'pageSize' => 10]);
        return $this->render('newslist.html', [
            'articlerList'=>$articleList,
            'page'=>$pages,
        ]);
    }

}
