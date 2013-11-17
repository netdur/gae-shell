<?php

require_once "google/appengine/api/log/LogService.php";
require_once 'google/appengine/api/log/RequestLogIterator.php';

use google\appengine\api\log\LogService;
use google\appengine\LogReadRequest;

Shell\App::import("View");

class AHLog {

	public function GET($id) {

		/*
		$config = Shell\Config::getInstance();

		if ($config->admin_user == "" || $config->admin_pass == "") {
			die("admin is not set");
		}

		if (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) {
			if ($_SERVER["PHP_AUTH_USER"] != $config->admin_user || $_SERVER["PHP_AUTH_PW"] != $config->admin_pass) {
				unset($_SERVER["PHP_AUTH_USER"]);
			}
		}

		if (!isset($_SERVER["PHP_AUTH_USER"])) {
			header("WWW-Authenticate: Basic realm='Log'");
			header("HTTP/1.0 401 Unauthorized");
			return;
		}
		*/

		$view = Shell\View::getInstance();
		$view->setTemplate("<html><title>Log<body>");

		$body = $view->query("body");
		$ol = new Shell\Node("<ol/>");
		$body->append($ol);

		$log = LogService::fetchById($id)[0];
		$hack = (array) $log;
		foreach ($hack as $key => $value) {
			$hack = (array) $hack[$key];
			break;
		}

		$li = new Shell\Node("<li/>");
		$li->setText(sprintf("mega cycles: %s", $hack["mcycles"]));
		$ol->append($li);

		$lv = [
			"getAppEngineRelease" => "App Engine Release",
			"getAppId" => "The application ID that handled this request",
			"getCombined" => "The Apache-format combined log entry for this request. While the information in this field can be constructed from the rest of this message, we include this method for convenience.",
			//"getEndDateTime" => "The same value as getEndTimeUsec() as a DateTime instance accurate to the second",
			"getEndTimeUsec" => "The time at which the request finished processing, in microseconds since the Unix epoch",
			"getHost" => "The Internet host and port number of the resource being requested.",
			"getHttpVersion" => "The HTTP version of this request.",
			"getInstanceIndex" => "The module instance that handled the request if manual_scaling or basic_scaling is configured or -1 for automatic_scaling.",
			//"getInstanceKey" => "Mostly-unique identifier for the instance that handled the request, or the empty string.",
			"getIp" => "The origin IP address of this request. App Engine uses an origin IP address from the 0.0.0.0/8 range when the request is to a web hook. Some examples of web hooks are task queues, cron jobs and warming requests.",
			"getLatencyUsec" => "The time required to process this request in microseconds.",
			"getMethod" => "The request's HTTP method (e.g., GET, PUT, POST).",
			"getModuleId" => "The version of the application that handled this request.",
			"getNickname" => "The nickname of the user that made the request. An empty string is returned if the user is not logged in.",
			"getPendingTimeUsec" => "The time, in microseconds, that this request spent in the pending request queue, if it was pending at all.",
			"getReferrer" => "The referrer URL of this request.",
			"getResource" => "The resource path on the server requested by the client. Contains only the path component of the request URL.",
			"getResponseSize" => "The size (in bytes) of the response sent back to the client.",
			//"getStartDateTime" => "The same value as getStartTimeUsec() as a DateTime instance accurate to the second.",
			"getStartTimeUsec" => "The time at which this request began processing, in microseconds since the Unix epoch.",
			"getStatus" => "The HTTP response status of this request.",
			"getTaskName" => "The request's task name, if this request was generated via the Task Queue API.",
			"getTaskQueueName" => "The request's queue name, if this request was generated via the Task Queue API.",
			"getUrlMapEntry" => "The file or class within the URL mapping used for this request. Useful for tracking down the source code which was responsible for managing the request, especially for multiply mapped handlers.",
			"getUserAgent" => "The user agent used to make this request.",
			"getVersionId" => "The version of the application that handled this request.",
			"isFinished" => "Whether or not this request has finished processing. If not, this request is still active.",
			"isLoadingRequest" => "Whether or not this request was a loading request."
		];

		foreach ($lv as $method => $desc) {
			$li = new Shell\Node("<li/>");
			$li->setText(sprintf("%s: %s", $method, $log->$method()));
			$li->set("title", $desc);
			$ol->append($li);
		}

		$app_logs = $log->getAppLogs();
		foreach ($app_logs as $app_log) {
			$li = new Shell\Node("<li/>");
			$li->setText(sprintf("log: %s", $app_log->getMessage()));
			$ol->append($li);	
		}
	}
}