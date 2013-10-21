<?php

Shell\App::import("Restful");
Shell\App::import("Task", "tasks");

class Tasks extends Shell\Restful {

	public $model = "Task";

}