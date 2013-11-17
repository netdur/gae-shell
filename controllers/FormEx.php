<?php

require_once 'google/appengine/api/users/User.php';
require_once 'google/appengine/api/users/UserService.php';

use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

Shell\App::import("View");
Shell\App::import("Form");

class FormEx {

	public function GET() {

		$view = Shell\View::getInstance();
		$view->setTemplateFromFile("main");
		// $view->mergeFromFile("#main", "/static/index.html");

		$user = UserService::getCurrentUser();
		$link = $view->query("#auth");
		if (isset($user)) {
			$link->setText(sprintf("Logout %s", $user->getNickname()))
				->set("href", UserService::createLogoutUrl("/"));
		} else {
			$link->set("href", UserService::createLoginUrl("/"));
		}

		$form = new Shell\Form();
		$form->first_name = $form->field();
		$form->image = $form->file();
		$view->query("#main")->append($form->getNode());

	}

	public function POST() {
		$form = new Shell\Form();
		echo "<pre>";
		echo var_dump($form->verify());
		echo var_dump($form->first_name);
		print_r($form);
	}
}