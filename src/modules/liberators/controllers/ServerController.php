<?php
namespace app\modules\liberators\controllers;
use \app\modules\home\controllers\CommonController;
use app\Library\gameapi\Play;
use app\Library\gameapi\GameApi;
class ServerController extends CommonController
{
    public function init()
    {
        parent::init();
        $this->view->params['meta_title'] = \yii::t('common', 'TitLiberatorsServer');
        $this->view->params['keyword'] = "liberators server,liberators play,liberators official site,mutantbox";
        $this->view->params['description'] = "Liberators server list on official site for players to start play free online strategy games.Liberators,best ww2 MMO Strategy Game from Mutantbox.com.";
    }

    /**
     * 服务器列表
     *
     * @access public
     * @return void
     */
    public function actionIndex()
    {
        ini_set('max_execution_time', 300);

        $userInfo  = $this->is_login ? $this->user_info : [];

        $playApi         = new Play();
        $openedServer    = $playApi->getOpenedServerList(4, $this->is_login ? $userInfo['id'] : 0);
        $recommendServer = $playApi->getRecommendServerList(4);

        $myServiceLists = [];
        if ($this->is_login) {
            // 获取用户玩过的4个区服
            $tmpMyServiceLists = $playApi->getEnteredServerList(4, $userInfo['id'], '', 4);
            foreach ($tmpMyServiceLists as $item) {
                $myServiceLists[$item['server_id']] = $item;
            }

            // 获取玩过区服的用户信息
            $gameApiParams = [];
            $params['oid'] = [$userInfo['id']];
            foreach ($myServiceLists as $server_id => $item) {
                if (isset($openedServer[$server_id]['server_url']) && isset($openedServer[$server_id]['server_status'])) {
                    $myServiceLists[$server_id]['server_name']              = $openedServer[$server_id]['server_name'];
                    $myServiceLists[$server_id]['server_status']            = $openedServer[$server_id]['server_status'];
                    $params['server_id']                                    = $server_id;
                    if ($openedServer[$server_id]['server_status'] == Play::$OPEN_STATE['OPENING']['value']) {
                        $gameApiParams[$openedServer[$server_id]['server_url']] = $params;
                    }
                }else{
                    unset($myServiceLists[$server_id]);
                }
            }
            if ($gameApiParams) {
                $gameApi = new GameApi();
                foreach ($gameApi->getUserInfo($gameApiParams) as $serverID => $gameUserInfo) {
                    $myServiceLists[$serverID]['game_user_info'] = $gameUserInfo;
                }
            }
        }

        //11 => 'US West',12 => 'US East', 20 => 'Europe',30 => 'Oceania',40 => 'Asia',
        $areaServerList = [
            'US_WEST' => [],
            'US_EAST' => [],
            'EUROPE'  => [],
            'OCEANIA' => [],
            'ASIA'    => [],
            'UNKNOWN' => [],
        ];

        foreach ($openedServer as $serverID => $serverInfo) {
            switch (current(json_decode($serverInfo['city_region'], true))) {
                case 11:
                    $areaServerList['US_WEST'][] = $serverInfo;
                    break;
                case 12:
                    $areaServerList['US_EAST'][] = $serverInfo;
                    break;
                case 20:
                    $areaServerList['EUROPE'][] = $serverInfo;
                    break;
                case 30:
                    $areaServerList['OCEANIA'][] = $serverInfo;
                    break;
                case 40:
                    $areaServerList['ASIA'][] = $serverInfo;
                    break;
                default:
                    $areaServerList['UNKNOWN'][] = $serverInfo;
                    break;
            }
        }
        return $this->render('index.html', [
            'myServiceLists'        => $myServiceLists,
            'recommendedServerList' => array_slice($recommendServer, 0, 6), // 只获取6个
            'areaServerList'        => $areaServerList,
            'isLogined'             => $this->is_login,
            'areas'                 => ['EUROPE', 'US_WEST', 'US_EAST', 'OCEANIA'],
        ]);
    }

    /**
     * 所有服务器列表
     *
     * @access public
     * @return void
     */
    public function actionGetAllServer()
    {
        $this->layout = false;
        ini_set('max_execution_time', 300);

        $userInfo  = $this->is_login ? $this->user_info : [];

        $playApi         = new Play();
        $openedServer    = $playApi->getOpenedServerList(4, $this->is_login ? $userInfo['id'] : 0);

        $myServiceLists = [];
        if ($this->is_login) {
            // 获取用户玩过的4个区服
            $tmpMyServiceLists = $playApi->getEnteredServerList(4, $userInfo['id'], '', 9999);
            foreach ($tmpMyServiceLists as $item) {
                $myServiceLists[$item['server_id']] = $item;
            }

            // 获取玩过区服的用户信息
            $gameApiParams = [];
            $params['oid'] = [$userInfo['id']];
            foreach ($myServiceLists as $server_id => $item) {
                if (isset($openedServer[$server_id]['server_url']) && isset($openedServer[$server_id]['server_status'])) {
                    $myServiceLists[$server_id]['server_name']              = $openedServer[$server_id]['server_name'];
                    $myServiceLists[$server_id]['server_status']            = $openedServer[$server_id]['server_status'];
                    $params['server_id']                                    = $server_id;
                    if ($openedServer[$server_id]['server_status'] == Play::$OPEN_STATE['OPENING']['value']) {
                        $gameApiParams[$openedServer[$server_id]['server_url']] = $params;
                    }
                }else{
                    unset($myServiceLists[$server_id]);
                }
            }
            if ($gameApiParams) {
                $gameApi = new GameApi();
                foreach ($gameApi->getUserInfo($gameApiParams) as $serverID => $gameUserInfo) {
                    $myServiceLists[$serverID]['game_user_info'] = $gameUserInfo;
                }
            }
        }

        return $this->render('user_all_server.html', [
            'myServiceLists'        => $myServiceLists,
            'isLogined'             => $this->is_login,
        ]);
    }
}
