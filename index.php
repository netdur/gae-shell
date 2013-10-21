<?php

include("Shell/App.php");

class Index extends Shell\App {

	public function __construct() {

		$config["development"] = true;

		$config["urls"] = array(
			// api
			"/api/1/tasks/(.*)" => "Tasks",
			// pages
			"/news" => "News",
			"/todo/uid(.*)" => "Todo",
			"/todo" => "Todo",
			"/.*" => "Main"
		);

		$config["dbHost"] = "localhost";
		$config["dbUser"] = "root";
		$config["dbPass"] = "";
		$config["dbName"] = "todo_app";

		$config["salt"] = "1DTmN4m2CxMDfhaFDiUn";

		$config["path"] = dirname(__FILE__);

		$this->run($config);
	}

} new Index;