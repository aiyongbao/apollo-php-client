#!/usr/bin/env /usr/local/webserver/php/bin/php
<?php
require_once("ApolloClient.php");
use Org\Multilinguals\Apollo\Client\ApolloClient;

define('SAVE_DIR', '/data/conf'); //定义apollo配置本地化存储路径

//指定env模板和文件
define('ENV_DIR', SAVE_DIR.DIRECTORY_SEPARATOR.'env/');

//指定apollo的服务地址
$server = 'http://meta.apollo.jst.aiyongbao.com:8080';

$arguments = array();
foreach ($argv as $k => $arg) {
    if ($k == 1) {
        $arguments['appid'] = $arg;
    } elseif ($k == 2) {
        $arguments['namespaces'] = $arg;
    } elseif ($k >= 3) {
        $arguments[] = $arg;
    }
}

if(empty($arguments['namespaces'])){
	$arguments['namespaces'] = "application";
}

//指定appid
$appid = $arguments['appid'];

//指定要拉取哪些namespace的配置
$namespaces = explode(",",$arguments['namespaces']);

//定义apollo配置变更时的回调函数，动态异步更新.env
$callback = function () {
    // $list = glob(SAVE_DIR.DIRECTORY_SEPARATOR.'apolloConfig.*');
    // $apollo = [];
    // foreach ($list as $l) {
    //     $config = require $l;
    //     if (is_array($config) && isset($config['configurations'])) {
    //         $apollo = array_merge($apollo, $config['configurations']);
    //     }
	// }
	// echo "1111";
    // if (!$apollo) {
    //     throw new Exception('Load Apollo Config Failed, no config available');
    // }
    // ob_start();
    // $env_config = ob_get_contents();
    // ob_end_clean();
    // file_put_contents(ENV_DIR . $appid.".properties", $env_config);
};

$apollo = new ApolloClient($server, $appid, $namespaces);

//如果需要灰度发布，指定clientIp
/*
 * $clientIp = '10.160.2.131';
 * if (isset($clientIp) && filter_var($clientIp, FILTER_VALIDATE_IP)) {
 *    $apollo->setClientIp($clientIp);
 * }
 */

//从apollo上拉取的配置默认保存在脚本目录，可自行设置保存目录
$apollo->save_dir = SAVE_DIR;

ini_set('memory_limit','128M');
$pid = getmypid();
echo "start [$pid]\n";
$restart = false; //失败自动重启
do {
    $error = $apollo->start($callback); //此处传入回调
    if ($error) echo('error:'.$error."\n");
}while($error && $restart);
