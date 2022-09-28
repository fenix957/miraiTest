<?php
/**
 * Running every day
 * Update TimeZone exist date end remove if date expired
 */

namespace Mirai;

use PDO;

require_once(__DIR__ . '/vendor/autoload.php') ;
$config = require __DIR__ . '/config.php';

$needCountDays = 31 * 3;
$currentDayCounter = 0;

$timeManager = new TimezoneGenerator();
while ($currentDayCounter < $needCountDays) {
    echo "Updating data ".$currentDayCounter." of ".$needCountDays.PHP_EOL;
    $DBH = new PDO("mysql:host=" . $config["mysql"]['host'] . ";dbname=" . $config["mysql"]['db'], $config["mysql"]['username'], $config["mysql"]['password']);
    $needUpdateCityListQuery = <<<SQL
SELECT
  city.country_iso3 AS country_iso3,
  city.latitude AS latitude,
  city.longitude AS longitude,
  city.id as id
FROM city
WHERE city.id NOT IN (SELECT
         city.id
       FROM city
         INNER JOIN cityZone
           ON city.id = cityZone.cityId
       WHERE DATE(cityZone.date) = (CURDATE() + INTERVAL $currentDayCounter DAY))
GROUP BY city.id
SQL;
    $stmt = $DBH->query($needUpdateCityListQuery);
    $needUpdateCountry = [];
    while ($row = $stmt->fetch()) {
        $needUpdateCountry[] = $row;
    }
    foreach ($needUpdateCountry as $row) {
        $timeManager->getTimeDataByGeoCurl(
            $row["latitude"], $row["longitude"],
            $row["country_iso3"],
            strtotime("now + $currentDayCounter day"),
            $row['id'],
            true);
    }
    $currentDayCounter++;
    $DBH = null;
}


