<?php
/**
 * App configuration
 */

if (!defined('APP_DIR')) {

    define ('APP_DIR', __DIR__);

}

$config = [];
$config["mysql"] = [];
$config["mysql"]["host"] = 'fenixfxv.beget.tech';
$config["mysql"]["port"] = 3306;
$config["mysql"]["username"] = 'fenixfxv_mirai';
$config["mysql"]["password"] = 'fenixfxv.beget.techMirai';
$config["mysql"]["db"] = 'fenixfxv_mirai';

$config['api'] = [];
$config['api']['key']= 'Y9YGXWETZ5FW';
$config['api']["endpoint"] = 'http://api.timezonedb.com/v2.1/';

return $config;

