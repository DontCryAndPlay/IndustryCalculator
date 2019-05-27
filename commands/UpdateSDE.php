<?php
class UpdateSDECommand extends Command {
	protected static $name = "sde-update";
	protected static $helpMessage = "Download and syncs local data with the latest SDE";
	public function execute() {
		$socket = new Socket("GET", "https://developers.eveonline.com/resource/resources");

		try {
			$data = $socket->execute();
		} catch(Exception $e) {
			error($e->getMessage());
			return false;
		} finally {
			unset($socket);
		}
		preg_match_all("/(https:\/\/cdn[0-9]+.+\/sde-[0-9]+-TRANQUILITY\.zip)/", $data['data'], $matches);
		$uri = $matches[0][0];
		$name = basename($uri);
		debug("Detected URI: %s", $uri);
		debug("Detected name: %s", $name);
		$progressBar = new CLI\ProgressBar();
		try {
			$dl = new Downloader($uri);
			$dl->onProgress(function($ch, $totalSize = 0, $downloaded = 0) use ($progressBar) {
				if($totalSize == 0)
					return false;
				$percent = (int) floor($downloaded * 100 / $totalSize);
				$progressBar->updatePercent($percent);
			});
			$dl->force = true;
			$dl->execute();
		} catch(Exception $e) {
			error($e->getMessage());
			return false;
		} finally {
			unset($dl);
		}
	}
}
?>