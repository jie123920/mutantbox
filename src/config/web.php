<?php
$params = require(__DIR__ . '/' . YII_ENV . '/params.php');
//引入自定义类库
Yii::$classMap['Connect'] = '@app/Library/Connect.php';
Yii::$classMap['Errors'] = '@app/Library/Errors.php';
Yii::$classMap['Userinfo'] = '@app/Library/Userinfo.php';

$params['lang'] = [
    'en-us'=>'English',//英语
    'fr-fr'=>'Français',//法语
    'de-de'=>'Deutsch',//德语
    'es-es'=>'Español',//西班牙语
    'pt-pt'=>'Português',//葡萄牙语
    'ar-ar'=>'العَرَبِيَّة‎‎',//阿拉伯语
    'tr-tr'=>'Türkçe',//土耳其语
    'pl-pl'=>'Polski',//波兰语
    'ro-ro'=>'Română',//罗马尼亚语
    'el-el'=>'Ελληνικά',//希腊语
    'it-it'=>'Italiano',//意大利语
    'cs-cs'=>'Čeština',//捷克语
    'hu-hu'=>'Magyar',//匈牙利语
];
//商品风格
$params['STYLE']=[
    0=>'All Style',
    1=>'Active',
    2=>'Artsy',
    3=>'Business',
    4=>'Casual',
    5=>'Elegant',
    6=>'Festival',
    7=>'Party',
    8=>'Preppy',
    9=>'Punk',
    10=>'Urban',
    11=>'Vacation',
];
//FaceBook配置
$params['THINK_SDK_FACEBOOK'] = array(
    'APP_KEY' => '714278052039214',
    'APP_SECRET' => '3c455733f51025630be718fce9ac963d',
    'CALLBACK' => URL_CALLBACK . 'facebook',
);
//Google配置
$params['THINK_SDK_GOOGLE'] = array(
    'APP_KEY' => '455615275123-gnkurafbvjr751fioggsujmrli55ltu8.apps.googleusercontent.com',
    'APP_SECRET' => '1SDu1DEcRhFPeLSsU8zg6dI_',
    'CALLBACK' => PROTOCOL.'://www.mutantbox.com',
);

$config = [
    'id'=>'app',
    'basePath'=>dirname(__DIR__),
    'params' => $params,
    'defaultRoute' => BIND_MODULE.'/index/index',
];

$config['modules'] = [
    'home' => [
        'class' =>'app\modules\home\Module',
    ],
    'liberators'   => [
        'class' => 'app\modules\liberators\Module',
    ],
    'sl'   => [
        'class' => 'app\modules\sl\Module',
    ],
    'api'   => [
        'class' => 'app\modules\api\Module',
    ],
    'debug' => [
        'class'      => 'yii\debug\Module',
        'allowedIPs' => ['127.0.0.1', '::1'],
    ],
];
$config['bootstrap'] = ['log', 'debug'];

$config['components'] = [
    'errorHandler'=>array(
        'errorAction'=>'/'.BIND_MODULE.'/common/empty',
    ),
    'mailer' => [
        'class' => 'yii\swiftmailer\Mailer',
        'viewPath' => '',
        'transport' => [
            'class' => 'Swift_SmtpTransport',
            'host' => 'smtp.mailgun.org',
            'port' => 25,
            'encryption' => 'tls',
            'username' => 'postmaster@mutantboxmail.com',
            'password' => '233fb47265250cb7d8356f2089941433',
        ],
        'messageConfig' => [
            'charset' => 'utf-8',
            'from' => ['noreply@mutantboxmail.com' => 'MutantBox']
        ],
    ],
    'user' => [
        'identityClass' => 'app\models\User',
        'enableAutoLogin' => false,
    ],
    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'targets' => [
            [
                'class' => 'yii\log\FileTarget',
                'levels' => ['error'],
                'logVars'=>[]
            ],
        ],
    ],

    'cache'        => [
        'class' => 'yii\caching\FileCache',
    ],

    'urlManager'       => [
        'class'           => 'yii\web\UrlManager',
        'enablePrettyUrl' => true,
        'showScriptName'  => false,
        'cache'           => false,
        'rules'           => [
            ['class' => 'yii\rest\UrlRule', 'controller' => 'shop/rest', 'except' => ['delete', 'create', 'update']],
            '/contact'=>'/'.BIND_MODULE.'/company/contact',
            '/news'=>'/'.BIND_MODULE.'/article/index',
            '/news/<id:\d+>/<title:[\s|\S]*>' => '/'.BIND_MODULE.'/article/article',
            '/serverid/<server_id:\d+>' =>'/home/play/index',
            '/serverId/<server_id:\d+>' =>'/home/play/index',
            '/support'=>'/'.BIND_MODULE.'/support/index',
            '/community'=>'/'.BIND_MODULE.'/community/index',
            '/termsofuse' => '/'.BIND_MODULE.'/article/termsofuse',
            '/privacypolicy' => '/'.BIND_MODULE.'/article/privacypolicy',
            '/server'=>'/liberators/server/index',
            '/guide'=>'/'.BIND_MODULE.'/article/guide',
            '/404'=>'/'.BIND_MODULE.'/common/empty',
            '/login'=>'/'.BIND_MODULE.'/login/login',
            '/register'=>'/'.BIND_MODULE.'/login/register',
            '/ct-login'=>'/'.BIND_MODULE.'/login/ct-login',
            '/ct-register'=>'/'.BIND_MODULE.'/login/ct-register',



            //兼容老的 URL
            '/termsofuse.html' => '/home/article/termsofuse',
            '/privacypolicy.html' => '/home/article/privacypolicy',
            '/Article/<article:[\s|\S]*>/<id:\d+>.html'=>'/'.BIND_MODULE.'/article/article',
            '/Contact'=>'/home/company/contact',
            '/Support'=>'/home/support/index',
            '/Support.html'=>'/home/support/index',
            '/Community'=>'/home/community/index',
            '/TermsofUse' => '/home/article/termsofuse',
            '/PrivacyPolicy' => '/home/article/privacypolicy',
            '/play'=>'/home/play/index',
            '/Game/<article:[\s|\S]*>.html' =>'/'.BIND_MODULE.'/play/index',
            '<controller>/<action>' => BIND_MODULE.'/<controller>/<action>',
            '<controller>/<action>\/\*' => BIND_MODULE.'/<controller>/<action>',
        ],
    ],
    'request' => [
        'class'               => 'yii\web\Request',
        'cookieValidationKey' => 'V1sUo0zLiK-n42OZxsYO1vRa8Wd4ks6x',
    ],
    'session' => [
        'cookieParams' => ['domain' => '.' . DOMAIN],
    ],
    'db' => require(__DIR__  .  '/' . YII_ENV .  '/db.php'),
    //'db_shop' => require(__DIR__  .  '/' . YII_ENV .  '/db_shop.php'),
    'dbpay'=>require(__DIR__  .  '/' . YII_ENV .  '/dbpay.php'),
];

if (YII_ENV_DEV) {
//     configuration adjustments for 'dev' environment
   $config['bootstrap'][] = 'debug';
   $config['modules']['debug'] = [
       'class' => 'yii\debug\Module',
   ];

   $config['bootstrap'][] = 'gii';
   $config['modules']['gii'] = [
       'class' => 'yii\gii\Module',
   ];
}

return $config;
