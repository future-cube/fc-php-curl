<?php
require __DIR__ . '/../vendor/autoload.php';

use FutureCube\FcPhpCurl\FcCurl;

echo PHP_EOL, PHP_EOL, 'start >>>>>', PHP_EOL;

$url = 'https://www.baidu.com';
$curl = new FcCurl($url);
$curl->setMethod('POST');
$curl->setFollowLocation(true);
$curl->setFollowLocationMax(5);
$curl->setCookieFile(__DIR__ . '/cookie.txt');
$curl->setCookieJar(__DIR__ . '/cookie.txt');

//$curl->setReturnTransfer(true);
//$curl->setReturnHeaderOut(false);
//$curl->setReturnHeader(true);
//$curl->setReturnBody(false);

$header = $curl->getRequestHeaders(false);
echo "request header:" . $header;
echo '<<<< end', PHP_EOL;

echo PHP_EOL, PHP_EOL, 'start >>>>>', PHP_EOL;
$header = $curl->getResponseHeaders(false);
echo "request header:" . $header;
echo '<<<< end', PHP_EOL;

$content = $curl->execute();
