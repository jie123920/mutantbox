<?php
//访问协议
define('PROTOCOL', $_SERVER['SERVER_PORT']==443 ? 'https' : 'http');

//定义回调URL通用的URL
define('URL_CALLBACK', PROTOCOL.'://www.movemama.com/login/');
//主域
define('DOMAIN', 'movemama.com');
//接入公共页
define('CONNECTURL', 'http://play.movemama.com/public');
define('CONNECTURL2', PROTOCOL.'://play.movemama.com/public');
//环境配置
define('ENVUC', 'dev');

//UcenterUrl配置
$tmphosts = explode('.', $_SERVER['HTTP_HOST']);
unset($tmphosts[0]);
$tmphosts = implode('.', $tmphosts);
if ( in_array($tmphosts, ['movemama.com', 'mutantbox.com'] ) ) {
        define('UCENTER_URL', PROTOCOL.'://ucenter.' . $tmphosts . '/');
} else {
        define('UCENTER_URL', PROTOCOL.'://devucenter.' . $tmphosts . '/');
}
unset($tmphosts);

//时间戳
define('NOWTIME', time());
//私钥
define('KEY_VERIFY', 'mutantbox@gameconnect*20160509#Tonly&');
//STATIC URL
define('STATICURL', '/assets/src');
//版本号
define('VERSION', '20160622');
//Cookie Key
define('COOKIE_KEY', '_fbttl');

//所有日志的路径
define('LOG_PATH', "/data/logs/");

// 官网二级域名
define('SLD_WEB', "www");
// 游戏二级域名
define('SLD_LIBERATOR', "liberators");
// 论坛二级域名
define('SLD_FORUM', "forum");

define('WEB_URL',PROTOCOL.'://www.movemama.com');

//图片上传根目录
define('UPLOAD_IMAGE_FILE_URL', '/Uploads/images/');
define('UPLOAD_IMAGE_FILE', dirname(dirname(__DIR__)) .'/web'. UPLOAD_IMAGE_FILE_URL);
//构建目录名称
define('GULP', 'src');
define('CDN_URL', '/Public/src');
define('UPLOAD_CDN_URL', '//testcdn.movemama.com/00/03/uploads');
define('CDN_NEW_URL', '//cdn.mutantbox.com/00/03/testuploads');
define('__SELF__', strtolower(strip_tags($_SERVER['REQUEST_URI'])));
define('DEFAULT_AVATAR','https://cdn-image.mutantbox.com/201712/174855335576ea7b1f0ae9e36548bfd6.png');
// https://cdn-image.mutantbox.com/201711/2bc15e414e7369699ceb0d0ebb2e8c77.png





return [
    'COOKIE_DOMAIN' => DOMAIN,
    'adminEmail'          => 'admin@mutantbox.com',
    //返回数据格式
    'result'              => ['code' => 0, 'error' => [], 'data' => []],
    'pay'                 => 'http://pay.movemama.com',
    'play'                => 'http://play.movemama.com',
    'LOGURL2'             => '52.197.52.137',
    'LOGPORT2'            => '5517',
    'LOGURL' => '133.130.90.180',
    'LOGPORT' => '8889',
    //url 设置
    'MY_URL'=>array(
        'FORUM'=>PROTOCOL.'://forum.movemama.com/',
        'GAME'=>PROTOCOL.'://game.movemama.com/',
        'WEB'=>PROTOCOL.'://www.movemama.com/',
        'PAY'  => PROTOCOL.'://mpay.movemama.com',
        'PAYS' => 'https://mpay.movemama.com',
        'LIBERATORS' => PROTOCOL.'://liberators.movemama.com',
        'UCENTER' => PROTOCOL.'://ucenter.movemama.com',
        'SAPI' => 'http://sapi.movemama.com',
        'OPS' => 'http://ops.movemama.com',
        'EMAIL'=>'http://127.0.0.1:18000',
        'SurvivorLegacy'=>PROTOCOL.'://survivorlegacy.movemama.com',
        'ShopPay' => PROTOCOL.'://devpay.clothesforever.com',
        'ShopPay_2' => PROTOCOL.'://devpay.lovecrunch.com',
        'SHOP'=> PROTOCOL.'://devshop.clothesforever.com',
        'CF'=> PROTOCOL.'://devwww.clothesforever.com',
        'BATTLESPACE' => PROTOCOL.'://battlespace.movemama.com',
        'HERO' => PROTOCOL.'://cryptoconquerors.movemama.com',
        'WALLET' => 'http://dev-wallet.cryptoconquerors.com',
        'BLOCKCHAIN' => PROTOCOL.'://blockchain.movemama.com',

    ),
    //key
    'MY_KEY'=>array(
        'GAMES_KEY'=>'Liberators123!@#',
    ),
    //环境类型 1:Online 2:QA 3:Develop
    'ENV_TYPE' => 3,

    //游戏配置 game_id=>array()
    'GAME_CONFIG' => array(
        '4' => array(),
        '9' => array()
    ),
    'RSYNC_CDN_ADDRESS' => array(
        '52.86.232.220::uploads.testcdn.movemama.com',
        '34.198.155.213::uploads.testcdn.movemama.com',
    ),
    'TOKEN' => array(
        'KEY'=>'uow)*^$@!#%&456kj',
        'ucentkey' => '1Fq9uZj9JeJPuje2',
        'PASSKEY' =>'Liberators123!@#',
        'email_token'=>'c227a43454a2fcac3fbb0d9ce8d8cfa7',
        'shop' => 'rZ2Xj7Q77Tv1lKvZ',
        'queue' => '4ZWrfeG2FEl6Llzu',
        'WALLET' => 'svggzOoYRw8KJxWr',
    ),

    'allowed_server_type' => [
        31 => 31, // 正式服
        // 41 => 41, // 永测服
        // 42 => 42, // 外网测试服
        // 43 => 43, // 内网测试服
        // 44 => 44, // QA测试服
    ],
    'PLATFORM_NUM_GW'    => 3,
    'PLATFORM_NUM_FB'    => 4,
    /* 文章封面原图片上传相关配置 */
    'ARTICLEPICTURE_UPLOAD' => array(
        'mimes' => '', //允许上传的文件MiMe类型
        'maxSize' => 2 * 1024 * 1024, //上传的文件大小限制 (0-不做限制)
        'exts' => 'jpg,gif,png,jpeg', //允许上传的文件后缀
        'autoSub' => true, //自动子目录保存文件
        'subName' => array('date', 'Ym'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Uploads/images/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt' => '', //文件保存后缀，空则使用原后缀
        'replace' => false, //存在同名是否覆盖
        'hash' => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ),
    'language' => [
        'en-us' => '1',
        'fr-fr' => '2',
        'de-de' => '3',
        'es-es' => '4',
        //         '5' => 'ch-ch',
        'pt-pt' => '6',
        'ar-ar' => '7',
        'el-el' => '8',
        'tr-tr' => '9',
        'pl-pl' => '10',
        //         '11' => 'cs-cs',
    //         '12' => 'it-it',
    //         '13' => 'hu-hu',
        'ro-ro' => '14',
    ],

    //图片服务
    'image_server_app_id'     => '782937352627475',
    'image_server_secret_key' => 'c227a43454a2fcac3fbb0d9ce8d8cfa7',
    'image_server_host'       => 'http://devimage.movemama.com',
    'image_server_version'    => 'v1',
];
