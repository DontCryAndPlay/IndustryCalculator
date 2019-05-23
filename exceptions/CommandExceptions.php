<?php
class CommandException extends Exception {
	public function __construct(string $message = "", int $code = 0) {
		$this->message = $message . "\n";
		$this->code = $code;
	}
}
?>