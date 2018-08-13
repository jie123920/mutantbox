<?php
/**
 * 用户中心
 * author:Tonly
 * date: 20160510
 * */
namespace Ucenter;


class Ucenter {
    private $_cache = array();
    private $params = array();


    /*
     * 构造函数
     * params:
     *  $url    string
     *  $domain string
     *  $expire int
     * */
    public function __construct( array $params=array() ){
        $this->params = $params;
    }


    /*
     * 注册 - 官网注册
     * */
    public function Register(){
        if( false === isset($this->_cache['Register']) ){
            $this->_cache['Register'] = new Register($this->params);
        }
        return $this->_cache['Register'];
    }

    /*
     * 登录 - 官网登录
     * */
    public function Login(){
        if( false === isset($this->_cache['Login']) ){
            $this->_cache['Login'] = new Login($this->params);
        }
        return $this->_cache['Login'];
    }


    /*
     * 验证用户登录合法性
     * */
    public function Auth(){
        if( false === isset($this->_cache['Auth']) ){
            $this->_cache['Auth'] = new Auth($this->params);
        }
        return $this->_cache['Auth'];
    }

    /*
     * 获取用户信息
     * */
    public function User(){
        if( false === isset($this->_cache['User']) ){
            $this->_cache['User'] = new User($this->params);
        }
        return $this->_cache['User'];
    }

}
