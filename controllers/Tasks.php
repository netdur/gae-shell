<?php

Shell\App::import("Restful");
Shell\App::import("Task");

class Tasks extends Shell\Restful {

	public $model = "Task";

}