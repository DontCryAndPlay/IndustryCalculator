<?php
abstract class FileException extends Exception {
	function __construct(string $message = "") {
		if(!isset($message) || strlen($message) == 0)
			$this->message = get_called_class();
		else
			$this->message = $message;
	}
}
class FileNotFoundException extends FileException {}
class FileNotReadableException extends FileException {}
?>