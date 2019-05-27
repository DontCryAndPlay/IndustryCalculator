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
		#TODO: move this to a class library
		#	- TODO: consider creating FileSystem class library containing all files/zips standard functions
		$zip = new ZipArchive;
		$ok = $zip->open($name);
		if(!$ok) {
			error("Failed to open ZIP file");
			return false;
		}
		$zip->extractTo("./", array("sde/fsd/typeIDs.yaml", "sde/fsd/blueprints.yaml"));
		$zip->close();
		debug("ZIP Extracted");
		unlink($name);
		debug("ZIP Removed");

		$this->createSDE();
	}
	private function createSDE() {
		global $sqlite;
		//var_dump($sqlite);
		$sqlite->query("DROP TABLE IF EXISTS `manufacture_input`");
		$sqlite->query("DROP TABLE IF EXISTS `manufacture_output`");
		$sqlite->query("DROP TABLE IF EXISTS `type`");
		$sqlite->query("CREATE TABLE IF NOT EXISTS `type` ( `typeID` INTEGER NOT NULL, `name` TEXT NOT NULL, `volume` NUMERIC NOT NULL, PRIMARY KEY(`typeID`) ) WITHOUT ROWID");
		$sqlite->query("CREATE TABLE IF NOT EXISTS `manufacture_input` ( `bpid` INTEGER NOT NULL, `amount` INTEGER NOT NULL, `typeid` INTEGER NOT NULL, PRIMARY KEY(`bpid`,`typeid`), FOREIGN KEY(`bpid`) REFERENCES `type`(`typeID`) ON DELETE CASCADE ON UPDATE CASCADE, FOREIGN KEY(`typeid`) REFERENCES `type`(`typeID`) ON DELETE CASCADE ON UPDATE CASCADE ) WITHOUT ROWID");
		$sqlite->query("CREATE TABLE IF NOT EXISTS `manufacture_output` ( `bpid` INTEGER NOT NULL, `amount` INTEGER NOT NULL, `typeid` INTEGER NOT NULL, PRIMARY KEY(`bpid`,`typeid`), FOREIGN KEY(`bpid`) REFERENCES `type`(`typeID`) ON UPDATE CASCADE ON DELETE CASCADE, FOREIGN KEY(`typeid`) REFERENCES `type`(`typeID`) ON UPDATE CASCADE ON DELETE CASCADE ) WITHOUT ROWID");
		#TODO: checks for yaml, handler?
		$types = yaml_parse_file("sde/fsd/typeIDs.yaml");
		$bps   = yaml_parse_file("sde/fsd/blueprints.yaml");
		$progressBar = new CLI\ProgressBar();
		$total = count($bps);
		$i = 0;
		foreach($bps as $blueprint) {
			$i++;
			$percent = floor($i * 100 / $total);
			$progressBar->updatePercent($percent);
			if(!isset($blueprint['activities']['manufacturing']['materials']) ||
			   !isset($blueprint['activities']['manufacturing']['products']))
				continue;
			$blueprintID = $blueprint['blueprintTypeID'];
			if(!isset($types[$blueprintID]))
				continue;
			$name = $types[$blueprintID]['name']['en'];
			$volume = $types[$blueprintID]['volume'] ?? 0;
			$query = sprintf("INSERT INTO type VALUES(%u, \"%s\", %f)", $blueprintID, $name, $volume);
			$sqlite->query($query);
			foreach($blueprint['activities']['manufacturing']['materials'] as $material) {
				$id = $material['typeID'];
				$quantity = $material['quantity'];
				$query = sprintf("SELECT typeID FROM type WHERE typeID = %u LIMIT 1", $id);
				$d = $sqlite->query($query);
				if(count($d) == 0) {
					if(!isset($types[$id])) {
						$query = sprintf("DELETE FROM type WHERE typeid = %u LIMIT 1", $blueprintID);
						$sqlite->query($query);
						continue 2;
					}

					$name = $types[$id]['name']['en'];
					$volume = $types[$id]['volume'] ?? 0;
					$query = sprintf("INSERT INTO type VALUES(%u, \"%s\", %f)", $id, $name, $volume);
					$sqlite->query($query);
				}
				$query = sprintf("INSERT INTO manufacture_input VALUES(%u, %u, %u)", $blueprintID, $quantity, $id);
				$sqlite->query($query);
			}
			foreach($blueprint['activities']['manufacturing']['products'] as $product) {
				$id = $product['typeID'];
				$quantity = $product['quantity'];
				$query = sprintf("SELECT typeID FROM type WHERE typeID = %u LIMIT 1", $id);
				$d = $sqlite->query($query);
				if(count($d) == 0) {
					if(!isset($types[$id])) {
						$query = sprintf("DELETE FROM type WHERE typeid = %u LIMIT 1", $blueprintID);
						$sqlite->query($query);
						continue 2;
					}
					$name = $types[$id]['name']['en'];
					$volume = $types[$id]['volume'] ?? 0;
					$query = sprintf("INSERT INTO type VALUES(%u, \"%s\", %f)", $id, $name, $volume);
					$sqlite->query($query);
				}
				$query = sprintf("INSERT INTO manufacture_output VALUES(%u, %u, %u)", $blueprintID, $quantity, $id);
				$sqlite->query($query);
			}
		}
	}
}
?>