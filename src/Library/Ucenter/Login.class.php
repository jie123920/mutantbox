<?php
/**
 * 用户中心 - 登录
 * author:Tonly
 * date: 20160511
 * */
namespace Ucenter;

use Ucenter\Library\Common;
use app\Library\Mlog;

class Login extends Common{

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
     * 登录 - 官网登录
     * params:
     *  account     string  帐号
     *  password    string  密码
     * return:
     * */
    public function loginGW($account, $password,$remeber_time=''){
        $url = $this->url . '/member/login';
        $mlog = new Mlog();
        $mlog->inlog('login', 'logingw_url', $url);

        $params = array('account' => $account, 'password' => $password,'remember_me'=>$remeber_time);
        if( $result = $this->curl_http($url, $params) ){
            if( $result['code'] == 0 ){//把用户中主Token保存cookie中s
                $this->setToken($result['data']['token']);//加密COOKIE
                $remember_me_token = isset($result['data']['remember_me_token'])?$result['data']['remember_me_token']:'';
                $remember_me_expire = isset($result['data']['remember_me_token'])?$result['data']['remember_me_expire']:0;
                $this->setEncodeCookie('remember_me_token',$remember_me_token,$remember_me_expire+time());//保存记住我到加密COOKIE
            }
        }
        return $result;
    }


    /*
     * 登录 - FB登录
     * params:
     *  token     string  FB登录token
     *  app_type    string  APP标识   默认mutantbox,游戏liberators
     * return:
     * */
    public function loginFB($token, $app_type='mutantbox', $ads_key = ''){
        $url = $this->url . '/fb/login';
        $ip = \Yii::$app->request->userIP;
        $params = array('token' => $token, 'app_type' => $app_type, 'ads_key' => $ads_key, 'ip' => $ip);
        if( $params = $this->curl_http($url, $params) ){
            if( $params['code'] == 0 ){//把用户中主Token保存cookie中
                $this->setToken($params['data']['token']);
            }
        }
        return $params;
    }


    /*
     * 登录 - Google登录
     * params:
     *  code     string  Google登录code
     * return:
     * */
    public function loginGG($code, $app_type='mutantbox'){
        $url = $this->url . '/gg/login';
        $ip = \Yii::$app->request->userIP;
        $params = array('code' => $code, 'app_type' => $app_type, 'ip' => $ip,'protocol'=>$_SERVER['SERVER_PORT']==443 ? 'https' : 'http');
        if( $params = $this->curl_http($url, $params) ){
            if( $params['code'] == 0 ){//把用户中主Token保存cookie中
                $this->setToken($params['data']['token']);
            }
        }
        return $params;
    }

}
