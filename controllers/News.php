<?php

require_once 'google/appengine/api/users/User.php';
require_once 'google/appengine/api/users/UserService.php';

use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

Shell\App::import("View");

class News {

	public function GET() {

		$view = Shell\View::getInstance();
		$view->setTemplateFromFile("main");
		$view->mergeFromFile("#main", "/static/news.html");

		$user = UserService::getCurrentUser();
		$link = $view->query("#auth");
		if (isset($user)) {
			$link->setText(sprintf("Logout %s", $user->getNickname()))
				->set("href", UserService::createLogoutUrl("/"));
		} else {
			$link->set("href", UserService::createLoginUrl("/"));
		}

	}

	public function POST() {

		$view = Shell\View::getInstance();
		$view->setTemplateFromFile("main");
		$view->mergeFromFile("#main", "/static/news.html");

		$user = UserService::getCurrentUser();
		$link = $view->query("#auth");
		if (isset($user)) {
			$link->setText(sprintf("Logout %s", $user->getNickname()))
				->set("href", UserService::createLogoutUrl("/"));
		} else {
			$link->set("href", UserService::createLoginUrl("/"));
		}

		if (!isset($_POST["url"])) {
			return;
		}

		$view->query("input[name=url]")->set("value", $_POST["url"]);

		$html = file_get_contents($_POST["url"]);
		$readability = new Shell\Node($html);
		$ps = [];
		$first = false;
		$els = $readability->queryAll("p");
		foreach ($els as $el) {
			$text = $el->getText();
			if ($first == false) {
				if (strlen($text) > 100) {
					$first = $text;
					continue;
				}
			}
			$ps[] = $text;
		}

		function byLen($a, $b){
			return strlen($b) - strlen($a);
		}
		usort($ps, "byLen");

		$app = $view->query("#app");

		$i = 0;
		$article = new Shell\Node("<ul/>");

		$p = new Shell\Node("<li/>");
		$first = explode(".", $first);
		$p->setText($first[0]);
		$article->append($p);

		foreach ($ps as $text) {
			if ($i == 4) {
				break;
			}
			$p = new Shell\Node("<li/>");
			$text = explode(".", $text);
			$p->setText($text[0]);
			$article->append($p);
			$i++;
		}
		$app->append($article);

		$app->append(new Shell\Node("<hr/>"));
	}
}