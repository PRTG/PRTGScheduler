<?php

/**
 * Translation file for German language.
 *
 * Most strings can be an array, with a value as the key. The system will
 * pick the translation corresponding to the key. The key "else" will be picked
 * if no matching value is found. This is useful for plurals.
 *
 * Licensed under the MIT license.
 *
 * For the full copyright and license information, please view the LICENSE file.
 *
 * @author Rémi Lanvin <remi@cloudconnected.fr>
 * @link https://github.com/rlanvin/php-rrule
 */
return array(
	'yearly' => array(
		'1' => 'J&auml;hrlich',
		'else' => 'Alle %{interval} Jahre'
	),
	'monthly' => array(
		'1' => 'Monatlich',
		'else' => 'Alle %{interval} Monate'
	),
	'weekly' => array(
		'1' => 'W&ouml;chentlich',
		'2' => 'Alle zwei Wochen',
		'else' => 'Alle %{interval} Wochen'
	),
	'daily' => array(
		'1' => 'T&auml;glich',
		'2' => 'Jeden zweiten Tag',
		'else' => 'Alle %{interval} Tage'
	),
	'hourly' => array(
		'1' => 'St&uuml;ndlich',
		'else' => 'Alle %{interval} Stunden'
	),
	'minutely' => array(
		'1' => 'Min&uuml;tlich',
		'else' => 'Alle %{interval} Minuten'
	),
	'secondly' => array(
		'1' => 'Sek&uuml;ndlich',
		'else' => 'Alle %{interval} Sekunden'
	),
	'dtstart' => ', ab dem %{date}',
	'infinite' => '',
	'until' => ' bis %{date}',
	'count' => array(
		'1' => ', einmalig',
		'else' => ', %{count} mal'
	),
	'and' => 'und',
	'x_of_the_y' => array(
		'yearly' => '%{x} des Jahres', // e.g. the first Monday of the year, or the first day of the year
		'monthly' => '%{x} des Monats',
	),
	'bymonth' => ' im %{months}',
	'months' => array(
		1 => 'Januar',
		2 => 'Februar',
		3 => 'M&auml;rz',
		4 => 'April',
		5 => 'Mai',
		6 => 'Juni',
		7 => 'Juli',
		8 => 'August',
		9 => 'September',
		10 => 'October',
		11 => 'November',
		12 => 'Dezember',
	),
	'byweekday' => ' am %{weekdays}',
	'weekdays' => array(
		1 => 'Montag',
		2 => 'Dienstag',
		3 => 'Mittwoch',
		4 => 'Donnerstag',
		5 => 'Freitag',
		6 => 'Samstag',
		7 => 'Sonntag',
	),
	'nth_weekday' => array(
		'1' => 'ersten %{weekday}', // e.g. the first Monday
		'2' => 'zweiten %{weekday}',
		'3' => 'dritten %{weekday}',
		'else' => '%{n}en %{weekday}'
	),
	'-nth_weekday' => array(
		'-1' => 'letzten %{weekday}', // e.g. the last Monday
		'-2' => 'vorletzten %{weekday}',
		'-3' => 'drittletztem %{weekday}',
		'else' => 'der %{n} letzte %{weekday}'
	),
	'byweekno' => array(
		'1' => ' in Woche %{weeks}',
		'else' => ' In den Wochen %{weeks}'
	),
	'nth_weekno' => '%{n}',
	'bymonthday' => ' an %{monthdays}',
	'nth_monthday' => array(
		'1' => 'der 1.',
		'2' => 'der 2.',
		'3' => 'der 3.',
		'21' => 'der 21.',
		'22' => 'der 22.',
		'23' => 'der 23.',
		'31' => 'der 31.',
		'else' => 'der %{n}e'
	),
	'-nth_monthday' => array(
		'-1' => 'letzten Tag',
		'-2' => 'vorletzten Tag',
		'-3' => 'am drittletzten Tag',
		'-21' => 'the 21st to the last day',
		'-22' => 'the 22nd to the last day',
		'-23' => 'the 23rd to the last day',
		'-31' => 'the 31st to the last day',
		'else' => 'the %{n}th to the last day'
	),
	'byyearday' => array(
		'1' => ' am %{yeardays} Tag',
		'else' => ' an %{yeardays} Tagen'
	),
	'nth_yearday' => array(
		'1' => 'Der erste',
		'2' => 'Der zweite',
		'3' => 'Der dritte',
		'else' => 'Der %{n}th'
	),
	'-nth_yearday' => array(
		'-1' => 'Der letzte',
		'-2' => 'Der vorletzte',
		'-3' => 'Der drittletzte',
		'else' => 'Der %{n}-letzte'
	),
	'byhour' => array(
		'1' => ' um %{hours}',
		'else' => ' um %{hours}'
	),
	'nth_hour' => '%{n}',
	'byminute' => array(
		'1' => ':%{minutes} Uhr',
		'else' => ':%{minutes} Uhr'
	),
	'nth_minute' => '%{n}',
	'bysecond' => array(
		'1' => ':%{seconds}',
		'else' => ':%{seconds}'
	),
	'nth_second' => '%{n}',
	'bysetpos' => ', Aber nur die %{setpos} Instanz dieses Sets',
	'nth_setpos' => array(
		'1' => 'die erste',
		'2' => 'die zweite',
		'3' => 'die dritte',
		'else' => 'die %{n}te'
	),
	'-nth_setpos' => array(
		'-1' => 'die letzte',
		'-2' => 'die vorletzte',
		'-3' => 'die drittletzte',
		'else' => 'die %{n}t-letzte'
	)
);
