<?php

namespace Mirai;

require_once(__DIR__ . '/../../vendor/autoload.php') ;
use PHPUnit\Framework\TestCase;

class TimezoneGeneratorTest extends TestCase
{

    public function testGetDateTimeFromUtc()
    {
        $tzGenerator = new TimezoneGenerator();
        $time = $tzGenerator->getDateTimeFromUtc("eb56dea3-4cbe-44e7-acd1-0bc26dd8ab5b",1664304050,true);
        $this->assertEquals('1664314850', $time["localTime"]);



    }

    public function testGetCityById()
    {
        $tzGenerator = new TimezoneGenerator();
        $city = $tzGenerator->getCityById('040efa6e-3512-4523-a4dc-33e29aece663');
        $this->assertSame('Финикс', $city["name"]);

    }

    public function testGetTimeDataByGeoCurl()
    {
        $tzGenerator = new TimezoneGenerator();
        $time = $tzGenerator->getTimeDataByGeoCurl("55,5533","38,1500","RUS",1664485200,"eb56dea3-4cbe-44e7-acd1-0bc26dd8ab5b");
        $this->assertEquals('1664496000', $time->timestamp);


    }

    public function testGetDateTimeFromLocal()
    {
        $tzGenerator = new TimezoneGenerator();
        $time = $tzGenerator->getDateTimeFromLocal("eb56dea3-4cbe-44e7-acd1-0bc26dd8ab5b",1664496000,true);
        $this->assertEquals('1664485200', $time["timeFromUtc"]);
    }
}
