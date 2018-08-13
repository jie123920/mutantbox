<?php
spl_autoload_register(function ($class) {
    $prefix = 'Ucenter\\';
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;
    
	$len = strlen($prefix);
    if( strncmp($prefix, $class, $len) !== 0 ){
        return null;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.class.php';
    if( file_exists($file) ){
        require $file;
    }
});
