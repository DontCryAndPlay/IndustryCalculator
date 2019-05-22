<?php
function debug(string $message, ...$params) {
	$message = vsprintf($message, $params);
	if(!defined("DEBUG"))
		return;
	list(,$caller)=debug_backtrace(false);
	if(isset($caller['class'])){
		if ($caller['class'] == "DummyClass"){
			list(,,$caller) = debug_backtrace(false);
		}
		printf("[%s][%s]: %s\n", $caller['class'], $caller['function'], $message);
	} else
		printf("[%s]: %s\n", $caller['function'], $message);
}
?>