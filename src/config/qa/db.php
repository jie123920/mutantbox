<?php

return [
    'class' => 'yii\db\Connection',
    'tablePrefix' => 'ww2_',


    // common configuration for masters
    'masterConfig' => [
        'username' => 'web_mutantbox_qa',
        'password' => 'FSwFFUAVnP4XXIKW',
        'charset' => 'utf8',
    ],

    // list of master configurations
    'masters' => [
        ['dsn' => 'mysql:host=127.0.0.1;dbname=web'],
    ],

    // common configuration for slaves
    'slaveConfig' => [
        'username' => 'web_mutantbox_qa',
        'password' => 'FSwFFUAVnP4XXIKW',
        'charset' => 'utf8',
    ],

    // list of slave configurations
    'slaves' => [
        ['dsn' => 'mysql:host=127.0.0.1;dbname=web'],
    ],

];
