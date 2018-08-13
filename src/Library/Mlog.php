<?php
namespace app\Library;
/**
 * 日志发送格式，需严格按照文档走；无内容字段请空着
 * upd@NO1
 */
class Mlog{
	
	private $ip = ''; //默认 udp 服务器 ip
	private $port = '';//默认端口
	private $rid = '';
	private $version = 2;
	private $sock = NULL;
	
	public function __construct(){
	    $this->ip = \Yii::$app->params['LOGURL2'];
	    $this->port = \Yii::$app->params['LOGPORT2'];
	    $this->rid = 1;	    
	    
		if(extension_loaded('sockets')){
			
			$this->ip = ($this->ip!='' && filter_var($this->ip, FILTER_VALIDATE_IP))?$this->ip:"";//(return "ip不能为空！");
			$this->port = ($this->port !='' && is_numeric($this->port))?$this->port:"";//(return "端口不能为空！");
			$this->rid = ($this->rid !='' && is_numeric($this->rid))?$this->rid:"";//(return "资源id不能为空！");
			$this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		}else{
			
			echo "socket：无法创建";
		}
	}
	
	/**
	 * $sendMsg 为日志内容，字段间以 ^ 分隔；
	 * $type 为日志类型（login、pay）
	 */
	public function Send($sendMsg = '',$type="login"){
	    $sendMsg = implode('^', $sendMsg);
	
		if($this->ip == '' || $this->port == '' || $this->rid == ''){return "不能发送，ip/端口/资源id不能为空！";}
		if($this->sock){
			$data = array();	 
			$sendMsg = $this->version."^".substr($type,0,6)."^".$sendMsg."^".date_default_timezone_get();
			
			$this->inlog('mlog', 'send', $sendMsg);
			
			$data[] = pack("N",strlen($sendMsg)+20);//包长度 4byte 			
			$data[] = pack("a6", $type);//6byte	日志类型
			$data[] = pack("S", $this->version);//2byte udp版本号
			$data[] = pack("N", $this->rid); //gameid 4byte 
			$data[] = pack("N", time());//4byte 时间戳	  
			$data[] = pack("a".strlen($sendMsg),$sendMsg);//内容转二进制
			$output = implode(NULL, $data);
			
			$len = strlen($output);
			$result = socket_sendto($this->sock, $output, $len, 0, $this->ip, $this->port);
			
			return $result; 
		}else{
			echo "socket为空";
		}
	}
	
	public function inlog($channel, $action, $data){
	    if (YII_DEBUG){
	        $str = is_array($data)?json_encode($data):$data;
	        file_put_contents(\Yii::$app->getRuntimePath().'/mlog'.date('Ymd').'.log', date('Y-m-d H:i:s').'::'.$channel.'::'.$action.'::'.$str."\n", FILE_APPEND);
	    }
	}
	
	public function __destruct (){
		
		socket_close($this->sock);
	}
}
