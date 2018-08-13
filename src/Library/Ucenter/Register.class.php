<?php
/**
 * 用户中心 - 注册
 * author:Tonly
 * date: 20160511
 * */
namespace Ucenter;

use Ucenter\Library\Common;


class Register extends Common{

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
     * 注册 - 官网注册
     * params:
     *  account     string  帐号
     *  password    string  密码
     *  ads_key     string  广告标识
     *  ip          string  用户IP
     * return:
     * */
    public function register( array $params ){
        $url = $this->url . '/member/register';
        if( $params = $this->curl_http($url, $params) ){
            if( $params['code'] == 0 ){//把用户中主Token保存cookie中
                $this->setToken($params['data']['token']);
            }
        }
        return $params;
    }

}
