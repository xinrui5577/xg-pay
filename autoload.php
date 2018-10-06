<?php

function classLoader($class)
{
    echo $class;
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $path = str_replace('Payment' . DIRECTORY_SEPARATOR, '', $path);

    $file = __DIR__ . '/src/' . $path . '.php';
    echo $file;
    die('gg');
    if (file_exists($file)) {
        require_once $file;
    }
}
spl_autoload_register('classLoader');
