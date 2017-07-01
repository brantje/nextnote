<?php

namespace OCA\OwnNote\Lib;

\OCP\User::checkLoggedIn();
\OCP\App::checkAppEnabled('ownnote');

use DateTime;
use DOMDocument;

class Backend {

	private $userManager;

	public function __construct($userManager) {
        $this->userManager = $userManager;
	}

	public function startsWith($haystack, $needle) {
		return $needle === "" || strripos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}

	public function endsWith($string, $test) {
		$strlen = strlen($string);
		$testlen = strlen($test);
		if ($testlen > $strlen) return false;
		return substr_compare($string, $test, $strlen - $testlen, $testlen, true) === 0;
	}

	public function getAnnouncement() {
		$ret = "";
		$url = 'https://raw.githubusercontent.com/Fmstrat/announcements/master/ownnote/announcement.html';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.0; S60/3.0 NokiaN73-1/2.0(2.0617.0.0.7) Profile/MIDP-2.0 Configuration/CLDC-1.1)");
		$result = curl_exec($ch);
		if ($result === FALSE) {
			die('Curl failed: ' . curl_error($ch));
		} else {
			$ret = $result;
		}
		curl_close($ch);
		return $ret;
	}

	public function checkEvernote($folder, $file) {
		$html = "";
		if ($html = \OC\Files\Filesystem::file_get_contents($folder."/".$file)) {
			$DOM = new DOMDocument;
			$DOM->loadHTML($html);
			$items = $DOM->getElementsByTagName('meta');
			$isEvernote = false;
			for ($i = 0; $i < $items->length; $i++) {
				$item = $items->item($i);
				if ($item->hasAttributes()) {
					$attrs = $item->attributes;
					foreach ($attrs as $a => $attr) {
						if ($attr->name == "name") {
							if ($attr->value == "exporter-version" || $attr->value == "Generator") {
								$isEvernote = true;
								continue;
							}
						}
					}
				}
			}
			if ($isEvernote) {
				$items = $DOM->getElementsByTagName('img');
				$isEvernote = false;
				for ($i = 0; $i < $items->length; $i++) {
					$item = $items->item($i);
					if ($item->hasAttributes()) {
						$attrs = $item->attributes;
						foreach ($attrs as $a => $attr) {
							if ($attr->name == "src") {
								$url = $attr->value;
								if (!$this->startsWith($url, "http") && !$this->startsWith($url, "/") && !$this->startsWith($url,"data")) {
									if ($data = \OC\Files\Filesystem::file_get_contents($folder."/".$url)) {
										$type = pathinfo($url, PATHINFO_EXTENSION);
										$base64 = "data:image/".$type.";base64,".base64_encode($data);
										$html = str_replace($url, $base64, $html);
									}
								}
							}
						}
					}
				}
				\OC\Files\Filesystem::file_put_contents($folder."/".$file, $html);
			}
		}
	}

	public function getTimeString($filetime, $now, $l) {
		$difftime = $filetime->diff($now);
		$years = $difftime->y;
		$months = $difftime->m;
		$days = $difftime->d;
		$hours = $difftime->h;
		$minutes = $difftime->i;
		$seconds = $difftime->s;
		$timestring = "";
		if ($timestring == "" && $years == 1) $timestring = str_replace('#', $years, $l->t("# year ago"));
		if ($timestring == "" && $years > 0) $timestring = str_replace('#', $years, $l->t("# years ago"));
		if ($timestring == "" && $months == 1) $timestring = str_replace('#', $months, $l->t("# month ago"));
		if ($timestring == "" && $months > 0) $timestring = str_replace('#', $months, $l->t("# months ago"));
		if ($timestring == "" && $days == 1) $timestring = str_replace('#', $days, $l->t("# day ago"));
		if ($timestring == "" && $days > 0) $timestring = str_replace('#', $days, $l->t("# days ago"));
		if ($timestring == "" && $hours == 1) $timestring = str_replace('#', $hours, $l->t("# hour ago"));
		if ($timestring == "" && $hours > 0) $timestring = str_replace('#', $hours, $l->t("# hours ago"));
		if ($timestring == "" && $minutes == 1) $timestring = str_replace('#', $minutes, $l->t("# minute ago"));
		if ($timestring == "" && $minutes > 0) $timestring = str_replace('#', $minutes, $l->t("# minutes ago"));
		if ($timestring == "" && $seconds == 1) $timestring = str_replace('#', $seconds, $l->t("# second ago"));
		if ($timestring == "" && $seconds > 0) $timestring = str_replace('#', $seconds, $l->t("# seconds ago"));
		return $timestring;
	}

	public function splitContent($str) {
		$maxlength = 2621440; // 5 Megs (2 bytes per character)
		$count = 0;
		$strarray = array();
		while (true) { 
			if (strlen($str) <= $maxlength) { 
				$strarray[$count++] = $str;
				return $strarray;
			} else {
				$strarray[$count++] = substr($str, 0, $maxlength);
				$str = substr($str, $maxlength);
			}
		}
	}
	
	/**
	 * Returns a user's owned and shared notes
	 * @param string $uid the user's id
	 * @return array the owned notes (uid=uid) and shared notes (OwnnoteShareBackend)
	 */
	private function queryNotesWithUser($uid) {
		// Get owned notes
		$query = \OCP\DB::prepare("SELECT id, uid, name, grouping, mtime, deleted FROM *PREFIX*ownnote WHERE uid=? ORDER BY name");
		$results = $query->execute(Array($uid))->fetchAll();
		
		// Get shares
		$shared_items = \OCP\Share::getItemsSharedWith('ownnote', 'populated_shares');
		
		return array_merge($results, $shared_items);
	}

	public function getListing($FOLDER, $showdel) {
		// Get the listing from the database
		$requery = false;
		$uid = \OCP\User::getUser();
		$results = $this->queryNotesWithUser($uid);
		
		$results2 = $results;
		if ($results)
			foreach($results as $result)
				foreach($results2 as $result2)
					if ($result['id'] != $result2['id'] && $result['name'] == $result2['name'] && $result['grouping'] == $result2['grouping']) {
						// We have a duplicate that should not exist. Need to remove the offending record first
						$delid = -1;
						if ($result['mtime'] == $result2['mtime']) {
							// If the mtime's match, delete the oldest ID.
							$delid = $result['id'];
							if ($result['id'] > $result2['id'])
								$delid = $result2['id'];
						} elseif ($result['mtime'] > $result2['mtime']) {
							// Again, delete the oldest
							$delid = $result2['id'];
						} elseif ($result['mtime'] < $result2['mtime']) {
							// The only thing left is if result is older
							$delid = $result['id'];
						}
						if ($delid != -1) {
							$delquery = \OCP\DB::prepare("DELETE FROM *PREFIX*ownnote WHERE id=?");
							$delquery->execute(Array($delid));
							$requery = true;
						}
					}
		if ($requery) {
			$results = $this->queryNotesWithUser($uid);
			$requery = false;
		}
		// Tests to add a bunch of notes
		//$now = new DateTime();
		//for ($x = 0; $x < 199; $x++) {
			//saveNote('', "Test ".$x, '', '', $now->getTimestamp());
		//}
		$farray = array();
		if ($FOLDER != '') {
			// Create the folder if it doesn't exist
			if (!\OC\Files\Filesystem::is_dir($FOLDER)) {
				if (!\OC\Files\Filesystem::mkdir($FOLDER)) {
					\OCP\Util::writeLog('ownnote', 'Could not create ownNote directory.', \OCP\Util::ERROR);
					exit;
				}
			}
			// Synchronize files to the database
			$filearr = array();
			if ($listing = \OC\Files\Filesystem::opendir($FOLDER)) {
				if (!$listing) {
					\OCP\Util::writeLog('ownnote', 'Error listing directory.', \OCP\Util::ERROR);
					exit;
				}
				while (($file = readdir($listing)) !== false) {
					$tmpfile = $file;
					if ($tmpfile == "." || $tmpfile == "..") continue;
					if (!$this->endsWith($tmpfile, ".htm") && !$this->endsWith($tmpfile, ".html")) continue;
					if ($info = \OC\Files\Filesystem::getFileInfo($FOLDER."/".$tmpfile)) {
						// Check for EVERNOTE but wait to rename them to get around:
						// https://github.com/owncloud/core/issues/16202
						if ($this->endsWith($tmpfile, ".html")) {
							$this->checkEvernote($FOLDER, $tmpfile);
						}
						// Separate the name and group name
						$name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $tmpfile);
						$group = "";
						if (substr($name,0,1) == "[") {
							$end = strpos($name, ']');
							$group = substr($name, 1, $end-1);	
							$name = substr($name, $end+1, strlen($name)-$end+1);
							$name = trim($name);
						}
						// Set array for later checking
						$filearr[] = $tmpfile;
						// Check to see if the file is in the DB
						$fileindb = false;
						if ($results)
							foreach($results as $result)
								if ($result['deleted'] == 0)
									if ($name == $result['name'] && $group == $result['grouping']) {
										$fileindb = true;
										// If it is in the DB, check if the filesystem file is newer than the DB
										if ($result['mtime'] < $info['mtime']) {
											// File is newer, this could happen if a user updates a file
											$html = "";
											$html = \OC\Files\Filesystem::file_get_contents($FOLDER."/".$tmpfile);
											$this->saveNote('', $result['name'], $result['grouping'], $html, $info['mtime']);
											$requery = true;
										}
									}
						if (! $fileindb) {
							// If it's not in the DB, add it.
							$html = "";
							if ($html = \OC\Files\Filesystem::file_get_contents($FOLDER."/".$tmpfile)) {
							} else {
								$html = "";
							}
							$this->saveNote('', $name, $group, $html, $info['mtime']);
							$requery = true;
						}
						// We moved the rename down here to overcome the OC issue
						if ($this->endsWith($tmpfile, ".html")) {
							$tmpfile = substr($tmpfile,0,-1);
							if (!\OC\Files\Filesystem::file_exists($FOLDER."/".$tmpfile)) {
								\OC\Files\Filesystem::rename($FOLDER."/".$file, $FOLDER."/".$tmpfile);
							}
						}
					}
				}
			}
			if ($requery) {
				$results = $this->queryNotesWithUser($uid);
			}
			// Now also make sure the files exist, they may not if the user switched folders in admin.
			if ($results)
				foreach($results as $result)
					if ($result['deleted'] == 0) {
						$tmpfile = $result['name'].".htm";
						if ($result['grouping'] != '')
							$tmpfile = '['.$result['grouping'].'] '.$result['name'].'.htm';
						$filefound = false;
						foreach ($filearr as $f)
							if ($f == $tmpfile) {
								$filefound = true;
								break;
							}
						if (! $filefound) {
							$content = $this->editNote($result['name'], $result['grouping']);
							$this->saveNote($FOLDER, $result['name'], $result['grouping'], $content, 0);
						}
					}
		}
		// Now loop through and return the listing
		if ($results) {
			$count = 0;
			$now = new DateTime();
			$filetime = new DateTime();
			$l = \OCP\Util::getL10N('ownnote');
			foreach($results as $result)
				if ($result['deleted'] == 0 || $showdel == true) {
					$filetime->setTimestamp($result['mtime']);
					$timestring = $this->getTimeString($filetime, $now, $l);
					$f = array();
					$f['id'] = $result['id'];
					$f['uid'] = $result['uid'];
					$f['name'] = $result['name'];
					$f['group'] = $result['grouping'];
					$f['timestring'] = $timestring;
					$f['mtime'] = $result['mtime'];
					$f['timediff'] = $now->getTimestamp()-$result['mtime'];
					$f['deleted'] = $result['deleted'];
					$f['permissions'] = $result['permissions'];
					
					$shared_with = \OCP\Share::getUsersItemShared('ownnote', $result['id'], $result['uid']);
					// add shares (all shares, if it's an owned note, only the user for shared notes (not disclosing other sharees))
					$f['shared_with'] = ($result['uid'] == $uid) ? $shared_with : [$uid];

					$farray[$count] = $f;
					$count++;
				}
		}
		return $farray;
	}

	public function createNote($FOLDER, $in_name, $in_group) {
		$name = str_replace("\\", "-", str_replace("/", "-", $in_name));
		$group = str_replace("\\", "-", str_replace("/", "-", $in_group));
		$now = new DateTime();
		$mtime = $now->getTimestamp();
		$uid = \OCP\User::getUser();
		$fileindb = false;
		$filedeldb = false;
		$ret = -1;
		$query = \OCP\DB::prepare("SELECT id, uid, name, grouping, mtime, deleted FROM *PREFIX*ownnote WHERE name=? and grouping=? and uid=?");
		$results = $query->execute(Array($name, $group, $uid))->fetchAll();
		foreach($results as $result)
			if ($result['deleted'] == 0) {
				$fileindb = true;
				$ret = $result['id'];
			} else {
				$filedeldb = true;
			}
		if ($filedeldb) {
			$query = \OCP\DB::prepare("DELETE FROM *PREFIX*ownnote WHERE name=? and grouping=? and uid=?");
			$results = $query->execute(Array($name, $group, $uid));
		}
		// new note
		if (! $fileindb) {
			if ($FOLDER != '') {
				$tmpfile = $FOLDER."/".$name.".htm";
				if ($group != '')
					$tmpfile = $FOLDER."/[".$group."] ".$name.".htm";
				if (!\OC\Files\Filesystem::file_exists($tmpfile)) {
					\OC\Files\Filesystem::touch($tmpfile);
				}
				if ($info = \OC\Files\Filesystem::getFileInfo($tmpfile)) {
					$mtime = $info['mtime'];
				}
			}
			$query = \OCP\DB::prepare("INSERT INTO *PREFIX*ownnote (uid, name, grouping, mtime, note, shared) VALUES (?,?,?,?,?,?)");
			$query->execute(Array($uid, $name, $group, $mtime, '', ''));
			$ret = \OCP\DB::insertid('*PREFIX*ownnote');
		}
		return $ret;
	}

	public function deleteNote($FOLDER, $nid) {
	    if (!$this->checkPermissions(\OCP\Constants::PERMISSION_DELETE, $nid)) {
	        return false;
		}
		
		$now = new DateTime();
		$mtime = $now->getTimestamp();
		$uid = \OCP\User::getUser();
		$query = \OCP\DB::prepare("UPDATE *PREFIX*ownnote set note='', deleted=1, mtime=? WHERE id=?");
		$results = $query->execute(Array($mtime, $nid));
		
		$query = \OCP\DB::prepare("DELETE FROM *PREFIX*ownnote_parts WHERE id=?");
		$query->execute(Array($nid));
		if ($FOLDER != '') {
			$tmpfile = $FOLDER."/".$name.".htm";
			if ($group != '')
				$tmpfile = $FOLDER."/[".$group."] ".$name.".htm";
			if (\OC\Files\Filesystem::file_exists($tmpfile))
				\OC\Files\Filesystem::unlink($tmpfile);
		}
		return true;
	}


	public function editNote($id) {
		$retVal = "";
		$note = $this->getNote($id);
		$content = $note['note'];

		// query parts
		$query = \OCP\DB::prepare("SELECT note FROM *PREFIX*ownnote_parts WHERE id=? order by pid");
		$results = $query->execute(Array($note['id']))->fetchAll();
		foreach($results as $result) {
			$retVal .= $result['note'];
		}

		return $retVal;
	}

	public function saveNote($FOLDER, $nid, $content, $in_mtime) {
		$maxlength = 2621440; // 5 Megs (2 bytes per character)
		$now = new DateTime();
		$mtime = $now->getTimestamp();
		if ($in_mtime != 0) {
			$mtime = $in_mtime;
		}

		// get the specific note
		$note = $this->getNote($nid);
		$name = $note['name'];
		$group = $note['grouping'];
				
		if ($FOLDER != '') {
			$tmpfile = $FOLDER."/".$name.".htm";
			if ($group != '')
				$tmpfile = $FOLDER."/[".$group."] ".$name.".htm";
				\OC\Files\Filesystem::file_put_contents($tmpfile, $content);
			if ($info = \OC\Files\Filesystem::getFileInfo($tmpfile)) {
				$mtime = $info['mtime'];
			}
		}
		$query = \OCP\DB::prepare("UPDATE *PREFIX*ownnote set note='', mtime=? WHERE id=?");
		$results = $query->execute(Array($mtime, $note['id']));
		$query = \OCP\DB::prepare("DELETE FROM *PREFIX*ownnote_parts WHERE id=?");
		$results = $query->execute(Array($note['id']));
		$contentarr = $this->splitContent($content);
		for ($i = 0; $i < count($contentarr); $i++) {
			$query = \OCP\DB::prepare("INSERT INTO *PREFIX*ownnote_parts (id, note) values (?,?)");
			$results = $query->execute(Array($note['id'], $contentarr[$i]));
		}
		return true;
	}

	public function renameNote($FOLDER, $id, $in_newname, $in_newgroup) {
		$newname = str_replace("\\", "-", str_replace("/", "-", $in_newname));
		$newgroup = str_replace("\\", "-", str_replace("/", "-", $in_newgroup));
		
		// We actually need to delete and create so that the delete flag exists for syncing clients
		$content = $this->editNote($id);		
		$this->deleteNote($FOLDER, $id);
		
		$newId = $this->createNote($FOLDER, $newname, $newgroup);
		$this->saveNote($FOLDER, $newId, $content, 0);
		
		return true;
	}

	public function deleteGroup($FOLDER, $group) {
		// We actually need to just rename all the notes
		$uid = \OCP\User::getUser();
		$query = \OCP\DB::prepare("SELECT id, name, grouping, mtime FROM *PREFIX*ownnote WHERE deleted=0 and uid=? and grouping=?");
		$results = $query->execute(Array($uid, $group))->fetchAll();
		foreach($results as $result) {
			$this->renameNote($FOLDER, $result['id'], $result['name'], '');
		}
	}

	public function renameGroup($FOLDER, $group, $newgroup) {
		$uid = \OCP\User::getUser();
		$query = \OCP\DB::prepare("SELECT id, name, grouping, mtime FROM *PREFIX*ownnote WHERE deleted=0 and uid=? and grouping=?");
		$results = $query->execute(Array($uid, $group))->fetchAll();
		foreach($results as $result) {
			$this->renameNote($FOLDER, $result['id'], $result['name'], $newgroup);
		}
		return true;
	}

	public function getVersion() {
		$v = file_get_contents(__DIR__."/../appinfo/version");
		if ($v)
			return trim($v);
		else
			return "";
	}

	public function setAdminVal($option, $value) {
		\OCP\Config::setAppValue('ownnote', $option, $value);
	}
	
	private function getNote($noteid) {
		$query = \OCP\DB::prepare("SELECT id, uid, name, grouping, mtime, note, deleted FROM *PREFIX*ownnote WHERE id=?");
		return $query->execute(Array($noteid))->fetchAll()[0];
	}

	private function checkPermissions($permission, $nid) {
		// gather information
		$uid = \OCP\User::getUser();
		$note = $this->getNote($nid);
		
		// owner is allowed to change everything
		if ($uid === $note['uid']) {
			return true;
		}

		// check share permissions
		$shared_note = \OCP\Share::getItemSharedWith('ownnote', $nid, 'populated_shares')[0];
		return $shared_note['permissions'] & $permission;
	}
}

?>
