<?php
/**
 * 服务器相关接口
 */

namespace app\Library\gameapi;
class Server {
    public static $ASSORT = [
        'PRODUCT' => ['label' => '正式服', 'value' => '31'],
        'TEST'    => ['label' => '永测服', 'value' => '41'],
        'OUTTEST' => ['label' => '外网测试服', 'value' => '42'],
        'INTEST'  => ['label' => '内网测试服', 'value' => '43'],
        'QA'      => ['label' => 'QA测试服', 'value' => '44'],
    ];

    // 开放状态
    public static $OPEN_STATE = [
        'UNCONFIGURED' => ['label' => '待配服', 'value' => '1'],
        'CONFIGURED'   => ['label' => '已配服', 'value' => '2'],
        'WAIT_OPEN'    => ['label' => '待开启', 'value' => '3'],
        'WAIT_PUBLIC'  => ['label' => '待开服', 'value' => '4'],
        'OPENING'      => ['label' => '已开启', 'value' => '5'],
        'MAINTAINING'  => ['label' => '维护中', 'value' => '6'],
        'MERGING'      => ['label' => '合服中', 'value' => '7'],
        'STOPING'      => ['label' => '已停服', 'value' => '8'],
        'MERGED'       => ['label' => '已合服', 'value' => '9'],
    ];

    // 强制样式
    public static $FORCE_STYLE = [
        'AUTO'     => ['label' => '自动', 'value' => '1'],
        'NORMAL'   => ['label' => '正常', 'value' => '2'],
        'HOT'      => ['label' => '火爆', 'value' => '3'],
        'OPENSOON' => ['label' => '即将开启', 'value' => '4'],
        'STOPSOON' => ['label' => '停止激活', 'value' => '5'],
    ];

    private $SAPIUrl; // 开服URl,可获得服务器列表
    private $ErrMsg = '';

    private $CachePrefix    = 'ServerApiCache';
    private $CacheExpireSec = 60;

    public function __construct() {
        $this->SAPIUrl = \YII::$app->params['MY_URL']['SAPI']. '/index.php';
        $this->OPSUrl  = \YII::$app->params['MY_URL']['OPS'] . '/api/index';
    }

    public function getGameInfo($gameID) {
        $cacheName = $this->CachePrefix . '.GameInfo' . $gameID;
        $fileName  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($cacheName) . '.mutex';

        $dataArr = \Yii::$app->cache->get($cacheName);
        // 数据过期并且
        if (isset($dataArr['setUnixTime']) && (time() - $dataArr['setUnixTime']) <= $this->CacheExpireSec && isset($dataArr['data'])) {
            return $dataArr['data'];
        } else {
            // 使用文件互斥锁.防止多个用户同时更新数据
            $fp = fopen($fileName, 'w+');
            if (flock($fp, LOCK_EX | LOCK_NB) || !isset($dataArr['data'])) {
                if (function_exists('fastcgi_finish_request') && isset($dataArr['data'])) {
                    // 存在nginx异步机制.异步更新缓存
                    register_shutdown_function(function () use ($cacheName, $fp, $gameID) {
                        ignore_user_abort();
                        fastcgi_finish_request();
                        if ($tmpData = $this->_getGameInfo($gameID)) {
                            $dataArr = [
                                'data'        => $tmpData,
                                'setUnixTime' => time(),
                            ];
                            \Yii::$app->cache->set($cacheName, $dataArr);
                        } else {
                            \Yii::getLogger()->log('cant decode async getGameInfo data', 9, __FILE__ . '    ' . __LINE__);
                        }
                        flock($fp, LOCK_UN);
                        fclose($fp);
                    });
                } else {
                    // 不存在nginx异步机制.同步更新缓存
                    if ($tmpData = $this->_getGameInfo($gameID)) {
                        $dataArr = [
                            'data'        => $tmpData,
                            'setUnixTime' => time(),
                        ];
                        \Yii::$app->cache->set($cacheName, $dataArr);
                    } else {
                        \Yii::getLogger()->log('cant decode sync getGameInfo data', 9, __FILE__ . '    ' . __LINE__);
                    }
                    flock($fp, LOCK_UN);
                    fclose($fp);
                }
            } else {
                fclose($fp);
            }
            return isset($dataArr['data']) ? $dataArr['data'] : false;
        }
    }

    /**
     *
     * @access public
     * @param  void   $gameID
     * @return void
     */
    private function _getGameInfo($gameID) {
        $data = $this->sendRequest([], 'GET', true, $this->SAPIUrl . '/game/list/game_id/' . $gameID . '/no_cache/1');
        if (!is_array($data)) {
            return false;
        }
        foreach ($data as $item) {
            if ($item['game_id'] == $gameID) {
                return $item;
            }
        }
    }

    /**
     * 获得所有服务器列表
     *
     * @access private
     * @return void
     */
    public function getServerLists($gameID) {
        $cacheName = $this->CachePrefix . '.ServerList' . $gameID;
        $fileName  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($cacheName) . '.mutex';

        $dataArr = \Yii::$app->cache->get($cacheName);

        // 数据过期并且
        if (isset($dataArr['setUnixTime']) && (time() - $dataArr['setUnixTime']) <= $this->CacheExpireSec && isset($dataArr['data'])) {
            return $dataArr['data'];
        } else {
            // 使用文件互斥锁.防止多个用户同时更新数据
            $fp = fopen($fileName, 'w+');
            if (flock($fp, LOCK_EX | LOCK_NB) || !isset($dataArr['data'])) {
                if (function_exists('fastcgi_finish_request') && isset($dataArr['data'])) {
                    // 存在nginx异步机制.异步更新缓存
                    register_shutdown_function(function () use ($cacheName, $fp, $gameID) {
                        ignore_user_abort();
                        fastcgi_finish_request();
                        if ($tmpData = $this->sendRequest([], 'GET', true, $this->SAPIUrl . '/server/listbygame/game_id/' . $gameID . '/no_cache/1')) {
                            $dataArr = [
                                'data'        => $tmpData,
                                'setUnixTime' => time(),
                            ];
                            \Yii::$app->cache->set($cacheName, $dataArr);
                        } else {
                            \Yii::getLogger()->log('cant decode async getServerLists data', 9, __FILE__ . '    ' . __LINE__);
                        }
                        flock($fp, LOCK_UN);
                        fclose($fp);
                    });
                } else {
                    // 不存在nginx异步机制.同步更新缓存
                    if ($tmpData = $this->sendRequest([], 'GET', true, $this->SAPIUrl . '/server/listbygame/game_id/' . $gameID . '/no_cache/1')) {
                        $dataArr = [
                            'data'        => $tmpData,
                            'setUnixTime' => time(),
                        ];
                        \Yii::$app->cache->set($cacheName, $dataArr);
                    } else {
                        \Yii::getLogger()->log('cant decode sync getServerLists data', 9, __FILE__ . '    ' . __LINE__);
                    }
                    flock($fp, LOCK_UN);
                    fclose($fp);
                }
            } else {
                fclose($fp);
            }

            return isset($dataArr['data']) ? $dataArr['data'] : false;
        }
    }

    /**
     * 获得所有服务器列表
     *
     * @access private
     * @return void
     */
    public function getServerListsByDetail($gameID) {
        $cacheName = $this->CachePrefix . '.ServerListByDetail' . $gameID;

        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($cacheName) . '.mutex';

        $dataArr = \Yii::$app->cache->get($cacheName);
        // 数据过期并且
        if (isset($dataArr['setUnixTime']) && (time() - $dataArr['setUnixTime']) <= $this->CacheExpireSec && isset($dataArr['data'])) {
            return $dataArr['data'];
        } else {
            // 使用文件互斥锁.防止多个用户同时更新数据

            $fp = fopen($fileName, 'w+');
            if (flock($fp, LOCK_EX | LOCK_NB) || !isset($dataArr['data'])) {

                if (function_exists('fastcgi_finish_request') && isset($dataArr['data'])) {
                    // 存在nginx异步机制.异步更新缓存
                    register_shutdown_function(function () use ($cacheName, $fp, $gameID) {
                        ignore_user_abort();
                        fastcgi_finish_request();
                        if ($tmpData = $this->_getServerListsByDetail($gameID)) {

                            $dataArr = [
                                'data'        => $tmpData,
                                'setUnixTime' => time(),
                            ];
                            \Yii::$app->cache->set($cacheName, $dataArr);
                        } else {

                            \Yii::getLogger()->log('cant decode async getServerListsByDetail data', 9, __FILE__ . '    ' . __LINE__);
                        }
                        flock($fp, LOCK_UN);
                        fclose($fp);
                    });
                } else {
                    // 不存在nginx异步机制.同步更新缓存
                    if ($tmpData = $this->_getServerListsByDetail($gameID)) {
                        $dataArr = [
                            'data'        => $tmpData,
                            'setUnixTime' => time(),
                        ];
                        \Yii::$app->cache->set($cacheName, $dataArr);
                    } else {

                        \Yii::getLogger()->log('cant decode sync getServerListsByDetail data', 9, __FILE__ . '    ' . __LINE__);
                    }

                    flock($fp, LOCK_UN);
                    fclose($fp);
                }
            } else {

                fclose($fp);
            }

            return isset($dataArr['data']) ? $dataArr['data'] : false;
        }
    }

    /**
     * 获得详细服务器列表
     *
     * @access public
     * @param  void   $gameID
     * @return void
     */
    private function _getServerListsByDetail($gameID) {
        $serverLists = $this->getServerLists($gameID);
        if (!is_array($serverLists)) {
            return false;
        }

        foreach ($serverLists as $key => $item) {
            unset($serverLists[$key]);
            $serverLists[$item['server_id']] = $item;
        }

        // 获得所有
        $opgameConfig = [];
        $tmpData      = $this->sendRequest([], 'GET', true, $this->SAPIUrl . '/opgame/list/no_cache/1');
        if (!is_array($tmpData)) {
            return false;
        }

        foreach ($tmpData as $item) {
            $opgameConfig[$item['opgame_id']] = $item;
        }

        if ($serverLists) {
            foreach ($serverLists as $key => $item) {
                if (isset($opgameConfig[$item['opgame_id']])) {
                    $serverLists[$key]['detail_info'] = [
                        'opgame' => $opgameConfig[$item['opgame_id']],
                    ];
                }
            }
        }
        return $serverLists;
    }


    /**
     * 获得服务器信息
     * 支持批量获取
     *
     * @access public
     * @param  array   $serverId
     * @return array
     */
    public function getServerInfo(array $serverIDArr) {
        $serverIDArr = (array) $serverIDArr;
        sort($serverIDArr);

        $cacheName = $this->CachePrefix . '.ServerInfo' . md5(implode(',', $serverIDArr));
        if (($data = \Yii::$app->cache->get($cacheName))) {
            return $data;
        }

        $multiCurl = new \app\Library\curl\MultiCurl();

        $data = [];
        foreach ($serverIDArr as $serverID) {
            $multiCurl->addGet($this->OPSUrl . '/server/listbyserver/server_id/' . $serverID . '/no_cache/1');
        }

        // 拉取服务器信息成功
        $multiCurl->success(function ($instance) use (&$data) {
            if ($handledData = $this->handleData($instance->response, true)) {
                $data[] = current($handledData);
            }
        });

        // 会阻塞在这里
        $multiCurl->start();

        $data && \Yii::$app->cache->set($cacheName, $data, $this->CacheExpireSec);

        return $data;
    }
    /**
     * 获取所有开启的服务器列表
     *
     * @access public
     * @param  void   $gameID
     * @param  void   $platform
     * @param  void   $userID
     * @return void
     */
    public function getAllServerList($gameID = null, $platform = null, $userID = null, $userIP = null, $showMaintaining = null, $showOpeningSoon = null) {
        $platformNum = $platform == 'fb' ? \YII::$app->params['PLATFORM_NUM_FB'] : \YII::$app->params['PLATFORM_NUM_GW'];

        // 获取游戏列表对应的服务器.
        $allServerLists = $this->getServerListsByDetail($gameID) ?: [];
        $gameInfo       = $this->getGameInfo($gameID);

        $gameWhiteIP = (string) $gameInfo['ip_white'] === '' ? [] : explode(',', $gameInfo['ip_white']);
        $gameBlackIP = (string) $gameInfo['ip_black'] === '' ? [] : explode(',', $gameInfo['ip_black']);
        $isWhiteUser = in_array($userIP, $gameWhiteIP);
        $isBlackUser = in_array($userIP, $gameBlackIP);

        foreach ($allServerLists as $serverID => $item) {
            if ($isWhiteUser) {
                continue;
            }

            if ($isBlackUser || !$this->filterServer($item, $platformNum, $userID, $userIP, $showMaintaining, $showOpeningSoon)) {
                unset($allServerLists[$serverID]);
            }
        }

        array_multisort(
        // array_column($allServerLists, 'is_recommend'), SORT_DESC, SORT_NUMERIC,
            array_column($allServerLists, 'first_opentime'), SORT_DESC, SORT_NUMERIC,
            $allServerLists
        );

        $return = [];
        foreach ($allServerLists as $key => $item) {
            $return[$item['server_id']] = $item;
        }

        $this->result['data'] = $return;
        return $this->result;
    }


    /**
     * 服务器筛选.过滤测试账号.过滤服务器类型
     *
     * @access public
     * @param  array  $serverArr
     * @return void
     */
    public function filterServer($serverInfo, $platformNum, $userID, $userIP, $showMaintaining = 0, $showOpeningSoon = 0) {
        // 存在测试账号白名单
        if (isset($serverInfo['detail_info']['opgame']['opgame_instances'][$platformNum]['test_account'])) {
            if ((string) $serverInfo['detail_info']['opgame']['opgame_instances'][$platformNum]['test_account'] !== '') {
                $allowdUserID = explode(',', $serverInfo['detail_info']['opgame']['opgame_instances'][$platformNum]['test_account']);
                if ($userID && in_array($userID, $allowdUserID)) {
                    return true;
                }
            }
        }

        // 存在IP白名单
        if (isset($serverInfo['ip_white']) && (string) $serverInfo['ip_white'] !== '') {
            $allowdUserIP = explode(',', $serverInfo['ip_white']);
            if ($userIP && in_array($userIP, $allowdUserIP)) {
                return true;
            } else {
                return false;
            }
        }

        // 存在IP黑名单
        if (isset($serverInfo['ip_black']) && (string) $serverInfo['ip_black'] !== '') {
            $allowdUserIP = explode(',', $serverInfo['ip_black']);
            if ($userIP && in_array($userIP, $allowdUserIP)) {
                return false;
            }
        }

        //------------------------------------------------------------------------
        // 普通用户显示要求
        // 1.服务器类型为正式服
        // 2.is_active为正常
        // 3.is_hide为非隐藏
        // 4.服务器状态为正常或维护且showMaintaining为1
        // 5.开服预告时间到了

        // 服务器类型筛选
        if (!isset(\YII::$app->params['allowed_server_type'][$serverInfo['type']])) {
            return false;
        }

        // 服务器允许状态
        // 激活/不隐藏/服务器状态
        if ($serverInfo['is_active'] == '0' || $serverInfo['is_hide'] == '1') {
            return false;
        } elseif ($serverInfo['server_status'] == self::$OPEN_STATE['OPENING']['value']) {
            return true;
        } elseif ($showMaintaining && $serverInfo['server_status'] == self::$OPEN_STATE['MAINTAINING']['value']) {
            return true;
        } elseif ($showOpeningSoon && $serverInfo['notice_opentime'] && $serverInfo['notice_opentime'] <= $_SERVER['REQUEST_TIME'] && $serverInfo['server_status'] != self::$OPEN_STATE['MAINTAINING']['value']) {
            return true;
        } else {
            return false;
        }

        return true;
    }
    /**
     * 获得错误信息
     *
     * @access public
     * @return void
     */
    public function getError() {
        return $this->ErrMsg;
    }

    /**
     * 发送请求
     *
     * @access public
     * @param  array  $params 参数
     * @param  string $request_method 请求方法 GET|POST
     * @param  bool   $return_data 是否返回数据中的data字段
     * @return void
     */
    private function sendRequest(array $params = array(), $request_method = 'GET', $return_data = true, $url = false) {
        $log_str = json_encode($params);

        $url = $url ?: $this->url;

        $curl = new \app\Library\curl\Curl();
        switch (strtoupper($request_method)) {
        case 'POST':
            $response_body = $curl->post($url, $params);
            break;

        case 'GET':
        default:
            $response_body = $curl->get($url, $params);
            break;
        }

        return $this->handleData($response_body, $return_data);
    }

    /**
     * 处理数据
     *
     * @access public
     * @param  string $response_body
     * @return void
     */
    private function handleData($response_body = '', $return_data) {
        $response_arr = json_decode($response_body, true);
        if (isset($response_arr['stat']) && $response_arr['stat'] == 'ok') {
            if ($return_data) {
                return isset($response_arr['msg']) ? $response_arr['msg'] : false;
            }
            return true;
        }
        if (isset($response_arr['msg'])) {
            $this->ErrMsg = $response_arr['msg'];
        }
        return false;
    }
}
