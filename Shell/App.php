<?php

/**
 * a b c d e f g h i j k l m n o p q r s t u v w x y z
 * 0 1 2 3 4 5 6 7 8 9
 */
 
namespace Shell;

require_once 'google/appengine/api/log/LogService.php';
use google\appengine\api\log\LogService;

class App {

	private static $imports = array();
	public static $config = null;
	private $isHTML = true;
	private	$version = "0.8";

	function run($args) {

		App::import("AppException");
		App::import("Config");
		App::import("Request");
		App::import("I18n");
		App::import("Log");

		/*$log =*/new Log();

		$config = new Config($args);
		$config->set("start", microtime(true));
		$config->set("version", $this->version);
		self::$config = $config;

		$i18n = new I18n();
		$i18n->addFile(sprintf("%s/%s", $config->get("path"), "i18n/%LOCAL/appshell/core.xml"));

		$request = new Request();
		$request = $request->getArguments();

		$sucess = false;
		$file = sprintf($config->get("classSchema"), $config->get("path"), $config->get("mainClass"));

		// [Error conditions] Error at line 45: Only variables should be passed by reference
		$mainClass = explode("/", $config->get("mainClass"));
		$mainClass = end($mainClass);
		$config->set("mainClass", $mainClass);

		if (is_file($file) === false) {
			Log::write($i18n->getText("file_not_found", $file));
		} else {
			$config->set("mainClass", ucfirst($config->get("mainClass")));
		}

		include_once($file);

		$classStartTime = microtime(true);
		$classMemoryUsage = memory_get_usage();
		if (class_exists($config->get("mainClass")) === false) {
			Log::write($i18n->getText("class_not_found", $config->get("mainClass"), $file));
		} else {
			$reflectionClass = new \ReflectionClass($config->get("mainClass"));
			$instance = $reflectionClass->newInstanceArgs(array());
		}

		if (method_exists($config->get("mainClass"), $config->get("mainMethod")) === false) {
			Log::write(
				$i18n->getText("method_not_found", $config->get("mainMethod"), $config->get("mainClass"))
			);
		} else {
			$callback = new \ReflectionMethod($config->get("mainClass"), $config->get("mainMethod"));
			$callback->invokeArgs($instance, $request);
			$success = true;
		}

		if ($success == false) {
			// fallback
		}

		Log::write($i18n->getText("execute_time_of",
			$config->get("mainClass"),
			$config->get("mainMethod"),
			(microtime(true) - $classStartTime))
		);
		Log::write($i18n->getText("memory_usage",
			$config->get("mainClass"),
			$config->get("mainMethod"),
			(memory_get_usage() - $classMemoryUsage))
		);

		if (isset($instance)) {
			unset($instance);			
		}

		if ($config->get("isHTML") === true) {
			$view = View::getInstance();
			if ($view->getDocument() != null) {
				if ($config->get("development") == true) {
					$selector = new Selector();
					$console = $selector->query("#appshellConsole");
					if ($console) {
						$logs = Log::getLog();
						foreach ($logs as $line) {
							$li = new Node("<li/>");
							$li->setText($line);
							$console->append($li);
						}
						$li = new Node("<li><a/></li>");
						$li->query("a")
								->setText(sprintf("request id: %s", Log::getId()))
								->set("href", sprintf("/_ah/log/%s", Log::getId()));
						$console->append($li);
					}
				}
				$view->setLocal();
				$view->flush();
			}
		}

	}

	public static function import($library) {
		$config = self::$config;

		if (in_array($library, self::$imports)) {
			return;
		} else {
			self::$imports[] = $library;
		}

		$core = array("Config", "AppException", "I18n", "Log", "Image", "URL", "Security", "Debug", "Node", "Request", "Restful",
				"Selector", "View", "XPath", "Form", "Query");

		if (in_array($library, $core)) {
			return include_once(sprintf("%s.php", $library));
		}

		if (file_exists(sprintf("%s%s.php", $config->modelsDir, $library))) {
			include_once("DBDrivers/mysql.php");
			include_once("Model.php");
			include_once("Query.php");
			return include_once(sprintf("%s%s.php", $config->modelsDir, $library));
		}

		return include_once(sprintf("%s%s%s", $config->get("classDir"), $library, $config->get("phpExt")));
	}
}