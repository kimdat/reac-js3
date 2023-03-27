<?php

// Đường dẫn đến file autoload.php của Composer
$composerAutoloadFile = __DIR__ . '/vendor/autoload.php';


// Kiểm tra xem file autoload.php có tồn tại không
if (!file_exists($composerAutoloadFile)) {
    die('Composer autoloader file not found.');
}
// Đăng ký file autoload.php của Composer
require_once $composerAutoloadFile;

// Sử dụng hàm spl_autoload_register() để đăng ký một hàm autoload tùy chỉnh
function myAutoloader($className)
{
    $prefixes = array(
        "Offline\\" => "./OFFLINE/",
        "Online\\" => "./ONLINE/",
        "ManageInventories\\" => "./ManageInventories/",
        // add more namespaces and directories as needed
    );

    foreach ($prefixes as $prefix => $directory) {
        if (strpos($className, $prefix) === 0) {
            $classFile = $directory . str_replace('\\', '/', substr($className, strlen($prefix))) . '.php';

            if (file_exists($classFile)) {

                require $classFile;
                return;
            }
        }
    }
}


spl_autoload_register('myAutoloader');
