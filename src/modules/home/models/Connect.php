<?php
/**
 * 游戏接入通用方法
 * author:Tonly
 * date: 20160421
 * */
namespace app\modules\home\models;

class Connect {
    private $SECRET_KEY = 'mutantbox#game@play%tonl*20160421';

    //生产安全KEY
    public function makeSecret($pf, $uid, $gid, $sid, $secret_key=''){
        if( $secret_key ){
            $this->SECRET_KEY = $secret_key;
        }
        $secret['time'] = NOWTIME;
        $secret['secret'] = $this->maketoken($this->SECRET_KEY . '|' . $pf  .'|' . $uid .'|' . $gid .'|' . $sid .'|' . $secret['time']);

        return $secret;
    }

    //生成验证码
    private function maketoken($str){
        return md5($str);
    }

}
