<?php

namespace app\Library;

class  SendMessage{
    
    public $type;
    public $data = array();
    public $host;
    public $port;
    
    public function __construct(){
        $this->host = \Yii::$app->params['LOGURL'];
        $this->port = \Yii::$app->params['LOGPORT'];
    }
    
     public function Send($type,$data){
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $bind = socket_connect($socket, $this->host, $this->port);
        
        $login_time = time();
        $str = implode('^', $data);
        $tmp = $type.'^y*.^'. $str;
        
        socket_write($socket, $tmp, strlen($tmp));
        socket_close($socket);
    }
   
}