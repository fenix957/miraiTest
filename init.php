<?php
/**
 * Init Db
 */

require_once( __DIR__.'/vendor/autoload.php');
$config = require __DIR__ . '/config.php';

/**
 * Connection to db
 */
$DBH = new PDO("mysql:host=".$config["mysql"]['host'].";dbname=". $config["mysql"]['db'], $config["mysql"]['username'], $config["mysql"]['password']);
$query = file_get_contents(APP_DIR . "/data/city.sql");
$query = str_replace("ft_extra",$config["mysql"]['db'],$query);
$stmt = $DBH->query($query);
$stmt->closeCursor();
$count = $stmt->rowCount();
$DBH = null;
Echo "Done";








