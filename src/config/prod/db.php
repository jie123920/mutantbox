<?php

return [
    'class' => 'yii\db\Connection',
    'tablePrefix' => 'ww2_',

    // common configuration for masters
    'masterConfig' => [
        'username' => 'mutantboxdbm',
        'password' => 'ei#9yR8W*3dDf4',
        'charset' => 'utf8',
    ],

    // list of master configurations
    'masters' => [
        ['dsn' => 'mysql:host=172.31.9.110;dbname=pro_mutantbox_web1'],
    ],

    // common configuration for slaves
    'slaveConfig' => [
        'username' => 'mutantboxdbm',
        'password' => 'ei#9yR8W*3dDf4',
        'charset' => 'utf8',
    ],

    // list of slave configurations
    'slaves' => [
        ['dsn' => 'mysql:host=172.31.9.110;dbname=pro_mutantbox_web1'],
    ],

   // 'serverStatusCache' => 'file_cache',
];
