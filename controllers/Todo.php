<?php

require_once 'google/appengine/api/users/User.php';
require_once 'google/appengine/api/users/UserService.php';

use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

Shell\App::import("View");
Shell\App::import("Task");

class Todo {

	public function GET() {

		$view = Shell\View::getInstance();
		$view->setTemplateFromFile("main");
		$view->mergeFromFile("#main", "/static/todo.html");

		$user = UserService::getCurrentUser();
		$link = $view->query("#auth");
		if (isset($user)) {
			$link->setText(sprintf("Logout %s", $user->getNickname()))
				->set("href", UserService::createLogoutUrl("/"));
		} else {
			$link->set("href", UserService::createLoginUrl("/"));
			$view->query("#add-task")->set("disabled", "disabled");
		}

		if (!isset($user)) {
			return;
		}

		$markup = new Shell\Node('
		<div class="col-lg-10 task">
			<div class="input-group ">
				<span class="input-group-addon">
					<input type="checkbox">
				</span>
				<input type="text" class="form-control">
			</div>
		</div>');
		$app = $view->query("#app .row");
		
		$q = Task::query();
		$q->find(array(
			"owner" => $user->getUserId()
		));
		$tasks = $q->fetch();
		foreach ($tasks as $task) {
			$el = $markup->cloneNode();
			$input = $el->query("input[type=text]");
			$input->set("value", $task->title);
			$input->set("data-id", $task->id);
			if ($task->done == 1) {
				$input->addClass("done");
				$el->query("input[type=checkbox]")->set("checked", "checked");
			}
			$app->append($el);
		}

	}

	public function POST() {
		$user = UserService::getCurrentUser();
		if (!isset($user)) {
			header("Location: /todo");
			return;
		}

		$task = new Task();
		$task->owner = $user->getUserId();
		$task->title = $_POST["title"];
		$task->save();

		header("Location: /todo");
	}

	public function PUT($id) {
		header("Content-type: application/json");
		$user = UserService::getCurrentUser();
		if (!isset($user)) {
			echo json_encode(["status" => 401]);
			return;
		}
		
		$task = Task::get((int) $id);
		if ($task->owner != $user->getUserId()) {
			echo json_encode(["status" => 405]);
			return;
		}
		
		$put = Shell\Request::$_PUT;
		$task->title = $put["title"];
		$task->done = $put["done"] == "true";
		$task->save();

		echo json_encode(["status" => 200, "id" => $task->id, "done" => $task->done]);
	}
}