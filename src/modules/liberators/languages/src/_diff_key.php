<?php
error_reporting(E_ERROR);

foreach ([
    'ar-ar',
    'cs-cs',
    'de-de',
    'el-el',
    'es-es',
    'fr-fr',
    'hu-hu',
    'it-it',
    'pl-pl',
    'pt-pt',
    'ro-ro',
    'tr-tr',
    'en-us',
] as $value) {
    $targetArr = require "{$value}/common.php";
    echo $targetArr['emailNotFound'] . PHP_EOL;
    // $diff_key = array_diff_key($enArr, $targetArr);
    // print_r($diff_key);
}
