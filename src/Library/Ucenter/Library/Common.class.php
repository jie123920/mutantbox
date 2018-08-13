<?php
/**
 * 用户中心
 * author:Tonly
 * date: 20160504
 * error: 150 - 159
 * */
namespace Ucenter\Library;

abstract class Common{
    private $baseurl = array(
        'dev' => 'http://ucenter.movemama.com',
        'qa' => 'http://testucenter.movemama.com',
        'prod' => 'http://ucenter.mutantbox.com',
    );
    public $env = 'prod';
    public $url = null;
    public $domain = 'mutantbox.com';
    public $expire = 86400;    //1days


    /*
     * 构造函数
     * params:
     *  $url    string
     *  $domain string
     *  $expire int
     *  $env    string
     * */
    public function __construct( array $params=array() ){
       $tmphosts = explode('.', $_SERVER['HTTP_HOST']);
        unset($tmphosts[0]);
        $domain = $this->domain = implode('.', $tmphosts);
        if ( in_array( $domain, ['movemama.com', 'mutantbox.com'] ) ) {
                $this->baseurl = array(
                        'dev' => 'http://ucenter.' . $domain,
                        'qa' => 'http://testucenter.' . $domain,
                        'prod' => 'http://ucenter.' . $domain,
                );
        } else {
                $this->baseurl = array(
                        'dev' => 'http://devucenter.' . $domain,
                        'qa' => 'http://testucenter.' . $domain,
                        'prod' => 'http://ucenter.' . $domain,
                );

        }

        if( isset($params['domain']) ){
            $this->domain = $params['domain'];
        }
        if( isset($params['expire']) ){
            $this->expire = $params['expire'];
        }
        if( isset($params['env']) && isset($this->baseurl[$params['env']]) ){
            $this->env = $params['env'];
        }
        $this->url = $this->baseurl[$this->env];
    }


    /*
     * 保存Token
     * */
    public function setToken($token, $expire=0){
        if( $expire === 0 ){
            $expire = $this->expire + time();
        }
        return Cookie::getInstance()->setCookie(array('token'=>$token), $expire, $this->domain);
    }

    /*
     * 删除cookie
     * */
    public function cookieDel(){
        return Cookie::getInstance()->delCookie($this->domain);
    }

    /*
     * 获取Token
     * */
    public function getToken(){
        if( $cookie = Cookie::getInstance()->getCookie() ){
            if( isset($cookie['token']) ){
                return $cookie['token'];
            }
        }
        return null;
    }

    /*
     * 获取Cookie
     * */
    public function getCookeValue($key){
        if( $cookie = Cookie::getInstance()->getCookie() ){
            if( isset($cookie[$key]) ){
                return $cookie[$key];
            }
        }
        return null;
    }

    /*
     * 公共方法
     * */
    public function curl_http($url, array $params, $method='POST'){
        //构造参数
        $argv = http_build_query($params);
        if( $method === 'GET' ){
            $url .= '?' . $argv;
        }

        //curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	//Follow 301 redirects
        curl_setopt($ch, CURLMOPT_PIPELINING, 0);	   //启用管道模式
        if( $method === 'POST' ){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $argv);
        }
        $return = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if( $errno ){
            return Errorlog::write(150, $errno.'<|>'.$error.'<|>'.$url);
        }
        $return = json_decode($return, true);
        if( true === is_array($return) ){
            if( isset($return['code'])&&$return['code']){
                Errorlog::write(151, json_encode($return).'<|>'.$argv.'<|>'.$url);
            }
            return $return;
        }
        return Errorlog::write(152, $return.'<|>'.$url);
    }

    /*
     * 保存Token
     * */
    public function setEncodeCookie($key,$v,$expire=0){
        if( $expire === 0 ){
            $expire = $this->expire + time();
        }
        return Cookie::getInstance()->setEncodeCookie($key,$v, $expire, $this->domain);
    }

    /*
     * 获取Token
     * */
    public function getEncodeCookie($key){
        if( $cookie = Cookie::getInstance()->getEncodeCookie($key) ){
            return $cookie;
        }
        return null;
    }
}
