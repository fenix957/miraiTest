<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
include_once( __DIR__ . '/vendor/autoload.php') ;
$config = require APP_DIR . '/config.php';


use Mirai\TimezoneGenerator;
$timeZoneGenerator = new TimezoneGenerator();


$timestampNew = false;
$convertResult = false;
if (isset($_GET["action"], $_GET["timestamp"], $_GET["city"]) && trim($_GET["city"]) !== "" && trim($_GET["timestamp"]) !== "") {
    $result = [];
    if (trim($_GET["action"]) === "fromUtc" || trim($_GET["action"]) === "toUtc") {
        if (trim($_GET["action"]) === "fromUtc") {
            $convertResult = $timeZoneGenerator->getDateTimeFromUtc(trim($_GET['city']), $_GET['timestamp'], true);
        } else {
            $convertResult = $timeZoneGenerator->getDateTimeFromLocal(trim($_GET['city']), $_GET['timestamp'], true);
        }
    }
}

if (isset($_GET["json"])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($convertResult);
    exit(0);
}


$stmt = $timeZoneGenerator->getAllCity();
$allCity = [];
while ($row = $stmt->fetch()) {
    $allCity[] = $row;
}

?>
<!DOCTYPE HTML>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <title>Тестовое задание </title>
    </head>
    <body>

    <h3>Получения локального времени в городе по переданному идентификатору города и метке времени по UTC+0</h3>
    <form id="toLocalDatetimeForm" method="get" action="/">
        <input name="action" type="hidden" value="fromUtc">

        <label>
            Date:
            <input name="timestamp" type="datetime-local" value=""/>
        </label>

        <label>
            City:
            <select name="city">
                <?php
                foreach ($allCity as $item) {
                    ?>
                    <option value="<?php echo $item["id"] ?>"><?php echo $item["name"] . " " . $item["country_iso3"] ?></option>
                    <?php
                }
                ?>
            </select>
        </label>
        <br><br>

        <button type="submit">Рассчитать</button>
    </form>


    <h3>Обратное преобразование из локального времени и идентификатора города в метку времени по UTC+0.
    </h3>
    <form id="fromLocalTime" method="get" action="/">
        <input name="action" type="hidden" value="toUtc">
        <label>
            Date:
            <input name="timestamp" type="datetime-local" value=""/>
        </label>
        <label>
            City:
            <select name="city">
                <?php
                foreach ($allCity as $item) {
                    ?>
                    <option value="<?php echo $item["id"] ?>"><?php echo $item["name"] . " " . $item["country_iso3"] ?></option>
                    <?php
                }
                ?>
            </select>
        </label>
        <br><br>

        <button type="submit">Рассчитать</button>
    </form>


    <h4 class="toLocalDatetimeResult">
        <?php

        if ($convertResult) {

            echo " <h3>Результат   </h3>";
            echo "<pre>" . json_encode($convertResult);
            echo "</pre><br><a  target='_blank' href='/?action=" . $_GET["action"] . "&timestamp=" . $_GET["timestamp"] . "&city=" . $_GET["city"] . "&json=true'>Get Json </a>";

        }


        ?>

    </h4>


    </body>
    </html>


<?php

