<?php

/**
 * Initial and most used functions and operations
 */

// explode string by length
function explode_length ($text, $length): array
{
	$result = [];

	$currentLength = 0;
	$partsCount = intval(strlen($text) / $length);

	for ($i = 0; $i < $partsCount; $i++)
	{
		$result[] = substr($text, $currentLength, $length);

		$currentLength += $length;
	}

	return $result;
}

// checks of string empty
function is_empty ($text): bool
{
	$test = implode('', explode(PHP_EOL, $text));
	if ($test == '') {
		return true;
	}
	$test = implode('', explode('\n', $test));
	if ($test == '') {
		return true;
	}
			
	$test = implode('', explode(' ', $test));
	if ($test == '') {
		return true;
	}
		
	return false;
}

// capitalize the string
function capitalize ($str, $encoding = "UTF-8"): string
{
	$str = mb_ereg_replace('^[\ ]+', '', $str);

    return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding).mb_substr($str, 1, mb_strlen($str), $encoding);
}