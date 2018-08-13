<?php
namespace app\Library;

/**
 * 刘勇负责的游戏端Api.
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
class Game
{
    private $key = ''; // 加密key
    private $url = ''; // 请求url
    private $ErrMsg = '';
    private $CachePrefix = 'GameApiCache';
    public $CacheExpireSec = 3600;

    private $targetFile = '/api.php';
    private $targetHttp = 'http://';

    public function __construct()
    {
        $this->key = 'as..ldiv@hq9!~!43hg';
        // TODO 测试服务器
        // $this->url = 'http://10.0.0.11:8086/api.php';
    }

    /**
     * 获取用户基础信息.
     *
     * @param array $params 查询参数数组
     *
     * @return array
     */
    /**
     * 批量获取用户信息.
     *
     * @param array $params
     *     array(
     *         '192.168.1.1:80' => array(
     *             'oid' => array("fw_100000"),
     *         )
     *     )
     */
    public function getUserInfo(array $params = array())
    {
        $multiCurl = new \app\Library\curl\MultiCurl();

        $data = [];
        $keyArr = [];
        foreach ($params as $ipPort => $param) {
            $param['action'] = 'getUserInfo';
            $tmpServerID = $param['server_id'];
            unset($param['token'], $param['server_id']);
            $param['token'] = $this->createToken($param);
            $curl = $multiCurl->addPost($this->targetHttp.$ipPort.$this->targetFile, $param);
            $keyArr[$curl->id] = $tmpServerID;
        }

        // 拉取服务器信息成功
        $multiCurl->success(function ($instance) use (&$data, $keyArr) {
            $handledData = $this->handleData($instance->rawResponse, true);

            if ($handledData) {
                $data[$keyArr[$instance->id]] = current($handledData);
            }
        });

        // 会阻塞在这里
        $multiCurl->start();

        return $data;
    }

    /**
     * 获得错误信息.
     */
    public function getError()
    {
        return $this->ErrMsg;
    }

    /**
     * 处理数据.
     *
     * @param string $response_body
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
     * 创建token.
     *
     * @param array &$params
     */
    private function createToken(array &$params = array())
    {
        $params['time'] = isset($params['time']) ? $params['time'] : time();
        ksort($params);

        return md5(json_encode($params, JSON_NUMERIC_CHECK).$this->key);
    }
}
