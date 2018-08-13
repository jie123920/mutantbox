<?php
/**
 * 用户中心 - 授权验证
 * author:Tonly
 * date: 20160511
 * */
namespace Ucenter;

use Ucenter\Library\Common;


class Auth extends Common{

    /*
     * 构造函数
     * params:
     *  $url    string
     *  $domain string
     *  $expire int
     * */
    public function __construct( array $params ){
        parent::__construct($params);
    }


    /*
     * 验证用户登录合法性
     * params:
     *  $token     string  登录token
     * return:
     * */
    public function verify($token=null){
        $url = $this->url . '/auth/verify';
        if( null ===$token ){
            if( !$token = $this->getToken() ){
                return false;
            }
        }
        return $this->curl_http($url, array('token'=>$token));
    }

    //校验token
    public function verifyToken($token = null)
    {
        if (null === $token) {
            return false;
        }

        $string = urldecode(base64_decode($token));
        $arr = json_decode($string, true);
        $originSignature = isset($arr['signature']) ? $arr['signature'] : '';
        unset($arr['signature']);

        $signature = md5(json_encode($arr) . 'mutantbox@usercenter*20160413#Tonly&');
        if ($signature == $originSignature) {
            return [
                'uid' => $arr['uid'],
                'account' => $arr['account'],
                'sign' => $signature
            ];
        } else {
            return false;
        }
    }

}
