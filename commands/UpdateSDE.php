<?php
class UpdateSDECommand extends Command {
	protected static $name = "sde-update";
	protected static $helpMessage = "Download and syncs local data with the latest SDE";
	private $types, $blueprints;
	private $sqlite;
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
		$progressBar->label = sprintf("Downloading %s", $name);
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

		global $sqlite;
		$this->sqlite = $sqlite;

		$this->createSDE();
	}
	private function processStep(int $blueprintID, string $step) : bool {
		switch ($step) {
			case "input":
				$table = "manufacture_input";
				$data = $this->blueprints[$blueprintID]['activities']['manufacturing']['materials'];
				break;
			case "output":
				$table = "manufacture_output";
				$data = $this->blueprints[$blueprintID]['activities']['manufacturing']['products'];
				break;
			default:
				return false;
		}
		foreach($data as $material) {
			$id = $material['typeID'];
			$quantity = $material['quantity'];
			$query = sprintf("SELECT typeID FROM type WHERE typeID = %u LIMIT 1", $id);
			$d = $this->sqlite->query($query);

			if(count($d) == 0) {
				if(!isset($this->types[$id]))
					return false;

				$name = $this->types[$id]['name']['en'];
				$volume = $this->types[$id]['volume'] ?? 0;
				$query = sprintf("INSERT INTO type VALUES(%u, \"%s\", %f)", $id, $name, $volume);
				$this->sqlite->query($query);
			}

			$query = sprintf("INSERT INTO %s VALUES(%u, %u, %u)", $table, $blueprintID, $quantity, $id);
			$this->sqlite->query($query);
		}
		return true;
	}
	private function installTables() {
		$this->sqlite->query("DROP TABLE IF EXISTS `manufacture_input`");
		$this->sqlite->query("DROP TABLE IF EXISTS `manufacture_output`");
		$this->sqlite->query("DROP TABLE IF EXISTS `type`");
		$this->sqlite->query("CREATE TABLE IF NOT EXISTS `type` ( `typeID` INTEGER NOT NULL, `name` TEXT NOT NULL, `volume` NUMERIC NOT NULL, PRIMARY KEY(`typeID`) ) WITHOUT ROWID");
		$this->sqlite->query("CREATE TABLE IF NOT EXISTS `manufacture_input` ( `bpid` INTEGER NOT NULL, `amount` INTEGER NOT NULL, `typeid` INTEGER NOT NULL, PRIMARY KEY(`bpid`,`typeid`), FOREIGN KEY(`bpid`) REFERENCES `type`(`typeID`) ON DELETE CASCADE ON UPDATE CASCADE, FOREIGN KEY(`typeid`) REFERENCES `type`(`typeID`) ON DELETE CASCADE ON UPDATE CASCADE ) WITHOUT ROWID");
		$this->sqlite->query("CREATE TABLE IF NOT EXISTS `manufacture_output` ( `bpid` INTEGER NOT NULL, `amount` INTEGER NOT NULL, `typeid` INTEGER NOT NULL, PRIMARY KEY(`bpid`,`typeid`), FOREIGN KEY(`bpid`) REFERENCES `type`(`typeID`) ON UPDATE CASCADE ON DELETE CASCADE, FOREIGN KEY(`typeid`) REFERENCES `type`(`typeID`) ON UPDATE CASCADE ON DELETE CASCADE ) WITHOUT ROWID");
	}
	private function createSDE() {
		$this->installTables();

		#TODO: checks for yaml, handler?
		$this->types      = yaml_parse_file("sde/fsd/typeIDs.yaml");
		$this->blueprints = yaml_parse_file("sde/fsd/blueprints.yaml");

		$progressBar = new CLI\ProgressBar();
		$progressBar->label = "Updating local database";

		$total = count($this->blueprints);
		$i = 0;
		foreach($this->blueprints as $blueprint) {
			$i++;
			$percent = floor($i * 100 / $total);
			$progressBar->updatePercent($percent);

			if(!isset($blueprint['activities']['manufacturing']['materials']) ||
			   !isset($blueprint['activities']['manufacturing']['products']))
				continue;
			$blueprintID = $blueprint['blueprintTypeID'];
			if(!isset($this->types[$blueprintID]))
				continue;

			$name = $this->types[$blueprintID]['name']['en'];
			$volume = $this->types[$blueprintID]['volume'] ?? 0;

			$ok = $this->processStep($blueprintID, "input");
			if(!$ok)
				continue;

			$ok = $this->processStep($blueprintID, "output");
			if(!$ok)
				continue;

			$query = sprintf("INSERT INTO type VALUES(%u, \"%s\", %f)", $blueprintID, $name, $volume);
			$this->sqlite->query($query);
		}
	}
}
?>