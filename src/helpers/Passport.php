<?php

namespace app\helpers;
use Yii;
Class Passport {
    
    /**
     * 通过接口获取服务器信息
     *2016年6月6日 下午2:52:31
     * @param unknown $serverid
     * @param string $param
     * @return string|unknown
     */
    public static function getserverinfo($serverid, $param = NULL){
        $data = '';
    
        $sapi = Yii::$app->params['MY_URL']['SAPI'];
        $url = $sapi."/index.php/server/listbyserver";
        $params = array(
            'server_id' => $serverid,
        );
    
        $returnjson = http($url, $params, 'GET');
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
     * 获取游戏及服务器列表
     * @return mixed
     */
    public static function getlist($type = 'iswhiteuser'){
        $gameurl = Yii::$app->params['MY_URL']['GAME'];
        $url = $gameurl.'index.php?s=Api/getGameInfo'; //获取游戏及服务器列表
        
        $key = Yii::$app->params['TOKEN']['PASSKEY'];
        $token = md5($key);
        $envid = Yii::$app->params['ENV_TYPE'];
        $params = array(
            'token' => $token,
            'envid'=>$envid,
        );
        
        $data = http($url, $params,'POST');
        $data = json_decode($data, true);
        $data = $data['data'];

        $user_auth = cookie('user_auth');
        $uid = $user_auth['uid'];

        /**
         * 支付服务器支付开启白名单
         */
        $return = array();
        foreach ($data[0]['server'] as $key=>$value){            
            if ($type == 'iswhiteuser'){
                $iswhiteuser = self::iswhiteuser2($value['server_id'], $uid);
                if (!$iswhiteuser){
                    unset($data[0]['server'][$key]);
                }                    
            }else {
                $iswhiteuser = self::iswhiteuser($value['server_id'], $uid);
                $ispay = $value['ispay'];
                if (!$iswhiteuser || !$ispay){
                    unset($data[0]['server'][$key]);
                }
            }
                      
        }
        $data[0]['server'] = array_values($data[0]['server']);
        return $data;
    }
    
    /**
     * 如果服务器开启白名单，查询id是否允许支付
     *2016年2月24日 下午6:50:16
     */
    public static function iswhiteuser($serverid, $uid, $gameid=2){
        $url = Yii::$app->params['MY_URL']['GAME'].'/index.php?s=api/iswhiteuser';
        $key = Yii::$app->params['TOKEN']['PASSKEY'];
        $envid = Yii::$app->params['ENV_TYPE'];
    
        $params = array(
            'envid' => $envid,
            'serverid' => $serverid,
            'uid' => $uid,
            'token' => md5($key),
        );
    
        $data = http($url, $params, 'post');
        $data = json_decode($data, true);
        return $data['data'];
    }
    
    /**
     * 如果服务器开启白名单，查询id是否允许支付
     *2016年2月24日 下午6:50:16
     */
    public static function iswhiteuser2($serverid, $uid, $gameid=2){
        $url = Yii::$app->params['MY_URL']['GAME'].'/index.php?s=api/iswhiteuser2';
        $key = Yii::$app->params['TOKEN']['PASSKEY'];
        $envid = Yii::$app->params['ENV_TYPE'];
    
        $params = array(
            'envid' => $envid,
            'serverid' => $serverid,
            'uid' => $uid,
            'token' => md5($key),
        );
    
        $data = http($url, $params, 'post');
        $data = json_decode($data, true);
        return $data['data'];
    }
    
    /**
     * 获取人员信息
     * @param unknown $uid
     */
    public static function getpassinfo($uid, $type = ''){
        $gameurl = Yii::$app->params['MY_URL']['GAME'];
        $url = $gameurl.'index.php?s=Api/getUserInfo'; //获取人员信息
        $key = Yii::$app->params['TOKEN']['PASSKEY'];
        $token = md5($key);
        $params = array(
            'token' => $token,
            'gw_uid' =>$uid,
        );
        
        $data = http($url, $params,'POST');
        $return = json_decode($data);
        
        if ($type == 'serverid'){
            if ($return->data){
                $data = $return->data;
                if ($data->last_server){
                    $serverid = $data->last_server;
                    return $serverid;
                }else {
                    return false;
                }                    
            }else {
                return false;
            }
                
        }else {
            $platform = $return->data->platform;
            
            if ($platform == 'facebook')
                $platform = 'fb';
            return $platform;
        }
        
    }
    
    /**
     * 根据cookie信息获取人员来源信息（fb、gg等）
     * @param unknown $uid
     */
    public static function getuserinfo(){
        $user_auth = cookie('user_auth');
        $data['uid'] = $user_auth['uid'];
        $data['username'] = $user_auth['username'];
        $data['email'] = $user_auth['email'];
        $data['headimg'] = $user_auth['thumb_avatar'];
        $gw_param = base_encode(json_encode($data));
        
        $gameurl = Yii::$app->params['MY_URL']['GAME'];
        $url = $gameurl.'index.php?s=Api/getInfoByEmail'; //获取人员信息
        $key = Yii::$app->params['TOKEN']['PASSKEY'];
        $token = md5($key);
        $params = array(
            'token' => $token,
            'userinfo' => $gw_param,
        );

        $data = http($url, $params,'POST');
        $return = json_decode($data,true);
        return $return['data'];
    }
    
}  