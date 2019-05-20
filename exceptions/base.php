<?php
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