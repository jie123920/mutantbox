<?php
namespace app\modules\home\controllers;
use app\helpers\myhelper;
use app\modules\home\models\ArticleCategory;
use app\modules\home\models\Forum;
use app\modules\home\models\Invest;
use app\modules\home\models\ReplyForum;
use app\modules\home\models\News_Multi_Language;
use app\Library\gameapi\Play;
use app\Library\gameapi\GameApi;
class SupportController extends CommonController
{
    public function init()
    {
        parent::init();
        $this->view->params['meta_title']  = \YII::t('common','TitLiberatorsOnline');
        $this->view->params['keyword']     = "liberators online,liberators play now,play liberators free,mutantbox";
        $this->view->params['description'] = "Have a question about play liberators free? Want to suggest an idea about playing liberators ? Submit your query and contact the mutantbox.com support team. ";
    }

    public function actionSearchrelated()
    {
        if (\YII::$app->request->isAjax) {
            $keyword         = \YII::$app->request->post("keyword");
            $article_id      = \YII::$app->request->post("id", 9);
            $ArticleCategory = new ArticleCategory;
            $faq_id          = $ArticleCategory->getCat($article_id, "FAQ");
            $search_str      = $ArticleCategory->getSearch($faq_id, $keyword);
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $search_str;
        }
    }

    public function actionIndex()
    {
        // $article_id      = \YII::$app->request->post("id", 9);
        $game_id         = \YII::$app->request->get("game_id", 4);

        $ArticleCategory = new ArticleCategory;

        // $faq_id   = $ArticleCategory->getCat($article_id, "FAQ");
        $faq_id   = $ArticleCategory->getCat($game_id, "FAQ");
        $faq_list = $ArticleCategory->getCatList($faq_id['id']);
        foreach ($faq_list as &$item) {
            switch ($item['name']) {
                case 'Payment':
                    $item['name'] = \YII::t('common','SupportPayment');
                    break;

                case 'Game':
                    $item['name'] = \YII::t('common','SupportGame');
                    break;

                case 'Account':
                    $item['name'] = \YII::t('common','SupportAccount');
                    break;
            }
        }
        return $this->render('index.html', [
            'faq_list'=>$faq_list,
            'game_list'=>$this->game_list,
            'game_id' => $game_id
        ]);
    }

    public function actionMoreticket()
    {
        if (\YII::$app->request->isAjax) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $page         = \YII::$app->request->post("page");
            $perpage      = 10; //每次显示条数
            $where['uid'] = $this->user_info['id'];
            $ticket_list = (new \yii\db\Query())
                ->select('*')
                ->from('ww2_forum')
                ->where($where)
                ->orderBy('add_time DESC')
                ->limit($perpage)
                ->offset(($page - 1) * $perpage)
                ->all();
            $str          = '';
            if (!empty($ticket_list)) {
                foreach ($ticket_list as $k => &$v) {
                    $v['source_content'] = strip_tags(htmlspecialchars_decode($v['descrption']));
                    $v['time']           = date("H:i:s", $v['add_time']);
                    $v['add_time']       = date('Y m d', $v['add_time']);
                    $v['content']        = myhelper::msubstr($v['source_content'], 0, 40, "utf-8");
                    if (in_array($v['status'], [ReplyForum::STATUS_NEW, ReplyForum::STATUS_NEW_REPLY])) {
                        $v['status'] = 'Pending';
                    } else if (in_array($v['status'], [ReplyForum::STATUS_IN_Progress, ReplyForum::STATUS_REPLIED])) {
                        $v['status'] = 'Replied';
                    } else if (in_array($v['status'], [ReplyForum::STATUS_CLOSED, ReplyForum::STATUS_DUPLICATED])) {
                        $v['status'] = 'Closed';
                    }
                    $url                 = '/article/article?id='. $v['id'];
                    $str .= '<tr onclick="window.open(' . $url . ');" class="tr_item">'
                        . '<td class="item item01">' . $v['forum_id'] . '</td>'
                        . '<td class="item item02">' . $v['subject'] . '</td>'
                        . '<td class="item item03">' . $v['content'] . '</td>'
                        . '<td class="item item04">' . $v['status'] . '</td>'
                        . '<td class="item item05">' . $v['add_time'] . '<br>' . $v['time'] . '</td>'
                        . '</tr>';
                }
            }
            return array('ap_str' => $str);
        }

    }

    public function actionTicketlist()
    {
        if(!$this->is_login){
            $this->redirect(['/']);\YII::$app->end();
        }
        $where['uid'] = $this->user_info['id'];
        $where['game_id'] = 4;
        $ticket_list = (new \yii\db\Query())
            ->select('*')
            ->from('ww2_forum')
            ->where($where)
            ->orderBy('add_time DESC')
            ->limit(10)
            ->offset(0)
            ->all();
        if (!empty($ticket_list)) {
            foreach ($ticket_list as $k => &$v) {
                $v['source_content'] = strip_tags(htmlspecialchars_decode($v['descrption']));
                $v['time']           = date("H:i:s", $v['add_time']);
                $v['add_time']       = date('Y m d', $v['add_time']);
                $v['content']        = myhelper::msubstr($v['source_content'], 0, 40, "utf-8");
                //$v['status']         = $v['status'] == 0 ? '<b>Pending</b>' : 'Replied';
                if (in_array($v['status'], [ReplyForum::STATUS_NEW, ReplyForum::STATUS_NEW_REPLY])) {
                    $v['status'] = '<b>Pending</b>';
                } else if (in_array($v['status'], [ReplyForum::STATUS_IN_Progress, ReplyForum::STATUS_REPLIED])) {
                    $v['status'] = '<b>Replied</b>';
                } else if (in_array($v['status'], [ReplyForum::STATUS_CLOSED, ReplyForum::STATUS_DUPLICATED])) {
                    $v['status'] = '<b>Closed</b>';
                }
            }
        }
        return $this->render('/support/ticketList.html', [
            'ticket_list'=>$ticket_list,
            'game_list'=>$this->game_list,
        ]);
    }

    public function actionTicketinfo()
    {
        if (!($this->is_login)) {
            return $this->redirect(['/404']);
        }
        $id                = \YII::$app->request->get("id");
        $where['forum_id'] = $id;
        $ReplyForum        = new ReplyForum;
        $where['uid']      = $this->user_info['id'];
        $count = (new \yii\db\Query())
            ->select('*')
            ->from(ReplyForum::tableName())
            ->where($where)
            ->count();
        //修改已读
        $ReplyForum->updateAll(['status'=>1],$where);

        if (!empty($id) && !empty($count)) {
            $where['admin_id'] = 0;
            $reply_list = (new \yii\db\Query())
                ->select('*')
                ->from('ww2_reply_forum')
                ->where($where)
                ->all();

            foreach ($reply_list as $key => $value) {
                $reply_list[$key]['add_time'] = date('Y m d, H:i:s', $value['add_time']);
                $reply_list[$key]['content']  = htmlspecialchars_decode($value['content']);
                $reply_list[$key]['username'] = $this->user_info['username'];
                $where                        = array();
                $where['id']                  = $value['uid'];

                $where              = array();
                $where['parent_id'] = $value['id'];
                $andwhere  = array("<>","admin_id", 0);
                $reply_arr = (new \yii\db\Query())
                    ->select('*')
                    ->from('ww2_reply_forum')
                    ->where($where)
                    ->andWhere($andwhere)
                    ->all();

                foreach ($reply_arr as $reply_key => $reply_value) {
                    $where                             = array();
                    $where['id']                       = $reply_value['admin_id'];
                    $admin = (new \yii\db\Query())
                        ->select('username')
                        ->from('ww2_admin')
                        ->where($where)
                        ->one();
                    $reply_arr[$reply_key]['username'] = $admin['username'];
                    $reply_arr[$reply_key]['add_time'] = date('Y m d, H:i:s', $reply_value['add_time']);
                    $reply_arr[$reply_key]['content']  = htmlspecialchars_decode($reply_value['content']);
                }
                $reply_list[$key]['reply'] = $reply_arr;
            }
            return $this->render('/support/ticketInfo.html', [
                'reply_list'=>$reply_list,
                'game_list'=>$this->game_list,
            ]);

        } else {
            return $this->redirect(['/404']);
        }
    }

    public function actionSubreply()
    {
        if (!($this->is_login)) {
            return $this->redirect(['/404']);
        }
        if (\YII::$app->request->isAjax) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $ReplyForum = new ReplyForum;
            $Forum = new Forum;
            $fid        = \YII::$app->request->post("forum_id");
            $ReplyForum->isNewRecord = true;
            $_POST['add_time'] = time();
            $_POST['uid'] = $this->user_info['id'];
            $ReplyForum->setAttributes($_POST);
            if ($ReplyForum->save($_POST)) {
                //更新时间
                $Forum->updateAll(array('add_time' => time(), 'status' => $ReplyForum::STATUS_NEW_REPLY),['id'=>$fid]);
                $result_data['error'] = 0;
                $result_data['msg']   = \YII::t("common","Thank you, your ticket has been sent") . $fid;
                return $result_data;
            } else {
                $result_data['error'] = 1;
                $result_data['msg']   = 'error';
                return $result_data;
            }
        }
    }

    public function actionSubsolved()
    {
        if (!($this->is_login)) {
            return $this->redirect(['/404']);
        }
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $id        = \YII::$app->request->post("id");
        $is_solved = \YII::$app->request->post("is_solved");

        $score        = \YII::$app->request->post("score");
        $where['id']  = $id;
        $ReplyForum   = new ReplyForum;
        $Forum   = new Forum;
        $where['uid'] = $this->user_info['id'];
        $count = (new \yii\db\Query())
            ->select('id')
            ->from('ww2_reply_forum')
            ->where($where)
            ->count();
        if (!empty($id) && !empty($count)) {
            $where['id'] = $id;
            $data['is_solved'] = $is_solved;
            $data['score']     = $score;
            $data['status']    = 1;
            if ($ReplyForum->updateAll($data,$where)) {
                //更改forum表,处理后台的问题的状态是否处理
                if ($is_solved == 1) {
                    $r = (new \yii\db\Query())
                        ->select('forum_id')
                        ->from('ww2_reply_forum')
                        ->where($where)
                        ->one();
                    $where['id']    = $r['forum_id'];
                    $data['status'] = $ReplyForum::STATUS_NEW_REPLY;
                    $Forum->updateAll($data,$where);
                }

                $result_data['error'] = 0;
                $result_data['msg']   = "Submitted successfully.";
                return $result_data;
            } else {
                $result_data['error'] = 1;
                $result_data['msg']   = "Failure to submit.";
                return $result_data;
            }
        } else {
            $result_data['error'] = 2;
            $result_data['msg']   = "Failure to submit.";
            return $result_data;
        }
    }

    public function actionTicketcheck()
    {
        if (\YII::$app->request->isAjax) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $result_data = [];
            if($this->is_login){
                $result_data['error'] = 0;
            } else {
                $result_data['error'] = 1;
            }
            return $result_data;
        }
    }

    public function actionGetusername()
    {
        $serverId  = \Yii::$app->request->post('server_id');
        $game_id  = \Yii::$app->request->post('game_id', 4);
        $GameApi   = new GameApi();
        $serverApi = new Play();
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $returnArr = array(
            'code' => 1,
            'msg'  => \YII::t('common','characterServer'),
        );

        // 获得用户uid和plateform
        $userInfo = $this->user_info;
        if (!$userInfo) {
            if (\Yii::$app->request->post('debug') == 1) {
                $returnArr['debug'] = 'user not founded';
            }
            return $returnArr;
        }

        //4librator   9 battlespace
        if ($game_id == 4) {
            $params = array(
            'oid' => array("{$userInfo['id']}"));
        } elseif($game_id == 9) {
            $params['oid'] = $userInfo['id'];
            $params['data'] = $params;
        }
    
        $allOpenedServers = $serverApi->getOpenedServerList($game_id);
        $userGameData     = array();

        if (isset($allOpenedServers[$serverId])) {
            $userGameData = $GameApi->getUserInfo_new(array("{$allOpenedServers[$serverId]['server_url']}:80" => $params), $game_id);
        }
        
        if ($game_id == 4) {
            $userGameData = current($userGameData);
            if ($userGameData) {
                $returnArr['code'] = 0;
                $returnArr['data'] = $userGameData['name'];
                $returnArr['oid'] = $userGameData['uid'];
                $returnArr['msg'] = 'successfully';
            }
        } elseif($game_id == 9) {
            if (isset($userGameData['base'])) {
                $returnArr['code'] = 0;
                $returnArr['data'] = $userGameData['base']['name'];
                $returnArr['oid'] = $userGameData['base']['uid'];
                $returnArr['msg'] = 'successfully';
            }
        }

        
        return $returnArr;
    }

    public function actionTicket()
    {
        if(!$this->is_login){
            return $this->isLogin();
        }
        $game_id = \Yii::$app->request->get('game_id', 4);
        if (\YII::$app->request->isAjax) {
            //防止重复提交START
            if(\Yii::$app->cache->get('web_'.__SELF__.'_uid_'.$this->user_info['id'])){
                return false;
            }
            \Yii::$app->cache->set('web_'.__SELF__.'_uid_'.$this->user_info['id'], 1, 5);
            //防止重复提交END
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $data = \Yii::$app->request->post();
            $data['uid'] = $this->user_info['id'];
            $Forum = new Forum();
            unset($data['client']);
            //$Forum->load($data);
            if(empty($data['game_name'])){
                $data['game_name'] = 'NULL';//数据库 不能为空的
            }
            $data['add_time'] = time();
            $data['forum_id'] = substr(strtoupper(md5(uniqid(mt_rand(), true))),mt_rand(0, 20),12);
            $data['clientinfo'] = 'PC';
            $data['language'] = LANG_SET;
            $Forum->isNewRecord = true;
            $Forum->setAttributes($data);

            $transaction = \Yii::$app->db->beginTransaction();
            try {
                if (!$Forum->save($data)) {
                    return $this->result(1,[],current($Forum->getErrors()));
                }
                if ($Forum->primaryKey) {
                    $ReplyForum = new ReplyForum();
                    $data2['forum_id'] = $Forum->primaryKey;
                    $data2['content'] = $data['descrption'];
                    $data2['parent_id'] = 0;
                    $data2['add_time'] = time();
                    $data2['score'] = 0;
                    $data2['uid'] = $data['uid'];
                    $data2['admin_id'] = 0;
                    $ReplyForum->isNewRecord = true;
                    $ReplyForum->setAttributes($data2);
                    $ReplyForum->save($data2);
                }

                $transaction->commit();

                $result_data['error'] = 0;
                $result_data['msg']   = \YII::t('common','Thank you, your ticket has been sent');
                return $result_data;

            } catch (Exception $e) {

                $transaction->rollBack();

                $result_data['error'] = 1;
                $result_data['msg']   = current($Forum->getErrors());
                return $result_data;
            }
        }


        $topic_list = (new \yii\db\Query())
            ->select('*, pid AS id')
            ->from(News_Multi_Language::tableName())
            ->where(array(
                'is_hot'   => 1,
                'language' => LANG_SET,
                'tid' => 9,
            ))
            ->orderBy('create_time DESC')
            ->limit(10)
            ->all();

        $topic_List = (new \yii\db\Query())
            ->select('*')
            ->from('ww2_topic')
            ->orderBy('sort ASC')
            ->all();

        $last_enter_server = 0;
        $userInfo  = $this->is_login ? $this->user_info : [];
        $playApi = new Play();
        $MyServiceLists = $playApi->getEnteredServerList($game_id, $userInfo['id'], '', 1);
        if($MyServiceLists && is_array($MyServiceLists)){
            foreach ($MyServiceLists as $item) {
                $last_enter_server = $item['server_id'];
                break;
            }
        }
        return $this->render('ticket.html', [
            'allServerLists'=>$this->getOpenedServers($game_id),
            'topic_list'=>$topic_list,
            'topic_List'=>$topic_List,
            'game_list'=>$this->game_list,
            'last_enter_server'=>$last_enter_server,
            'game_id' => $game_id,
        ]);

    }

    /**
     * 获取开放的游戏列表
     *
     * @access public
     * @return void
     */
    protected function getOpenedServers($game_id = 4)
    {
        // 获取游戏列表对应的服务器.此处不支持多游戏.直接获取liberators的服务器
        $serverApi      = new Play();
        $allServerLists = $serverApi->getOpenedServerList($game_id);
        if($allServerLists){
            foreach ($allServerLists as $key => $item) {
                $sort_field[$item['server_id']] = (int) substr($item['server_id'], 0);
            }
            array_multisort($sort_field, $allServerLists);

            $return = [];
            foreach ($allServerLists as $key => $item) {
                $return[$item['server_id']] = $item;
            }
            return $return;
        }
        return false;
    }



    public function actionLiberatorsvip()
    {
        if (!($this->is_login)) {
            $this->layout = '@module/views/'.GULP.'/public/main.html';
            $this->view->params['login_show']= 1;
            return $this->render('/public/404.html', [
            ]);
            \YII::$app->end();
        }
        if (\YII::$app->request->isAjax) {
            //防止重复提交START
            if(\Yii::$app->cache->get('web_'.__SELF__.'_uid_'.$this->user_info['id'])){
                return false;
            }
            \Yii::$app->cache->set('web_'.__SELF__.'_uid_'.$this->user_info['id'], 1, 5);
            //防止重复提交END
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $data = \Yii::$app->request->post();
            $Invest = new Invest();
            $Invest->load($data);
            $data['add_time'] = time();
            $data['platform'] = 'PC';
            $data['language'] = LANG_SET;
            $data['uid'] = $this->user_info['id'];
            $Invest->setAttributes($data);
            $Invest->isNewRecord = true;
            if ($Invest->save($data)) {
                $result_data['error'] = 0;
                $result_data['msg']   = \YII::t('common','Thank you, your ticket has been sent');
                return $result_data;
            } else {
                foreach ($Invest->getFirstErrors() as $k=>$v){
                    if($k == 'email'){
                        $msg = $v;break;
                    }else{
                        $msg = $k.$v;break;
                    }
                }
                $result_data['error'] = 1;
                $result_data['msg']   = $msg;
                return $result_data;
            }
        }


        $playApi = new Play();
        $last_enter_server = 0;
        $userInfo  = $this->is_login ? $this->user_info : [];
        $MyServiceLists = $playApi->getEnteredServerList(4, $userInfo['id'], '', 1);
        if($MyServiceLists && is_array($MyServiceLists)){
            foreach ($MyServiceLists as $item) {
                $last_enter_server = $item['server_id'];
                break;
            }
        }


        $topic_List = (new \yii\db\Query())
            ->select('*')
            ->from('ww2_topic')
            ->orderBy('sort ASC')
            ->all();

        return $this->render('invest.html', [
            'allServerLists'=>$this->getOpenedServers(),
            'topic_List'=>$topic_List,
            'game_list'=>$this->game_list,
            'last_enter_server'=>$last_enter_server
        ]);

    }
}
