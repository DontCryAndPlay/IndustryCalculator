<?php
class ExceptionBase extends Exception {
	function __construct(string $message = "") {
		if(!isset($message) || strlen($message) == 0)
			$this->message = get_called_class();
		else
			$this->message = $message;
	}
}
function uncaughtException($e) {
	echo "Uncaught Exception: " . $e->getMessage() . " (" . $e->getCode() . ")\n";
	echo "In file: " . $e->getFile() . ":" . $e->getLine() . "\n";
	echo $e->getTraceAsString() . "\n";
}
function importExceptions() {
	$files = scandir("exceptions");
	foreach($files as $file) {
		if($file == "." || $file == "..")
			continue;
		if($file == basename(__FILE__))
			continue;
		include($file);
	}
}

set_exception_handler('uncaughtException');

?>