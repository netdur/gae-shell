<?php

include("Shell/App.php");

class Index extends Shell\App {

	public function __construct() {

		$config["urls"] = array(
			// log
			"/_ah/log/(.*)" => "AHLog",

			// examples
			// api
			"/api/1/tasks/(.*)" => "Tasks",

			// pages
			"/formex" => "FormEx",
			"/news" => "News",
			"/todo/uid(.*)" => "Todo",
			"/todo" => "Todo",
			"/.*" => "Main"
		);

		$config["development"] = true;
		$config["admin_user"] = "root";
		$config["admin_pass"] = "pass";

		$config["dbHost"] = "localhost";
		$config["dbUser"] = "root";
		$config["dbPass"] = "";
		$config["dbName"] = "todo_app";

		$config["salt"] = "1DTmN4m2CxMDfhaFDiUn";

		$config["path"] = dirname(__FILE__);

		$this->run($config);
	}

} new Index;