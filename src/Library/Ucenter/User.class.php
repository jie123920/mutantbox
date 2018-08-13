<?php
/**
 * 用户中心 - 用户信息
 * author:Tonly
 * date: 20160511
 * */
namespace Ucenter;

use app\helpers\myhelper;
use app\modules\home\controllers\CtUcenterController;
use Ucenter\Library\Common;
use app\Library\Mlog;

class User extends Common{

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
     * 获取用户信息
     * params:
     *  $token     string  登录token
     * return:
     * */
    public function userinfo($token=null, $field = ''){
        $url = $this->url . '/member/userinfo';
        if( null === $token ){
            if( !$token = $this->getToken() ){
                return false;
            }
        }
        $params = array('token'=>$token);
        if ((string) $field !== '') {
            $params['field'] = $field;
        }

        return $this->curl_http($url, $params);
    }


    /*
     * 获取fbid转uid信息
     * params:
     *  $token     string  登录token
     * return:
     * */
    public function fb2uid($token=null, $data,$app_id,$field = ''){
        $url = $this->url . '/fb/fb2uid';
        if( null === $token ){
            if( !$token = $this->getToken() ){
                return false;
            }
        }
        $params = array('token'=>$token,'data'=>$data,'app_id'=>$app_id);
        if ((string) $field !== '') {
            $params['field'] = $field;
        }

        return $this->curl_http($url, $params);
    }


    /*
     * 帐号重复
     * params:
     *  $account     string  帐号
     * return:
     * */
    public function account($account){
        $url = $this->url . '/auth/account';
        return $this->curl_http($url, array('account'=>$account));
    }


    /*
     * 退出登录
     * params:
     *  $token     string  登录token
     * return:
     * */
    public function logout($token=null){
        $url = $this->url . '/member/logout';
        if( null === $token ){
            if( !$token = $this->getToken() ){
                return false;
            }
        }
        $this->cookieDel();
        return $this->curl_http($url, array('token'=>$token));
    }

    public function updateuser($token=null, $params = array()){
        $url = $this->url . '/member/updateuser';
        if( null === $token ){
            if( !$token = $this->getToken() ){
                return false;
            }
        }
        $params['token'] = $token;
        return $this->curl_http($url, $params);
    }
    /*
      * 绑定fb
      * params:
      *  $token     string  登录token
      * return:
      * */
    public function bindfb($token = null, $uid = null, $fbtoken,$app_type='mutantbox'){
        $url = $this->url . '/bind/fb';
        if( null === $token ){
            if( !$token = $this->getToken() ){
                return false;
            }
        }
        $params = array(
            'token'=>$token,
            'uid' => $uid,
            'fbtoken' => $fbtoken,
            'app_type'=>$app_type
        );

        return $this->curl_http($url, $params);
    }

    /*
     * 绑定gg
     * params:
     *  $token     string  登录token
     * return:
     * */
    public function bindgg($token = null, $uid = null, $code,$app_type='mutantbox',$redirect_uri =''){
        $url = $this->url . '/bind/gg';
        if( null === $token ){
            if( !$token = $this->getToken() ){
                return false;
            }
        }
        $params = array(
            'token'=>$token,
            'uid' => $uid,
            'code' => $code,
            'app_type'=>$app_type,
            'redirect_uri' => $redirect_uri ? $redirect_uri:\Yii::$app->params['MY_URL']['WEB']
        );

        return $this->curl_http($url, $params);
    }
    /*
     * 获得已绑定的平台
     * params:
     *  $token     string  登录token
     * return:
     * */
    public function getbinded($token = null, $uid = null){
        $url = $this->url . '/bind/getbinded';
        if( null === $token ){
            if( !$token = $this->getToken() ){
                return false;
            }
        }
        $params = array(
            'token'=>$token,
            'uid' => $uid
        );

        return $this->curl_http($url, $params);
    }

    /*
    * 登录 - 官网登录
    */
    public function autoLogin($remember_me_token){
        $url = $this->url . '/member/auto-login';

        $mlog = new Mlog();
        $mlog->inlog('login', 'logingw_url', $url);

        $params = array('remember_me_token' => $remember_me_token);
        if( $result = $this->curl_http($url, $params) ){
            if( $result['code'] == 0 ){//把用户中主Token保存cookie中
                $this->setToken($result['data']['token']);//加密COOKIE
                $remember_me_token = isset($result['data']['remember_me_token'])?$result['data']['remember_me_token']:'';
                $remember_me_expire = isset($result['data']['remember_me_token'])?$result['data']['remember_me_expire']:0;
                $this->setEncodeCookie('remember_me_token',$remember_me_token,$remember_me_expire+time());//保存记住我到加密COOKIE
            }
        }
        return $result;
    }

    
    /*
     * 更新密码
     * params:
     *  $token     string  登录token
     * return:
     * */

    public function updatepwd($token=null, $oldPwd, $newPwd){
        $url = $this->url . '/member/updatepwd';
        if( null === $token ){
            if( !$token = $this->getToken() ){
                return false;
            }
        }
        $params['token'] = $token;
        $params['old_pwd'] = $oldPwd;
        $params['new_pwd'] = $newPwd;
        return $this->curl_http($url, $params);
    }

    //判断是否设置过交易密码
    public function paypwdExist($userData=[])
    {
        $ucenter = \YII::$app->params['MY_URL']['WALLET'];
        $url = $ucenter.'/mapping/pw-exist';
        $params = [
            'project' => 'cryptoterritories',
            'platform' => 'mutantbox',
            'source' => 'player',
            'userid' => $userData['uid'],
            'tmp' => (string)time(),
        ];
        $params['sign'] = $this->genrateSign($params);
        $json = myhelper::http($url, $params, 'POST');
        $data = json_decode($json,true);

        return $data['data'];
    }

    public function genrateSign($params = [])
    {
        $key = \YII::$app->params['TOKEN']['WALLET'];
        sort($params, SORT_STRING);
        $data = json_encode($params).$key;

        return md5($data);
    }
}
