<?php
date_default_timezone_set('UTC');
include 'init_autoloader.php';
putenv('UNITTEST=1');

if(!class_exists('PHPUnit\Framework\TestCase')) {
    include __DIR__.'/travis/patch55.php';
}
