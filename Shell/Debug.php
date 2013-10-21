<?php

namespace Shell;

class Debug {

	/**
	 * assert consts
	 */
	
	# const false	= 0: 
	# const true	= 1:
	const NUMBER	= 2;
	const RESOURCE  = 3; 
	const STRING	= 4;
	const OBJECT	= 5;
	const BOOL		= 6;
	const INT		= 7;
	const FLOAT		= 8;
	const DICT		= 9;
	
	#const url		= 10;
	const EMAIL		= 11;
	const USERNAME	= 12;
	const URL		= 13;

	const COUNT		= 20;
	const EQUAL		= 21;
	const NOTEQUAL	= 22;
	const NULL		= 23;

	const DATE		= 31;

	public static function assert($key, $value, $compare = null) {
		/**
		 * 0-9 # kind of stupid
		 */
		if ($value === true) {
			if ($key === true) return true;
		}
		if ($value === false) {
			if ($key === false) return true;
		}
		if ($value === self::NUMBER) {
			if (is_numeric($key)) return true;
		}
		if ($value === self::RESOURCE) {
			if (is_resource($key)) return true;
		}
		if ($value === self::STRING) {
			if (is_string($key)) return true;
		}
		if ($value === self::OBJECT) {
			if (is_object($key)) return true;
		}
		if ($value === self::BOOL) {
			if (is_bool($key)) return true;
		}
		if ($value === self::INT) {
			if (is_int($key)) return true;
		}
		if ($value === self::FLOAT) {
			if (is_float($key)) return true;
		}
		if ($value === self::DICT) {
			if (is_array($key)) return true;
		}
		
		/**
		 * 10-19
		 */
		if ($value === self::EMAIL) {
			return self::checkEmailAddress($key, true);
		}
		if ($value === self::USERNAME) {
			if (preg_match("#^[\w-]{1,40}$#", $key)) {
				return true;
			}
		}
		if ($value === self::URL) {
			$url = parse_url($key); 
			if ($url == false) {
				return false;
			} else if (!isset($url["scheme"]) || !isset($url["host"])) {
				return false;
			}
			return true;
		}
		
		/**
		 * 20-29
		 */
		if ($value === self::COUNT) {
			if (is_array($key)) {
				if (count($key) === $compare) return true;
			} else if (is_string($key)) {
				if (strlen($key) === $compare) return true;
			}
		}
		if ($value === self::EQUAL) {
			if ($compare === $key) return true;
		}
		if ($value === self::NOTEQUAL) {
			if ($compare != $key) return true;
		}

		/**
		 * 30-39
		 */
		if ($value === self::DATE) {
			return self::checkDate($key);
		}

		return false;
	}

	public static function getMemory() {
		return (memory_get_usage());
	}
	
	public static function memoryUsage($memory) {
		return (memory_get_usage() - $memory);
	}
	
	public static function getTime() {
		return (microtime(true));
	}
	
	public static function timeUsage($time) {
		return (microtime(true) - $time);
	}

	# PEAR /Mail/RFC822.php
	private static function checkEmailAddress($email, $strict = false) {
		$regex = $strict ? 
			'/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i' :
			'/^([*+!.&#$¦\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i';
		$regex = "/^([\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*[\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)$/i";
		if (preg_match($regex, trim($email), $matches)) {
			# return array($matches[1], $matches[2]);
			return true;
		} else {
			return false;
		}
	}

	public static function checkDate($date) {
		$stamp = strtotime($date);
		if (!is_numeric($stamp)) {
			return false;
		}
		$month = date("m", $stamp);
		$day   = date("d", $stamp);
		$year  = date("Y", $stamp);
		if (checkdate($month, $day, $year)) {
			return true;
		}
		return false;
	}
}
