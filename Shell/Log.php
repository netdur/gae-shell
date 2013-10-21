<?php

namespace Shell;

require_once 'google/appengine/api/log/LogService.php';
use google\appengine\api\log\LogService;

class Log {

	private static $log = array();
	private static $id = null;

	const EMERG = LogService::LEVEL_CRITICAL;   /** System is unusable */
	const ALERT = LogService::LEVEL_CRITICAL;   /** Immediate action required */
	const CRIT = LogService::LEVEL_CRITICAL;    /** Critical conditions */
	const ERR = LogService::LEVEL_ERROR;     /** Error conditions */
	const WARNING = LogService::LEVEL_WARNING; /** Warning conditions */
	const NOTICE = LogService::LEVEL_INFO;  /** Normal but significant */
	const INFO = LogService::LEVEL_INFO;    /** Informational */
	const DEBUG = LogService::LEVEL_DEBUG;   /** Debug-level messages */
	
	public static function emergence($message) {
		return Log::write($message, self::EMERG);
	}
	
	public static function alert($message) {
		return Log::write($message, self::ALERT);
	}
	
	public static function critical($message) {
		return Log::write($message, self::CRIT);
	}
	
	public static function error($message) {
		return Log::write($message, self::ERR);
	}
	
	public static function warning($message) {
		return Log::write($message, self::WARNING);
	}
	
	public static function notice($message) {
		return Log::write($message, self::NOTICE);
	}
	
	public static function info($message) {
		return Log::write($message, self::INFO);
	}
	
	public static function debug($message) {
		return Log::write($message, self::DEBUG);
	}

	public function __construct() {
		if (self::$id === null) {
			self::$id = $_SERVER["REQUEST_LOG_ID"];
		}
	}
	
	public static function getId() {
		return self::$id; 
	}

	public static function write($message, $level = self::DEBUG) {
		$config = new Config();
		$level = self::__levelToString($level);
		self::$log[] = sprintf($config->get("logLineFormate"), date($config->get("logTimeFormate")), $level, $message);
		syslog((int) $level, $message);
	}

	public static function __levelToString($level) {
		//@TODO I18n
		$levels = array(
			"Debug-level messages",
			"Informational",
			"Normal but significant",
			"Warning conditions",
			"Error conditions",
			"Critical conditions",
			"Immediate action required",
			"System is unusable"
		);
		return (string) $levels[$level];
	}
	
	public static function getLog() {
		return self::$log;
	}
	
	public static function toString() {
		return (string) implode("", self::$log);
	}

}