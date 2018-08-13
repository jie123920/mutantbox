<?php
define('__STATIC__',CDN_URL.'/static');
define('__JS__',CDN_URL.'/Home/js');
define('__IMG__',CDN_URL.'/Home/images');
define('__CSS__',CDN_URL.'/Home/css');
define('__AVATARS__',CDN_URL.'/Common/images/UserAvatar');
$_config = [
     'id'                  => 'home',
    'language'=>'en-us',
    'components'          => [
        'i18n' => [
            'class'        => 'yii\i18n\I18N',
            'translations' => [
                '*' => [
                    'class'    => 'yii\i18n\PhpMessageSource',
                    'basePath' => dirname(__DIR__).'/languages/',
                    'forceTranslation'=>true
                ],
            ],
        ],
    ],
];

return $_config;