<?php
/**
 * 服务器相关接口
 */

namespace app\Library;

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

    private $SAPIUrl; // 开服URl,可获得服务器列表
    private $ErrMsg = '';

    private $CachePrefix    = 'ServerApiCache';
    private $CacheExpireSec = 60;

    public function __construct() {
        $this->SAPIUrl = \Yii::$app->params['sapi_url'] . '/index.php';
        $this->OPSUrl  = \Yii::$app->params['ops_url'] . '/api/index';
    }

    public function getGameInfo($gameID) {
        $cacheName = $this->CachePrefix . '.GameInfo' . $gameID;
        if ($data = \Yii::$app->cache->get($cacheName)) {
            return $data;
        }

        $data = $this->sendRequest(array(), 'GET', true, $this->SAPIUrl . '/game/list/game_id/' . $gameID . '/no_cache/1');

        $return_data = false;
        if ($data) {
            foreach ($data as $item) {
                if ($item['game_id'] == $gameID) {
                    \Yii::$app->cache->set($cacheName, $item, $this->CacheExpireSec);
                    return $item;
                }
            }
        }

        return $return_data;
    }

    /**
     * 获得所有服务器列表
     *
     * @access private
     * @return void
     */
    public function getServerLists($gameID) {
        $cacheName = $this->CachePrefix . '.ServerList' . $gameID;
        if ($data = \Yii::$app->cache->get($cacheName)) {
            return $data;
        }

        $data = $this->sendRequest(array(), 'GET', true, $this->SAPIUrl . '/server/listbygame/game_id/' . $gameID . '/no_cache/1');

        $data && \Yii::$app->cache->set($cacheName, $data, $this->CacheExpireSec);

        return $data;
    }

    /**
     * 获得所有服务器列表
     *
     * @access private
     * @return void
     */
    public function getServerListsByDetail($gameID) {
        $cacheName = $this->CachePrefix . '.ServerListByDetail' . $gameID;
        if ($data = \Yii::$app->cache->get($cacheName)) {
            return $data;
        }

        $serverLists = $this->getServerLists($gameID);
        foreach ($serverLists as $key => $item) {
            unset($serverLists[$key]);
            $serverLists[$item['server_id']] = $item;
        }

        // 获得所有
        $opgameConfig = [];
        foreach ($this->sendRequest(array(), 'GET', true, $this->SAPIUrl . '/opgame/list/no_cache/1') as $item) {
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

        $serverLists && \Yii::$app->cache->set($cacheName, $serverLists, $this->CacheExpireSec);

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
        if ($data = \Yii::$app->cache->get($cacheName)) {
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

        $curl = new curl\Curl();
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

    /**
     * 创建token
     *
     * @access public
     * @param  array  &$params
     * @return void
     */
    private function createToken(array &$params = array()) {
    }
}
