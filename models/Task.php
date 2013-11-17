<?php

class Task extends Shell\Model {

	public function schema() {
		$this->title = self::varchar(250);
		$this->owner = self::varchar(250);
		$this->done = self::tinyint(1, 0);
	}

}