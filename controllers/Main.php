<?php

require_once 'google/appengine/api/users/User.php';
require_once 'google/appengine/api/users/UserService.php';

use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

Shell\App::import("View");

class Main {

	public function GET() {

		$view = Shell\View::getInstance();
		$view->setTemplateFromFile("main");
		$view->mergeFromFile("#main", "/static/index.html");

		$user = UserService::getCurrentUser();
		$link = $view->query("#auth");
		if (isset($user)) {
			$link->setText(sprintf("Logout %s", $user->getNickname()))
				->set("href", UserService::createLogoutUrl("/"));
		} else {
			$link->set("href", UserService::createLoginUrl("/"));
		}

	}
}