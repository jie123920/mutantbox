<?php
namespace app\Library\gameapi;
use app\helpers\myhelper;
use app\Library\curl\Curl;

class Play {
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

    private $key = ''; // 加密key

    private $Url; // play.mutantbox.com

    private $SAPI_Url; // play.mutantbox.com

    private $ErrMsg = '';

    private $CachePrefix   = 'PlayApiCache';
    public $CacheExpireSec = 60;

    public function __construct() {
        $this->Url      = dirname(CONNECTURL);
        $this->SAPI_Url = \YII::$app->params['MY_URL']['SAPI'];
    }

    /**
     * 广告用户通过IP获得服务器ID
     *
     * @access public
     * @param  void   $gameID
     * @param  void   $userIP
     * @return void
     */
    public function getAdServerID($gameID, $userIP) {
        $data = $this->sendRequest(array(), 'GET', true, $this->SAPI_Url . '/index.php/area/index/server_id/' . $gameID . '/user_ip/' . $userIP, false);

        return $data;
    }

    /**
     * 获得所有开放的服务器列表
     *
     * @access public
     * @param  void   $gameID
     * @return void
     */
    public function getOpenedServerList($gameID, $userID = 0, $platform = '', $showMaintaining = 1, $showOpeningSoon = 1, $userIP = null) {
        $userIP = $userIP !== null ? $userIP : myhelper::get_client_ip();

        $platformNum = $platform == 'fb' ? \YII::$app->params['PLATFORM_NUM_FB'] : \YII::$app->params['PLATFORM_NUM_GW'];

        // 获取游戏列表对应的服务器.
        $serverApi            = new Server();
        $allServerLists       = $serverApi->getServerListsByDetail($gameID) ?: [];
        $gameInfo             = $serverApi->getGameInfo($gameID);
        $gameInfo['ip_white'] = isset($gameInfo['ip_white']) ? (string) $gameInfo['ip_white'] : '';
        $gameInfo['ip_black'] = isset($gameInfo['ip_black']) ? (string) $gameInfo['ip_black'] : '';

        $gameWhiteIP = $gameInfo['ip_white'] === '' ? [] : explode(',', $gameInfo['ip_white']);
        $gameBlackIP = $gameInfo['ip_black'] === '' ? [] : explode(',', $gameInfo['ip_black']);
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
            array_column($allServerLists, 'first_opentime'), SORT_DESC, SORT_NUMERIC,
            $allServerLists
        );

        $return = [];
        foreach ($allServerLists as $key => $item) {
            $return[$item['server_id']] = $item;
        }

        return $return;
    }

    /**
     * 获得所有推荐的服务器列表
     *
     * @access public
     * @param  void   $gameID
     * @return void
     */
    public function getRecommendServerList($gameID, $userIP = null) {
        $userID   = 0;
        $platform = '';
        $userIP !== null ? $userIP : myhelper::get_client_ip();
        $platformNum = $platform == 'fb' ? \YII::$app->params['PLATFORM_NUM_FB'] : \YII::$app->params['PLATFORM_NUM_GW'];

        $allServerLists = $this->getOpenedServerList($gameID, $userID, $platform, 0, 1);

        $serverApi            = new Server();
        $gameInfo             = $serverApi->getGameInfo($gameID);
        $gameInfo['ip_white'] = isset($gameInfo['ip_white']) ? (string) $gameInfo['ip_white'] : '';
        $gameInfo['ip_black'] = isset($gameInfo['ip_black']) ? (string) $gameInfo['ip_black'] : '';

        $gameWhiteIP = $gameInfo['ip_white'] === '' ? [] : explode(',', $gameInfo['ip_white']);
        $gameBlackIP = $gameInfo['ip_black'] === '' ? [] : explode(',', $gameInfo['ip_black']);
        $isWhiteUser = in_array($userIP, $gameWhiteIP);
        $isBlackUser = in_array($userIP, $gameBlackIP);

        foreach ($allServerLists as $serverID => $item) {
            // 只获取被推荐的
            if ($item['is_recommend'] != 1) {
                unset($allServerLists[$serverID]);
            }

            if ($isWhiteUser) {
                continue;
            }

            // 过滤开启测试账号,服务器类型和服务器开启状态
            if ($isBlackUser || !$this->filterServer($item, $platformNum, $userID, $userIP, $showMaintaining = 0, $showOpeningSoon = 1)) {
                unset($allServerLists[$serverID]);
            }
        }
        array_multisort(
            array_column($allServerLists, 'first_opentime'), SORT_DESC, SORT_NUMERIC,
            $allServerLists
        );

        $return = [];
        foreach ($allServerLists as $key => $item) {
            $return[$item['server_id']] = $item;
        }

        return $return;
    }

    /**
     * 获得用户玩过的服务器列表
     *
     * @access public
     * @param  void   $gameID
     * @param  void   $uID
     * @param  void   $platform
     * @return void
     */
    public function getEnteredServerList($gameID, $uID, $platform = '', $limit = 4) {
        $key = 'EnteredServerList_'.$gameID.':'.$uID.':'.$limit;
        if ($data = \Yii::$app->cache->get($key)) {
            return $data;
        }

        $data = $this->sendRequest(array(), 'GET', true, $this->Url . "/api/get-entered-server?game_id={$gameID}&user_id={$uID}&platform={$platform}&max_num={$limit}&user_ip=" . myhelper::get_client_ip());

        $data && \Yii::$app->cache->set($key, $data, 60);

        return $data;
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
    private function sendRequest(array $params = array(), $request_method = 'GET', $return_data = true, $url = false, $checkData = true) {
        $url = $url ? $url : $this->Url;

        $curl = new \app\Library\curl\Curl();
        switch (strtoupper($request_method)) {
            case 'POST':
                $curl->post($url, $params);
                break;

            case 'GET':
            default:
                $curl->get($url, $params);
                break;
        }

        if ($curl->error) {
            $logStr = date('Y-m-d H:i:s') . '-----------PlayController' . PHP_EOL .
                'URL:' . $url . PHP_EOL .
                'METHOD:' . $request_method . PHP_EOL .
                'PARAMS:' . json_encode($params) . PHP_EOL .
                'curlErrorMessage:' . $curl->curlErrorMessage . PHP_EOL .
                'curlError:' . $curl->curlError . PHP_EOL .
                'httpStatusCode:' . $curl->httpStatusCode . PHP_EOL .
                'httpError:' . $curl->httpError . PHP_EOL .
                'error:' . $curl->error . PHP_EOL .
                'errorCode:' . $curl->errorCode . PHP_EOL .
                'effectiveUrl:' . $curl->effectiveUrl . PHP_EOL;
                \YII::error($logStr,'playlog');
        }

        if ($checkData) {
            return $this->handleData($curl->rawResponse, $return_data);
        }
        return $curl->rawResponse;
    }

    /**
     * 服务器筛选.过滤测试账号.过滤服务器类型
     *
     * @access public
     * @param  array  $serverArr
     * @return void
     */
    private function filterServer($serverInfo, $platformNum, $userID, $userIP, $showMaintaining = 0, $showOpeningSoon = 0) {
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
        $allowServerTypeArr = \YII::$app->params['allowed_server_type'];
        if (!isset($allowServerTypeArr[$serverInfo['type']])) {
            return false;
        }

        // 服务器允许状态
        // 激活/不隐藏/服务器状态
        if ($serverInfo['is_active'] == '0' || $serverInfo['is_hide'] == '1') {
            return false;
        } elseif ($serverInfo['server_status'] == Server::$OPEN_STATE['OPENING']['value']) {
            return true;
        } elseif ($showMaintaining && $serverInfo['server_status'] == Server::$OPEN_STATE['MAINTAINING']['value']) {
            return true;
        } elseif ($showOpeningSoon && $serverInfo['notice_opentime'] && $serverInfo['notice_opentime'] <= $_SERVER['REQUEST_TIME'] && $serverInfo['server_status'] != Server::$OPEN_STATE['MAINTAINING']['value']) {
            return true;
        } else {
            return false;
        }

        return true;
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
        if (isset($response_arr['code']) && $response_arr['code'] == 0) {
            if ($return_data) {
                if (isset($response_arr['data'])) {
                    if (!$response_arr['data']) {
                        $logStr = date('Y-m-d H:i:s') . '-----------PlayControllerHandleData' . PHP_EOL .
                            'response_body:' . $response_body . PHP_EOL;
                        \YII::error($logStr,'playlog');
                    }
                    return $response_arr['data'];
                } else {
                    $logStr = date('Y-m-d H:i:s') . '-----------PlayControllerHandleData' . PHP_EOL .
                        'response_body:' . $response_body . PHP_EOL;
                    \YII::error($logStr,'playlog');
                    return false;
                }
            }
            return true;
        }
        if (isset($response_arr['error'])) {
            $this->ErrMsg = $response_arr['error'];
            $logStr = date('Y-m-d H:i:s') . '-----------PlayControllerHandleData' . PHP_EOL .
                'response_body:' . $response_body . PHP_EOL;
            \YII::error($logStr,'playlog');
        }

        return false;
    }



    /**
     * 通过接口获取服务器信息
     *2016年6月6日 下午2:52:31
     * @param unknown $serverid
     * @param string $param
     * @return string|unknown
     */
    public static function getserverinfo($serverid, $param = NULL){
        $data = '';
        $sapi = \YII::$app->params['MY_URL']['SAPI'];
        $url = $sapi."/index.php/server/listbyserver?server_id=".$serverid;
        $curl = new Curl();
        $returnjson = $curl->get($url);

        $returnData = json_decode($returnjson, true);
        if (is_array($returnData) && isset($returnData['msg']) && ($returnData['stat'] == 'ok')){
            $serverInfo = $returnData['msg'][0];
            if (isset($serverInfo) && is_array($serverInfo)){
                if ($param == 'pay_url'){
                    $data[$param] = 'http://'.$serverInfo['server_url'].'/pay.php';
                }elseif ($param == 'ip'){
                    $data[$param] = $serverInfo['server_url'];
                }elseif ($param == 'recharge_opentime'){
                    $data[$param] = $serverInfo['recharge_opentime'];
                }else {
                    $data[$param] = $serverInfo['server_name'];
                }
            }
        }

        if (isset($data[$param])){
            return $data[$param];
        }else {
            return '';
        }
    }


    /**
     * 发送HTTP请求方法
     * @param  string $url    请求URL
     * @param  array  $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    public static function http($url, $params, $method = 'GET', $header = array(), $multi = false, $duration=30)
    {
        $opts = array(
            CURLOPT_TIMEOUT => $duration,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $header
        );

        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                exit('不支持的请求方式！');
        }
        
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        //     if ($error)
        //         throw new Exception('请求发生错误：' . $error);
        return $data;
    }
}
