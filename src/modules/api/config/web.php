<?php
define('__STATIC__',CDN_URL.'/static');
define('__JS__',CDN_URL.'/Games/js');
define('__IMG__',CDN_URL.'/Games/images');
define('__CSS__',CDN_URL.'/Games/css');
define('__AVATARS__',CDN_URL.'/Common/images/UserAvatar');
define('__LAYER__',CDN_URL.'/Games/layer');
$_config = [
     'id'                  => 'api',
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