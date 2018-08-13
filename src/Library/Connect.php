<?php
/**
 * 游戏接入通用方法
 * author:Tonly
 * date: 20160509
 * error: 100 - 109
 * */

class Connect {
    private $SECRET_KEY = 'mutantbox#game@play%tonl*20160421';
    const OPEN_SERVER = 'http://sapi.mutantbox.online/index.php';


    //生产安全KEY
    public function makeSecret($pf, $uid='', $gid='', $sid='', $secret_key=''){
        if( $secret_key ){
            $this->SECRET_KEY = $secret_key;
        }
        $secret['time'] = time();
        $secret['token'] = $this->maketoken($this->SECRET_KEY . '|' . $pf .'|' . $uid .'|' . $gid .'|' . $sid .'|' . $secret['time']);
        return $secret;
    }

    //验证
    public function verify_time($pf, $uid, $gid, $sid, $public_key, $secret_key=''){
        if( $secret_key ){
            $this->SECRET_KEY = $secret_key;
        }
        $offset = time() - $public_key;
        if( $offset > 30 ){//30s 失效
            return false;
        }
        return $this->maketoken($this->SECRET_KEY . '|'. $pf .'|' . $uid .'|' . $gid .'|' . $sid .'|' . $public_key);
    }

    //生成验证码
    private function maketoken($str){
        return md5($str);
    }

    //调用API
    public function server_web_api($url, array $params=array(), $method='GET'){
        return $this->curl_http($url, $params, $method);
    }



    //取得服务器配置信息
    public function get_server_open($gid, $sid){
        $url = self::OPEN_SERVER . '/server/listbyserver/server_id/' . $sid;
        return $this->curl_http($url, array(), 'GET');
    }

    //取得服务器配置信息 - 广告用户
    function get_server_ads_open($gid, $ip){
        $url = self::OPEN_SERVER . '/area/index/server_id/'.$gid.'/user_ip/'.$ip;
        return $this->curl_http($url, array(), 'GET');
    }

    //区服列表
    public function get_server_list_open($gid){
        $url = self::OPEN_SERVER . '/server/listbygame/game_id/' . $gid;
        return $this->curl_http($url, array(), 'GET');
    }


    /**
     * 创建游戏token
     */
    public function makeGameToken($oid, $username, $pf, $serverId, $privateKey){
        $prefix = $pf==='facebook' ? 'fb' : 'gw';

        $game_id  = 4;
        //$oid      = $prefix.'_'.$oid;
        $platform = $pf;
        $time     = time();

        $verify = md5($oid . $platform . $game_id . $serverId . $username . 'Uploads/UserAvatar/' . $time . $privateKey);
        $token = base64_encode($oid.'lllll'.$platform.'lllll'.$game_id.'lllll'.$serverId.'lllll'.$username.'lllll'.'Uploads/UserAvatar/'.'lllll'.$time.'lllll'.$verify);

        $token = str_replace(['/', '&', '+', '%', '=', '#', '(', ')', '-'], ['|a|', '|b|', '|c|', '|d|', '|e|', '|f|', '|g|', '|h|', '|i|'], $token);
        return $token;
    }

    /**
     * 创建游戏token
     */
    public function makeGameTokenLegacy($oid, $username, $pf, $serverId, $regpf ,$privateKey){
        $prefix = $pf==='facebook' ? 'fb' : 'gw';

        $game_id  = 4;
        //$oid      = $prefix.'_'.$oid;
        $platform = $pf;
        $time     = time();

        $verify = md5($oid . $platform . $game_id . $serverId . $username . 'Uploads/UserAvatar/' . $time . $regpf. $privateKey);
        $token = base64_encode($oid.'lllll'.$platform.'lllll'.$game_id.'lllll'.$serverId.'lllll'.$username.'lllll'.'Uploads/UserAvatar/'.'lllll'.$time.'lllll'.$regpf.'lllll'.$verify);

        $token = str_replace(['/', '&', '+', '%', '=', '#', '(', ')', '-'], ['|a|', '|b|', '|c|', '|d|', '|e|', '|f|', '|g|', '|h|', '|i|'], $token);
        return $token;
    }


    public function curl_http($url, array $params, $method='POST'){
        $argv = http_build_query($params);
        if( $method === 'GET' ){
            $url .= '?' . $argv;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	//Follow 301 redirects
        curl_setopt($ch, CURLMOPT_PIPELINING, 0);	//启用管道模式
        if( $method === 'POST' ){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $argv);
        }
        $return = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if( $errno ){
            return \Errors::write(100, $errno.'_'.$error.'_'.$url);
        }
        $return = json_decode($return, true);
        if( is_array($return) ){
            return $return;
        }
        return \Errors::write(101, (string)$return . ' _result is not array_'.$url);
    }

}
