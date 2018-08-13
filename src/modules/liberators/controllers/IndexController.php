<?php
namespace app\modules\liberators\controllers;
use \app\modules\home\controllers\CommonController;
use \app\modules\liberators\models\PeopleInformation;
use \app\modules\home\models\News_Multi_Language;
use \app\modules\liberators\models\Video;
use app\Library\gameapi\Play;
use app\Library\gameapi\GameApi;
use Yii;

class IndexController extends CommonController
{
    public function init()
    {
        parent::init();
        $this->view->params['meta_title'] = \yii::t('common', 'TitLiberatorsOfficialSite');
        $this->view->params['keyword'] = "liberators login,liberators gameplay,best strategy games,mutantbox";
        $this->view->params['description'] = "Liberators,best ww2 MMO Strategy Gamefrom mutantbox.com.Free online strategy games.Liberators official site provide free onling war games login and gameplay.";
    }

    public function actionIndex()
    {
        ini_set('max_execution_time', 300);
        $peopleModel   = new PeopleInformation;
        $peopleInfo    = $peopleModel->getInfoById(true); //获取默认人物信息
        $peopleInfoImg = $peopleModel->getFields('portraits,hid AS id'); //获取人物头像
        $peopleInfoImg = $this->orderInfo($peopleInfoImg, $peopleInfo['hid']);

        $articleModel = new News_Multi_Language;
        $eventList2  = $articleModel->getArticleList(16,3,true,0,LANG_SET,4);//EVENT
        if(empty($eventList2)) $eventList2  = $articleModel->getArticleList(16,3,true,0,'en-us',4);
        $eventList[] = current($eventList2);

        $anList2  = $articleModel->getArticleList(17,3,true,0,LANG_SET,4);//ANNOUNCEMENT
        if(empty($anList2)) $anList2  = $articleModel->getArticleList(17,3,true,0,'en-us',4);
        $anList[] = current($anList2);

        $strategyList  = $articleModel->getArticleList(13,6,false,0,LANG_SET,4);//STRATEGY
        if(empty($strategyList)) $strategyList  = $articleModel->getArticleList(13,3,false,0,'en-us',4);
        $tutorailList  = $articleModel->getArticleList(12,6,false,0,LANG_SET,4);//tutorail
        if(empty($tutorailList)) $tutorailList  = $articleModel->getArticleList(12,3,false,0,'en-us',4);
        $orignieslList  = $articleModel->getArticleList(14,6,false,0,LANG_SET,4);//orignies
        if(empty($orignieslList)) $orignieslList  = $articleModel->getArticleList(14,3,false,0,'en-us',4);

        $videoModel = new Video;
        $videoList  = $videoModel->getVideoList(4);
        $firstvideo = [];
        if($videoList){
            foreach ($videoList as $v){
                $firstvideo = $v;
                break;
            }
        }

        $userInfo  = $this->is_login ? $this->user_info : [];
        $playApi         = new Play;
        $openedServer    = $playApi->getOpenedServerList(4, $this->is_login ? $userInfo['id'] : 0);
        $recommendServer = $playApi->getRecommendServerList(4);

        $myServiceLists = [];
        if ($this->is_login) {
            // 获取用户玩过的4个区服
            $tmpMyServiceLists = $playApi->getEnteredServerList(4, $userInfo['id'], '', 4);
            if($tmpMyServiceLists && is_array($tmpMyServiceLists)){
                foreach ($tmpMyServiceLists as $item) {
                    $myServiceLists[$item['server_id']] = $item;
                }
            }

            // 获取玩过区服的用户信息
            $gameApiParams = [];
            $params['oid'] = [$userInfo['id']];
            if($myServiceLists  && is_array($myServiceLists)){
                foreach ($myServiceLists as $server_id => $item) {
                    if (isset($openedServer[$server_id]['server_url']) && isset($openedServer[$server_id]['server_status'])) {
                        $myServiceLists[$server_id]['server_name']              = $openedServer[$server_id]['server_name'];
                        $myServiceLists[$server_id]['server_status']              = $openedServer[$server_id]['server_status'];
                        $params['server_id']                                            = $server_id;
                        if ($openedServer[$server_id]['server_status'] == $playApi::$OPEN_STATE['OPENING']['value']) {
                            $gameApiParams[$openedServer[$server_id]['server_url']] = $params;
                        }
                    }else{
                        unset($myServiceLists[$server_id]);
                    }
                }
            }
            if ($gameApiParams) {
                $gameApi = new GameApi();
                foreach ($gameApi->getUserInfo($gameApiParams) as $serverID => $gameUserInfo) {
                    $myServiceLists[$serverID]['game_user_info'] = $gameUserInfo;
                }
            }
        }

        if (count($myServiceLists) < 3) {
            for ($i = 0, $plusNum = 3 - count($myServiceLists); $i < $plusNum; $i++) {
                $myServiceLists[] = [];
            }
        }

        return $this->render('index.html', [
            'myServiceLists'=>$myServiceLists,
            'recommendServer'=>array_slice($recommendServer, 0, 4),
            'isLogined'       => $this->is_login,
            'firstvideo'=>$firstvideo,
            'tutorailList'=>$tutorailList,
            'orignieslList'=>$orignieslList,
            'strategyList'=>$strategyList,
            'videoList'=>$videoList,
            'eventList'=>$eventList,
            'eventList2'=>$eventList2,
            'anList'=>$anList,
            'anList2'=>$anList2,
            'heroImg'=>$peopleInfoImg,
            'heroInfo'=>$peopleInfo,
        ]);
    }


    /**
     * 随机获取信息
     */
    private function orderInfo($data, $id)
    {
        $first = array();
        foreach ($data as $k => $v) {
            if ($v['id'] == $id) {
                $first = $data[$k];
                unset($data[$k]);
            }
        }
        array_unshift($data, $first);
        return $data;
    }

    /**
     * 根据英雄id获取相应的信息
     */
    public function actionGetpeople()
    {
        $id          = \Yii::$app->request->post('id',null);
        $id          = $id ? $id : true;
        $peopleModel = new PeopleInformation;
        $peopleInfo  = $peopleModel->getInfoById($id); //获取默认人物信息
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return array('status' => 200, 'data' => $peopleInfo);
    }

}
