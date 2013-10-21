<?php

namespace Shell;

require_once 'google/appengine/api/app_identity/AppIdentityService.php';
use \google\appengine\api\app_identity\AppIdentityService;

class Config {

	private static $instance = null;
	private static $hash = null;

	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new Config();
		}
		return self::$instance;
	}

	public function __construct($config = array()) {

		if (self::$hash !== null) {
			return;
		}

		self::$hash = array();

		ini_set("display_errors", "on");
		libxml_use_internal_errors(true);
		error_reporting(E_ALL | E_STRICT);
		date_default_timezone_set("Africa/Casablanca");
		set_error_handler(array("\Shell\AppException", "errorHandler"), E_ALL);

		/**
		 * default configuration values
		 */
		self::$hash["mainClass"] = "Main";
		self::$hash["mainMethod"] = "GET";
		self::$hash["phpExt"] = ".php";

		self::$hash["log"] = null;
		self::$hash["logLineFormate"] = "%s : [%s] %s\n";
		self::$hash["logTimeFormate"] = "c";

		self::$hash["framework"] = dirname(__FILE__);
		self::$hash["development"] = false;
		self::$hash["local"] = "en_US";
		self::$hash["ssl"] = false;
		
		self::$hash["i18nXmlSchema"] = "%s/i18n/%s/%s.xml";
		self::$hash["i18nCacheSchema"] = "%s/cache/i18n/%s/%s";
		self::$hash["classSchema"] = "%s/controllers/%s";
		self::$hash["classDir"]	= "%s/controllers/";
		self::$hash["staticSchema"] = "%s/static/%s.html";
		self::$hash["dataSchema"] = "%s/data/%s.xml";
		self::$hash["cache"] = "%s/cache/";

		self::$hash["dbType"] = "mysql";
		self::$hash["dbHost"] = "localhost";
		self::$hash["dbUser"] = "root";
		self::$hash["dbPass"] = "";
		self::$hash["dbName"] = "";
		self::$hash["dbPrefix"] = "";
		self::$hash["dbMem"] = true;

		self::$hash["salt"] = "#";
		
		/**
		 * overide default values && add more configuration keys
		 */
		foreach ($config as $key => $value) {
			self::$hash[$key] = $value;
		}

		if (isset(self::$hash["secret"])) {
			self::$hash["secret"] = sha1(sprintf("%s%s", $_SERVER["REMOTE_ADDR"], self::$hash["secret"]));
		}

		self::$hash["classSchema"] .= self::$hash["phpExt"];
		self::$hash["classDir"]	= sprintf(self::$hash["classDir"], self::$hash["path"]);

		self::$hash["cache"] = sprintf(self::$hash["cache"], self::$hash["path"]);

		/**
		 * App Engine
		 */
		self::$hash["accountName"] = AppIdentityService::getServiceAccountName();
		self::$hash["applicationId"] = AppIdentityService::getApplicationId();

		/**
		 * if running on web server!
		 */
		if (empty($_SERVER["HTTP_USER_AGENT"]) === false) {
			# $httprotocol = ($_SERVER["HTTPS"] === "on") ? "http" : "https";
			$httprotocol = (self::$hash["ssl"] === false) ? "http" : "https";
			if (isset($config["url"])) {
				self::$hash["url"] = sprintf($config["url"], $_SERVER["HTTP_HOST"]);
				self::$hash["url"] = sprintf("%s://%s", $httprotocol, self::$hash["url"]);
			} else {
				self::$hash["url"] = sprintf ("%s://%s", $httprotocol, $_SERVER["HTTP_HOST"]);
			}
		}
		
		$url = (substr(self::$hash["url"], -1) == "/") ? "%s%s" : "%s/%s";
		if (isset(self::$hash["bootstrap"])) {
			self::$hash["curl"] = sprintf($url, self::$hash["url"], self::$hash["bootstrap"]);
		} else {
			self::$hash["curl"] = sprintf($url, self::$hash["url"], basename($_SERVER["SCRIPT_FILENAME"]));
		}

		self::$hash["address"] = sprintf("http%s://%s%s%s%s",
			(isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) ? "s" : "",
			(isset($_SERVER["PHP_AUTH_USER"]))
				? sprintf("%s:%s@", $_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"])
				: "",
			$_SERVER["SERVER_NAME"],
			($_SERVER["SERVER_PORT"] != "80") ? sprintf(":%s", $_SERVER["SERVER_PORT"]) : "",
			$_SERVER["REQUEST_URI"]
		);
		$pos = strpos(self::$hash["address"], "?");
		self::$hash["self"] = substr(self::$hash["address"], 0, ($pos === false) ? strlen(self::$hash["address"]) : $pos);
	}

	public function get($key) {
		if (isset(self::$hash[$key])) {
			return self::$hash[$key];
		} else {
			return null;
		}
	}

	public function set($key, $value) {
		self::$hash[$key] = $value;
	}
	
	public function __get($key) {
		if (isset(self::$hash[$key])) {
			return self::$hash[$key];
		} else {
			return null;
		}
	}

	public function __set($key, $value) {
		self::$hash[$key] = $value;
	}

	public static function mapTable($key, $value) {
		self::$hash["dbTables"][$key] = $value;
	}
}
