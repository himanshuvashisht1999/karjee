<?php
header('Content-Type: text/plain');

echo 'PHP Version: ' . PHP_VERSION . PHP_EOL;

echo 'GD Extension: ';
echo extension_loaded('gd') ? 'ENABLED' : 'DISABLED';

echo PHP_EOL;

echo 'Imagick Extension: ';
echo extension_loaded('imagick') ? 'ENABLED' : 'DISABLED';