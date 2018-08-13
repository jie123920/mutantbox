<?php

namespace app\modules\api\controllers;
use Yii;
use app\helpers\myhelper;
use app\modules\home\models\Forum;
use app\modules\home\models\ReplyForum;
use app\modules\home\models\Message;
use app\modules\home\models\ForumSearch;
class TicketController extends CommonController
{
    public $defaultAction = 'getinfo';
    public function init() {
        parent::init();
    }
    public function actionInsert(){
            $data['uid'] = YII::$app->request->get('uid','');
            $data['game_id'] = YII::$app->request->get('game_id',0);
            $data['server_id'] = YII::$app->request->get('server_id',0);
            $data['game_name'] = YII::$app->request->get('game_name','');
            $data['email'] = YII::$app->request->get('email','');
            $data['topic_id'] = YII::$app->request->get('topic_id',0);
            $data['subject'] = YII::$app->request->get('subject','11');
            $data['descrption'] = YII::$app->request->get('descrption','11');
            $data['language'] = YII::$app->request->get('language','');;
            $Forum = new Forum();
            if(empty($data['game_name'])){
                $data['game_name'] = 'NULL';//数据库 不能为空的
            }
            $data['add_time'] = time();
            $data['server_id'] = 0;
            $data['forum_id'] = substr(strtoupper(md5(uniqid(mt_rand(), true))),mt_rand(0, 20),12);
            $data['clientinfo'] = 'PC';

            $data['deviceinfo'] = YII::$app->request->get('deviceinfo', '');
            // if ($deviceInfo = YII::$app->request->get('deviceinfo','')) {
            //     $data['deviceinfo'] = $deviceInfo;
            // }
            // 
            $data['forum_ip'] = YII::$app->request->get('forum_ip', 0);

            // if ($forum_ip = YII::$app->request->get('forum_ip',0)) {
            //     $data['forum_ip'] = $forum_ip;
            // }


            $Forum->isNewRecord = true;
            $Forum->setAttributes($data);

            $transaction = \Yii::$app->db->beginTransaction();
            try {
                if ($Forum->save($data) && $Forum->primaryKey) {
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
                }else{
                    $transaction->rollBack();
                    return $this->result(1,[],current($Forum->getErrors()));
                }
                $transaction->commit();
                return $this->result(0,[],'Thank you, your ticket has been sent');
            } catch (Exception $e) {
                $transaction->rollBack();
                return $this->result(1,[],current($Forum->getErrors()));
            }
    }


    public function actionGetCount(){
        $uid = YII::$app->request->get('uid','');
        $game_id = YII::$app->request->get('game_id','');

        $count_where = [];
        $count_where['is_read'] = 0;
        $count_where['uid'] = $uid;
        $count_where['ww2_message.game_id'] = $game_id;
        if ($ticket_count = \Yii::$app->cache->get('web_ticket_count_'.json_encode($count_where))) {

        }else{
            $ticket_count = (new \yii\db\Query())
                ->select("id")
                ->from(Message::tableName())
                ->join('INNER JOIN','ww2_message','ww2_message'.'.id='.Message::tableName().'.message_id')
                ->where($count_where)
                ->count();
            if($ticket_count){
                \Yii::$app->cache->set('web_ticket_count_'.json_encode($count_where), $ticket_count, 600);
            }
        }



        $where = [];
        $where['uid'] = $uid;
        $where['status'] = 0;
        $system_msg_count = (new \yii\db\Query())
            ->select("id")
            ->from('ww2_reply_forum')
            ->where($where)
            ->andWhere(['<>','admin_id',0])
            ->count();

        $message_count = $ticket_count + $system_msg_count;


        $data['message_count']= $message_count;
        $data['ticket_count']= $ticket_count;
        $data['system_msg_count']= $system_msg_count;

        return $this->result(0,$data,'ok');
    }


    public function actionGetMessage(){
        $uid = YII::$app->request->get('uid','');
        $game_id = YII::$app->request->get('game_id','');
        $page = \Yii::$app->request->get('p');
        $page_count = 4;
        $where['uid'] = $uid;
        $where['game_id'] = $game_id;

        $data = Message::messageList($where, 'id DESC',($page-1)*$page_count,$page_count);

        return $this->result(0,$data,'ok');
    }



    public function actionGetTicketList(){
        $uid = YII::$app->request->get('uid','');
        $game_id = YII::$app->request->get('game_id','');


        $where['uid'] = $uid;
        $where['game_id'] = $game_id;
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
        return $this->result(0,$ticket_list,'ok');
    }



    public function actionGetMoreTicketList(){
        $uid = YII::$app->request->get('uid','');
        $page = YII::$app->request->get('page','');

        $perpage      = 10; //每次显示条数
        $where['uid'] = $uid;
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
        return $this->result(0,$str,'ok');
    }



    public function actionGetTicketInfo()
    {
        $id = \YII::$app->request->get("id");
        $uid = YII::$app->request->get('uid', '');
        $username = YII::$app->request->get('username', '');

        $where['forum_id'] = $id;
        $where['uid'] = $uid;
        $ReplyForum = new ReplyForum;
        $count = (new \yii\db\Query())
            ->select('*')
            ->from(ReplyForum::tableName())
            ->where($where)
            ->count();
        $ReplyForum->updateAll(['status' => 1], $where);

        if (!empty($id) && !empty($count)) {
            $where['admin_id'] = 0;
            $reply_list = (new \yii\db\Query())
                ->select('*')
                ->from('ww2_reply_forum')
                ->where($where)
                ->all();

            foreach ($reply_list as $key => $value) {
                $reply_list[$key]['add_time'] = date('Y m d, H:i:s', $value['add_time']);
                $reply_list[$key]['content'] = htmlspecialchars_decode($value['content']);
                $reply_list[$key]['username'] = $username;
                $where = array();
                $where['id'] = $value['uid'];

                $where = array();
                $where['parent_id'] = $value['id'];
                $andwhere = array("<>", "admin_id", 0);
                $reply_arr = (new \yii\db\Query())
                    ->select('*')
                    ->from('ww2_reply_forum')
                    ->where($where)
                    ->andWhere($andwhere)
                    ->all();

                foreach ($reply_arr as $reply_key => $reply_value) {
                    $where = array();
                    $where['id'] = $reply_value['admin_id'];
                    $admin = (new \yii\db\Query())
                        ->select('username')
                        ->from('ww2_admin')
                        ->where($where)
                        ->one();
                    $reply_arr[$reply_key]['username'] = $admin['username'];
                    $reply_arr[$reply_key]['add_time'] = date('Y m d, H:i:s', $reply_value['add_time']);
                    $reply_arr[$reply_key]['content'] = htmlspecialchars_decode($reply_value['content']);
                }
                $reply_list[$key]['reply'] = $reply_arr;
            }


            return $this->result(0, $reply_list, 'ok');
        }
    }


    public function actionSubreply(){
        $uid = YII::$app->request->get('uid', '');
        $forum_id = YII::$app->request->get('forum_id', '');
        $content = YII::$app->request->get('content', '');


        $fid        = $forum_id;
        $data['forum_id'] = $forum_id;
        $data['add_time'] = time();
        $data['uid'] = $uid;
        $data['content'] = $content;

        $ReplyForum = new ReplyForum;
        $Forum = new Forum;
        $ReplyForum->isNewRecord = true;
        $ReplyForum->setAttributes($data);
        if ($ReplyForum->save($data)) {
            $Forum->updateAll(array('add_time' => time(), 'status' => $ReplyForum::STATUS_NEW_REPLY),['id'=>$fid]);
            return $this->result(0, [], \YII::t("common","Thank you, your ticket has been sent") . $fid);
        } else {
            return $this->result(1, [], 'failed');
        }
    }

    public function actionSubsolved(){
        $uid        = \YII::$app->request->get("uid");
        $id        = \YII::$app->request->get("id");
        $is_solved = \YII::$app->request->get("is_solved");
        $score        = \YII::$app->request->get("score");

        $where['id']  = $id;
        $where['uid'] = $uid;

        $ReplyForum   = new ReplyForum;
        $Forum   = new Forum;
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
                return $this->result(0, [], 'Submitted successfully.');
            } else {
                return $this->result(1, [], 'Failure to submit.');
            }
        } else {
            return $this->result(1, [], 'Failure to submit.');
        }
    }

    public function actionLiveTicketList(){
        $userID = YII::$app->request->get('uid','');
        $gameID = YII::$app->request->get('game_id','');
        $nowPage = (int) Yii::$app->request->get('page', 1);
        $pageSize = Yii::$app->request->get('pageSize');

        $searchModel  = new ForumSearch();
        $dataProvider = $searchModel->search([
            'ForumSearch' => [
                'uid'     => $userID,
                'game_id' => $gameID,
            ],
        ]);

        $dataProvider->Pagination->pageSize = $pageSize ? $pageSize : 10;
        $models = $dataProvider->getModels();

        $totalPage = ceil($dataProvider->getTotalCount() / $dataProvider->Pagination->pageSize);
        $nextPage  = $nowPage < $totalPage ? $nowPage + 1 : 0;

        $return_data = [
            'nowPage' => $nowPage,
            'nextPage' => $nextPage,
            'models'   => $models,
        ];
        return $this->result(0, $return_data, 'sucess');

    }

    //直播工单详情
    public function actionLiveTicketInfo(){
        $id = YII::$app->request->get('id','');
        $userID = YII::$app->request->get('uid','');
        $gameID = YII::$app->request->get('game_id','');

        $model = Forum::findOne($id);
        // \app\helpers\myhelper::inlog('tiket', 'actionLiveTicketInfo', [$model]);
        if ($model->uid != $userID) {
            throw new \Exception(Yii::t('common', 'You do not have permission to view other people\'s work orders'));
        }
        $return_data = [
            'models'   => $model,
            'replyForum'   => $model->replyForum,
        ];
        return $this->result(0, $return_data, 'sucess');

    }



}
