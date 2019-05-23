<?php
class UpdateSDECommand extends Command {
	protected static $name = "sde-update";
	protected static $helpMessage = "Download and syncs local data with the latest SDE";
	public function execute() {
		#TODO: move curl/fsockopen functions to a class library
		# - TODO: check if curl is installed
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://developers.eveonline.com/resource/resources");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$data = curl_exec($ch);
		curl_close($ch);
		preg_match_all("/(https:\/\/cdn[0-9]+.+\/sde-[0-9]+-TRANQUILITY\.zip)/", $data, $matches);
		$uri = $matches[0][0];
		$name = basename($uri);
		debug("\n\tDetected URI: %s\n\tDetected name: %s", $uri, $name);
	}
}