<?php
namespace app\Library\gameapi;
use app\Library\curl\MultiCurl;
/**
 *
 * 刘勇负责的游戏端Api
 *
 * 功能如下
 *
 * 刷新所有道具,所有城市,所有主线任务,所有新手引导缓存
 * 获得用户基础信息
 * 获得已完成任务
 * 获得已开启城池
 * 获得已完成新手引导
 * 获得充值日志
 * 获得背包中的道具列表
 *
 * 设置用户信息
 * 设置背包信息
 * 设置主线任务
 * 开启城池
 * 设置新手引导
 *
 * 获得所有道具列表
 * 获得所有新手引导列表
 * 获得所有城市列表
 * 获得登录Token
 *
 * 获得api错误信息
 *
 * @author 丁洲峰 <adriance@qq.com>
 */

class GameApi
{
    private $key           = ''; // 加密key
    private $url           = ''; // 请求url
    private $ErrMsg        = '';
    private $CachePrefix   = 'GameApiCache';
    public $CacheExpireSec = 3600;
     private $multipleThreadNum = 20;

    private $targetFile = '/api.php';
    private $targetHttp = 'http://';

    public function __construct()
    {
        $this->key = 'as..ldiv@hq9!~!43hg';
        // TODO 测试服务器
        // $this->url = 'http://10.0.0.11:8086/api.php';
    }

    /**
     * 获取用户基础信息
     *
     * @access public
     * @param  array $params 查询参数数组
     * @return array
     */
    /**
     * 批量获取用户信息
     *
     * @access public
     * @param  array  $params
     * array(
     *     '192.168.1.1:80' => array(
     *         'oid' => array("fw_100000"),
     *     )
     * )
     * @return void
     */
    public function getUserInfo(array $params = array())
    {
        $multiCurl = new MultiCurl();

        $data        = [];
        $urlArr      = [];
        $errorServer = [];
        $serverArr   = [];
        foreach ($params as $ipPort => &$param) {
            $param['action']    = 'getUserInfo';
            $serverArr[$ipPort] = $param['server_id'];
            unset($param['token'], $param['server_id']);
            $param['token'] = $this->createToken($param);
        }

        // 拉取服务器信息成功
        $multiCurl->success(function ($instance) use (&$data, &$urlArr, &$serverArr, &$params) {
            unset($params[$urlArr[$instance->id]]);
            $handledData = $this->handleData($instance->rawResponse, true);

            if ($handledData) {
                $data[$serverArr[$urlArr[$instance->id]]] = current($handledData);
            }
        });

        $multiCurl->error(function ($instance) use (&$params, &$errorServer, &$urlArr) {
            if (isset($errorServer[$urlArr[$instance->id]])) {
                if ($errorServer[$urlArr[$instance->id]] >= 3) {
                    unset($params[$urlArr[$instance->id]]);
                } else {
                    $errorServer[$urlArr[$instance->id]]++;
                }
            } else {
                $errorServer[$urlArr[$instance->id]] = 1;
            }
        });

        while ($params) {
            for ($i = 0; $i < $this->multipleThreadNum; $i++) {
                $tmpData = array_slice($params, $i, 1);
                if (!$tmpData) {
                    continue;
                }
                $tmpUrl            = key($tmpData);
                $tmpParam          = current($tmpData);
                $curl              = $multiCurl->addPost($this->targetHttp . $tmpUrl . $this->targetFile, $tmpParam);
                $urlArr[$curl->id] = $tmpUrl;
            }

            // 会阻塞在这里
            $multiCurl->start();
        }

        return $data;
    }


    public function getUserInfo_new(array $params = array(), $game_id = 4)
    {
        $cacheName = ':UserInfo:' . md5(json_encode($params)).$game_id;
        if (($data = \Yii::$app->cache->get($this->CachePrefix . $cacheName))) {
            return $data;
        }

        $multiCurl = new MultiCurl();

        $data   = [];
        $keyArr = [];
        foreach ($params as $ipPort => $param) {
            if ($game_id == 9) {
                $param['action'] = 'searchUser';
                $this->createSign($param);
            } elseif($game_id == 4) {
                $param['action'] = 'getUserInfo';
                $param['token'] = $this->createToken($param);
            }
            $multiCurl->addPost($this->targetHttp . $ipPort . $this->targetFile, $param);
        }

        // 拉取服务器信息成功
        $multiCurl->success(function ($instance) use (&$data, $keyArr) {
            $handledData = $this->handleData($instance->rawResponse, true);
            if ($handledData) {
                $data = $handledData;
            }
        });

        // 会阻塞在这里
        $multiCurl->start();

        return $data;
    }
    /**
     * 获得错误信息
     *
     * @access public
     * @return void
     */
    public function getError()
    {
        return $this->ErrMsg;
    }

    /**
     * 处理数据
     *
     * @access public
     * @param  string $response_body
     * @return void
     */
    private function handleData($response_body = '', $return_data = true)
    {
        $response_arr = json_decode($response_body, true);
        if (isset($response_arr['code']) && $response_arr['code'] == 0) {
            if ($return_data) {
                return isset($response_arr['data']) ? $response_arr['data'] : false;
            }
            return true;
        }
        if (isset($response_arr['msg'])) {
            $this->ErrMsg = $response_arr['msg'];
        }
        return false;
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
    private function sendRequest(array $params = array(), $request_method = 'GET', $return_data = true, $ip_port = null)
    {
        unset($params['token']);
        $params['token'] = $this->createToken($params);

        $url = $ip_port ? $this->targetHttp . $ip_port . $this->targetFile : $this->url;

        $response_body = http($url, $params, $request_method);

        if (I('post.debug') == 1) {
            $log_str = json_encode($params);
            //记录日志
            M('Gmlog')->add([
                'uid'       => 0,
                'toid'      => 0,
                'username'  => 'GAME_API',
                'operation' => "发送请求<br>URL:{$url}<br>方式:{$request_method}<br>发送参数:{$log_str}<br>接收参数:{$response_body}",
                'client_ip' => get_client_ip(),
                'dateline'  => time(),
            ]);
        }

        $response_arr = json_decode($response_body, true);
        if (isset($response_arr['code']) && $response_arr['code'] == API_STATE_SUCCESS) {
            if ($return_data) {
                return isset($response_arr['data']) ? $response_arr['data'] : false;
            }
            return true;
        }
        if (isset($response_arr['msg'])) {
            $this->ErrMsg = $response_arr['msg'];
        }
        return false;
    }

    /**
     * librate创建token
     *
     * @access public
     * @param  array  &$params
     * @return void
     */
    private function createToken(array &$params = array())
    {
        $params['time'] = isset($params['time']) ? $params['time'] : time();
        ksort($params);
        return md5(json_encode($params, JSON_NUMERIC_CHECK) . $this->key);
    }


    //battlespace创建签名
    private function createSign(array &$params = []) {
        ksort($params['data']);
        $params['data']['token'] =  md5(json_encode($params['data'], JSON_NUMERIC_CHECK) . $this->key);
    }
}
