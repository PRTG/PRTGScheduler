<?php

use RRule\RfcParser;

class RfcParserTest extends PHPUnit_Framework_TestCase
{
	public function rfcLines()
	{
		return array(
			array(
				'RDATE;TZID=America/New_York:19970714T083000',
				array(),
				array('name' => 'RDATE', 'params' => array('TZID' => 'America/New_York'), 'value' => '19970714T083000')
			),
			array(
				'RRULE:FREQ=YEARLY;UNTIL=20170202',
				array(),
				array('name' => 'RRULE', 'params' => array(), 'value' => 'FREQ=YEARLY;UNTIL=20170202')
			),
			array(
				'DTSTART=20160202T000000Z;FREQ=DAILY;UNTIL=20160205T000000Z',
				array('name' => 'RRULE'),
				array('name' => 'RRULE', 'params' => array(), 'value' => 'DTSTART=20160202T000000Z;FREQ=DAILY;UNTIL=20160205T000000Z')
			)
		);
	}

	/**
	 * @dataProvider rfcLines
	 */
	public function testParseLine($line, $default, $expected)
	{
		$this->assertEquals($expected, RfcParser::parseLine($line, $default));
	}

///////////////////////////////////////////////////////////////////////////////
// RDATE

	public function rdateLines()
	{
		return array(
			array('RDATE:19970714T123000Z',
				array(date_create('19970714T123000Z'))
			),
			array('RDATE;TZID=America/New_York:19970714T083000',
				array(date_create('19970714T083000',new \DateTimeZone('America/New_York')))
			),
			// array('RDATE;VALUE=PERIOD:19960403T020000Z/19960403T040000Z,19960404T010000Z/PT3H',
			// 	array()
			// ),
			array('RDATE;VALUE=DATE:19970101,19970120',
				array(date_create('19970101'),date_create('19970120'))
			)
		);
	}

	/**
	 * @dataProvider rdateLines
	 */
	public function testParseRDate($string, $expected)
	{
		$dates = RfcParser::parseRDate($string);
		$this->assertEquals($dates, $expected);
	}

///////////////////////////////////////////////////////////////////////////////
// EXDATE

	public function exdateLines()
	{
		return array(
			array('EXDATE:19960402T010000Z,19960403T010000Z,19960404T010000Z',
				array(date_create('19960402T010000Z'),date_create('19960403T010000Z'),date_create('19960404T010000Z'))
			),
			array('EXDATE;TZID=America/New_York:19970714T083000',
				array(date_create('19970714T083000',new \DateTimeZone('America/New_York')))
			),
		);
	}

	/**
	 * @dataProvider exdateLines
	 */
	public function testParseExDate($string, $expected)
	{
		$dates = RfcParser::parseExDate($string);
		$this->assertEquals($dates, $expected);
	}
}