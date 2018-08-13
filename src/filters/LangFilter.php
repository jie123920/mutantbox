<?php
$langSet = '';
if(isset($_GET['l'])){
    $langSet = $_GET['l'];// url中设置了语言变量
    setcookie('think_language',$langSet,time()+3600,'/',DOMAIN);
}elseif(isset($_COOKIE['think_language'])){// 获取上次用户的选择
    $langSet = $_COOKIE['think_language'];
}elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){// 自动侦测浏览器语言
    preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
    $langSet = $matches[1];
    $langSet = strtolower(explode('-', $langSet)[0]);
    foreach ($config['params']['lang'] as $key => $value) {
        if ($langSet == strtolower(explode('-', $key)[0])) {
            $langSet = $key;
            break;
        }
    }
    setcookie('think_language',$langSet,time()+3600,'/',DOMAIN);
}
if(!array_key_exists($langSet,$config['params']['lang'])) { // 非法语言参数
    $langSet = 'en-us';
}
// 定义当前语言
define('LANG_SET',strtolower($langSet));