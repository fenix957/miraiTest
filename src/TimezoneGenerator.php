<?php

namespace Mirai;

use Curl\Curl;
use PDO;
use PDOStatement;
define( 'APP_DIR', __DIR__ . '/../' );

class TimezoneGenerator
{


    private $config;
    private PDO $db;

    public function __construct()
    {
        date_default_timezone_set('UTC');
        $this->config = include APP_DIR . '/config.php';
        $this->db = new PDO("mysql:host=" . $this->config ["mysql"]['host'] . ";dbname=" . $this->config ["mysql"]['db'], $this->config ["mysql"]['username'], $this->config ["mysql"]['password']);
    }

    /** Getting timezone info by external API
     * @param $lat float latitude
     * @param $lon float latitude
     * @param $country string Country
     * @param $utcTimestamp string|int Needed time in UTC
     * @param $cityId string Current city id
     * @param $updateInDb bool Need update data in DB
     * @param $errorCounter int Count retry tries to get data
     * @return false|mixed|null
     */
    public function getTimeDataByGeoCurl( $lat,  $lon,  $country, $utcTimestamp,  $cityId,  $updateInDb = false,  $errorCounter = 5)
    {
        $lat = str_replace(",",".",$lat);
        $lon = str_replace(",",".",$lon);
        $day = date('Y-m-d', strtotime($utcTimestamp));
        $curl = new Curl();
        $apiLink = $this->config['api']["endpoint"] .
            "get-time-zone?key=" . $this->config['api']["key"]
            . "&format=json"
            . "&by=position"
            . "&lat=" . $lat
            . "&lng=" . $lon
            . "&country=" . $country
            . "&time=" . $utcTimestamp;
        $curl->get($apiLink);

        if ($curl->error) {
            if ($curl->errorCode === 429) {
                sleep(3);
                if ($errorCounter < 0) {
                    return false;
                }
                $errorCounter--;
                return $this->getTimeDataByGeoCurl($lat, $lon, $country, $utcTimestamp, $cityId, $updateInDb, $errorCounter);
            }
            return false;
        }
        $result = $curl->response;
        if ($result->status === "OK") {
            if ($updateInDb) {

                $DB = $this->db;
                $execResult = $DB->prepare(
                    "INSERT INTO cityZone ( cityId, date, gmtOffset, dst) VALUES ( ?, ?, ?, ?) ON DUPLICATE KEY UPDATE gmtOffset=gmtOffset,dst=dst;"
                );

                $execResult->bindParam(1, $cityId);
                $execResult->bindParam(2, $day);
                $execResult->bindParam(3, $result->gmtOffset);
                $execResult->bindParam(4, $result->dst);
                $execResult->execute();
                $DB = null;

            }

            return $result;
        }

        return false;
    }

    /**
     * Get city by id
     * @param $cityId
     * @return mixed
     */
    public function getCityById($cityId)
    {
        $query = $this->db->prepare("SELECT * from city WHERE id=:city  limit 1");
        $query->bindParam("city", $cityId);
        $query->execute();
        return $query->fetch();
    }

    /**
     * Getting all available city list
     * @return false|PDOStatement
     */
    public function getAllCity()
    {
        return $this->db->query("SELECT * from city ");
    }

    /** Converting UTC TIMESTAMP to localtimestamp
     * @param $cityId string City Id
     * @param $utcTimestamp string|int Utc timestamp
     * @param $tryOnline bool Try get data from external api
     * @return array
     */
    public function getDateTimeFromUtc( $cityId, $utcTimestamp,  $tryOnline = false)
    {
        $result["action"] = "fromUtc";
        $result["cityId"] = $cityId;
        $DBH = $this->db;
         $utcTimestamp = trim($utcTimestamp);
        if(!is_numeric($utcTimestamp)){
            $result["timeFromUtc"] = strtotime($utcTimestamp);
        }else{
            $result["timeFromUtc"] =$utcTimestamp;
        }

        $query = $DBH->prepare("SELECT * from cityZone WHERE cityZone.cityId=:city  and date(FROM_UNIXTIME(:timestamp)) = cityZone.date limit 1");
        $query->bindParam("city", $cityId);
        $query->bindParam("timestamp", $result["timeFromUtc"]);
        $query->execute();
        $citiData = $query->fetch();
        if (!$citiData) {
            if ($tryOnline) {
                $cityInfo = $this->getCityById($cityId);
                if (!$cityInfo) {
                    $result["status"] = 404;
                    $result["statusMessage"] = "City not found";
                    return $result;

                }
                $citiData = $this->getTimeDataByGeoCurl(
                    $cityInfo["latitude"],
                    $cityInfo["longitude"],
                    $cityInfo["country_iso3"],
                    $result["timeFromUtc"],
                    $cityInfo['id'],
                    true, 5);


                if ($citiData === false) {
                    $result["status"] = 404;
                    $result["statusMessage"] = "Can't find data by external Api";
                    return $result;

                }

                $result["gmtOffset"] = $citiData->gmtOffset;
                $result["dst"] = $citiData->dst;
                $result["localTime"] = $result["timeFromUtc"] + $citiData->gmtOffset;
                $result["status"] = 200;
                $result["stringTimeLocal"] = gmdate("Y-m-d\TH:i:s\Z", $result["localTime"]);
                $result["stringTimeUtc"] = gmdate("Y-m-d\TH:i:s\Z", $result["timeFromUtc"]);
                return $result;
            }

            $result["status"] = 404;
            $result["statusMessage"] = "Can't find any date";
            return $result;
        }

        $result["gmtOffset"] = $citiData["gmtOffset"];
        $result["dst"] = $citiData["dst"];
        $result["localTime"] = $result["timeFromUtc"] + $citiData['gmtOffset'];
        $result["stringTimeLocal"] = gmdate("Y-m-d\TH:i:s\Z", $result["localTime"]);
        $result["stringTimeUtc"] = gmdate("Y-m-d\TH:i:s\Z", $result["timeFromUtc"]);
        $result["status"] = 200;
        return $result;
    }


    /** Converting local  TIMESTAMP to UTC
     * @param $cityId string City Id
     * @param $localTimestamp string|int Utc timestamp
     * @param $tryOnline bool Try get data from external api
     * @return array
     */

    public function getDateTimeFromLocal($cityId, $localTimestamp, $tryOnline = false)
    {
        $result["action"] = "toUtc";
        $result["cityId"] = $cityId;
        $DBH = $this->db;
        $localTimestamp = trim($localTimestamp);
        if(!is_numeric($localTimestamp)){
            $result["localTime"] = strtotime($localTimestamp);
        }else{
            $result["localTime"] =$localTimestamp;
        }

        $query = $DBH->prepare("SELECT * from cityZone WHERE cityZone.cityId=:city  and date(FROM_UNIXTIME(:timestamp)) = cityZone.date limit 1");
        $query->bindParam("city", $cityId);
        $query->bindParam("timestamp", $result["localTime"]);
        $query->execute();
        $citiData = $query->fetch();
        if (!$citiData) {
            if ($tryOnline) {

                $cityInfo = $this->getCityById($cityId);
                if (!$cityInfo) {
                    $result["status"] = 404;
                    $result["statusMessage"] = "City not found";
                    return $result;

                }
                $citiData = $this->getTimeDataByGeoCurl(
                    $cityInfo["latitude"],
                    $cityInfo["longitude"],
                    $cityInfo["country_iso3"],
                    $result["localTime"],
                    $cityInfo['id'],
                    true, 5);


                if ($citiData === false) {
                    $result["status"] = 404;
                    $result["statusMessage"] = "Can't find data by external Api";

                } else {
                    $result["gmtOffset"] = $citiData->gmtOffset;
                    $result["dst"] = $citiData->dst;
                    $result["timeFromUtc"] = $result["localTime"] - $citiData->gmtOffset;

                    $result["stringTimeLocal"] = gmdate("Y-m-d\TH:i:s\Z", $result["localTime"]);
                    $result["stringTimeUtc"] = gmdate("Y-m-d\TH:i:s\Z", $result["timeFromUtc"]);
                    $result["status"] = 200;
                }
                return $result;
            }

            $result["status"] = 404;
            $result["statusMessage"] = "Can't find any date";
            return $result;
        }

        $result["gmtOffset"] = $citiData["gmtOffset"];
        $result["dst"] = $citiData["dst"];
        $result["timeFromUtc"] = $result["localTime"] - $citiData['gmtOffset'];
        $result["stringTimeLocal"] = gmdate("Y-m-d\TH:i:s\Z", $result["localTime"]);
        $result["stringTimeUtc"] = gmdate("Y-m-d\TH:i:s\Z", $result["timeFromUtc"]);
        $result["status"] = 200;
        return $result;
    }


}