<?php
/*
 * @COOKIE CLASS
 * @author:tonly
 * @date:2016-05-04
 **/
namespace Ucenter\Library;


class Cookie{
	private static $key = '_ttl';
    private static $instance = null;

	/** 
     * Description:私有化构造函数,防止外界实例化对象 
     */
	private function __construct(){
		//......
	}

	/** 
     * Description:静态方法,单例访问统一入口 
     * @return Singleton：返回应用中的唯一对象实例 
     */
    public static function getInstance(){
		if( null == self::$instance ){
			self::$instance = new Cookie();
		}
		return self::$instance;
	}
	
	
    /**
	 * 设置cookie
	 * @param	array	params	array('openid'=>'jfo1234', 'openkey'=>'49u9sdfj', 'appid'=>1, 'appname'=>'qwoeifj')
	 */
	public function setCookie(array $params, $expire=0, $domain='movemama.com'){
		header('P3P: CP="NOI DEV PSA PSD IVA PVD OTP OUR OTR IND OTC"');

		$value = $this->Encode( json_encode($params) );
		setcookie(self::$key, $value, $expire, '/', $domain);
		$_COOKIE[self::$key] = $value;

		return true;
	}
    
    /**
	 * 读取cookie
	 */
	public function getCookie(){
        $val = array();
        if( isset($_COOKIE[self::$key]) ){
			if( $val = $this->Decode( $_COOKIE[self::$key] ) ){
				$val = json_decode($val, true);
			}
		}

		return $val;
	}

	/**
	 * 删除cookie
	 */
	public function delCookie($domain){
        setcookie(self::$key, null, -3600, '/', $domain);
        unset($_COOKIE[self::$key]);

		return true;
	}//end fun


    /**
     * 设置自定义cookie
     */
    public function setSelfCookie($key, $value, $expire=0, $domain='movemama.com'){
        header('P3P: CP="NOI DEV PSA PSD IVA PVD OTP OUR OTR IND OTC"');
        $value = $this->Encode($value);
        setcookie($key, $value, $expire, '/', $domain);
        $_COOKIE[$key] = $value;
        return true;
    }

    /**
     * 获取自定义cookie
     */
    public function getSelfCookie($key){
        if( isset($_COOKIE[$key]) ){
            if( $val = $this->Decode($_COOKIE[$key]) ){
                return $val;
            }
        }
        return '';
    }

    /**
     * 删除自定义cookie
     */
    public function delSelfCookie($key){
        setcookie($key, null, -3600);
        unset($_COOKIE[$key]);
        return true;
    }


    /**
	 * 加密COOKIE内容
	 **/
	private function Encode( $content ){
		return base64_encode( XteaEncrypt::getInstance()->Encrypt($content) );
	}

	/**
	 * 解密COOKIE内容
	 **/
	private function Decode( $content ){
		if( $ret = XteaEncrypt::getInstance()->Decrypt(base64_decode($content)) ){
			return $ret;
		}
		return false;
	}

    /**
     * 通用加密cookie
     * @param string $key
     * @param string $v
     * @param int $expire
     * @param string $domain
     * @return bool
     */
    public function setEncodeCookie($key,$v, $expire=0, $domain='movemama.com'){
        header('P3P: CP="NOI DEV PSA PSD IVA PVD OTP OUR OTR IND OTC"');
        $value = $this->Encode($v);
        setcookie($key, $value, $expire, '/', $domain);
        $_COOKIE[$key] = $value;
        return true;
    }

    /**
     * 通用读取加密cookie
     */
    public function getEncodeCookie($key){
        $val = '';
        if( isset($_COOKIE[$key]) ){
            $val = $this->Decode( $_COOKIE[$key] );
        }
        return $val;
    }
}//end class