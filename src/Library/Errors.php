<?php
/**
 * 错误处理
 * @author:Tonly
 * @date:20160512
 **/
class Errors{

    private function __construct( array $params=array() ){
        //...
    }

    /**
     * 记录日志
     * params:
     * $errno    string  错误代码
     * $message  string  日志
     * return bool
     */
    public static function write($errno, $message=''){
        return self::wFile($errno, $message);
    }


    //写文件记录日志
    private static function wFile($errno, $message){
        $file = dirname(__DIR__) . '/runtime/error/error_' . date('Y-m-d') . '.log';
        if( !$fp = fopen($file, 'a') ){
            return false;
        }
        if( true === flock($fp, LOCK_EX) ){//进行排它型锁定
            fwrite($fp, $errno . ' | ' . $message . ' | ' . date('Y-m-d H:i:s') . PHP_EOL);
            flock($fp, LOCK_UN); //释放锁定
        }
        fclose($fp);

        return false;
    }


    //写DB记录日志
    private static function wDatabase($errno, $message){
        return false;
    }

}//end class