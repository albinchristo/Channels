<?php
/**
*
* @ This file is created by http://DeZender.Net
* @ deZender (PHP7 Decoder for ionCube Encoder)
*
* @ Version			:	4.1.0.0
* @ Author			:	DeZender
* @ Release on		:	15.05.2020
* @ Official site	:	http://DeZender.Net
*
*/

function plog($rText)
{
	echo '[' . date('Y-m-d h:i:s') . '] ' . $rText . "\n";
}

function getProcessCount()
{
	exec('pgrep -u mini_cs | wc -l 2>&1', $rOutput, $rRet);
	return intval($rOutput[0]);
}

function getKeyCache($rKey)
{
	if (file_exists(MAIN_DIR . 'cache/keystore/' . $rKey . '.key')) {
		return file_get_contents(MAIN_DIR . 'cache/keystore/' . $rKey . '.key');
	}

	return NULL;
}

function setKeyCache($rKey, $rValue)
{
	file_put_contents(MAIN_DIR . 'cache/keystore/' . $rKey . '.key', $rValue);

	if (file_exists(MAIN_DIR . 'cache/keystore/' . $rKey . '.key')) {
		return true;
	}

	return false;
}

function openCache($rChannel)
{
	if (file_exists(MAIN_DIR . 'cache/' . $rChannel . '.db')) {
		return json_decode(file_get_contents(MAIN_DIR . 'cache/' . $rChannel . '.db'), true);
	}

	return [];
}

function deleteCache($rChannel)
{
	if (file_exists(MAIN_DIR . 'cache/' . $rChannel . '.db')) {
		unlink(MAIN_DIR . 'cache/' . $rChannel . '.db');
	}

	return [];
}

function clearCache($rDatabase, $rID)
{
	unset($rDatabase[$rID]);
	return $rDatabase;
}

function getCache($rDatabase, $rID)
{
	if (isset($rDatabase[$rID])) {
		return $rDatabase[$rID]['value'];
	}

	return NULL;
}

function setCache($rDatabase, $rID, $rValue)
{
	global $rCacheTime;
	$rDatabase[$rID] = ['value' => $rValue, 'expires' => time() + $rCacheTime];
	return $rDatabase;
}

function saveCache($rChannel, $rDatabase)
{
	file_put_contents(MAIN_DIR . 'cache/' . $rChannel . '.db', json_encode($rDatabase));
}

function getPersistence()
{
	if (file_exists(MAIN_DIR . 'config/persistence.db')) {
		$rPersistence = json_decode(file_get_contents(MAIN_DIR . 'config/persistence.db'), true);
	}
	else {
		$rPersistence = [];
	}

	return $rPersistence;
}

function addPersistence($rScript, $rChannel)
{
	$rPersistence = getpersistence();

	if (!in_array($rChannel, $rPersistence[$rScript])) {
		$rPersistence[$rScript][] = $rChannel;
	}

	file_put_contents(MAIN_DIR . 'config/persistence.db', json_encode($rPersistence));
}

function removePersistence($rScript, $rChannel)
{
	$rPersistence = getpersistence();

	if (($rKey = array_search($rChannel, $rPersistence[$rScript])) !== false) {
		unset($rPersistence[$rScript][$rKey]);
	}

	file_put_contents(MAIN_DIR . 'config/persistence.db', json_encode($rPersistence));
}

function getKey($rType, $rData, $rMD5 = NULL)
{
	if ($rType == 'sling') {
		return json_decode(getURL('http://wvslingtv-drm.ddns.net:777/?pssh=' . urlencode($rData), 30), true);
	}

	if ($rType == 'dstv') {
		return json_decode(getURL('http://wvslingtv-drm.ddns.net:777/?pssh=' . urlencode($rData), 30), true);
	}

	if ($rType == 'bell') {
		return json_decode(getURL('http://wvslingtv-drm.ddns.net:777/?id=' . urlencode($rData) . '&md5=' . urlencode($rMD5), 30), true);
	}
}

function combineSegment($rVideo, $rAudio, $rOutput)
{
	global $rFFMpeg;
	$rWait = exec($rFFMpeg . ' -hide_banner -loglevel panic -y -nostdin -i "' . $rVideo . '" -i "' . $rAudio . '" -c:v copy -c:a copy -strict experimental "' . $rOutput . '" ');
	return file_exists($rOutput);
}

function decryptSegment($rKey, $rInput, $rOutput)
{
	global $rMP4Decrypt;

	if (is_array($rKey)) {
		$rKeyArray = [];
		$i = 0;

		while (count($rKey) < $i) {
			$rKeyString = join(' ', $rKeyArray);
			$rWait = exec($rMP4Decrypt . ' ' . $rKeyString . ' ' . $rInput . ' ' . $rOutput . ' 2>&1 &');
		}

		$rKeyArray[] = '--key ' . $i . ':' . $rKey[$i - 1];
		$i++;
	}
	else {
		$rWait = exec($rMP4Decrypt . ' --key 1:' . $rKey . ' --key 2:' . $rKey . ' ' . $rInput . ' ' . $rOutput . ' 2>&1 &');
	}

	return file_exists($rOutput);
}

function clearSegments($rChannel, $rLimit = NULL)
{
	global $rMaxSegments;
	global $rVideoDir;

	if (!$rLimit) {
		$rLimit = $rMaxSegments;
	}
	$rFiles = glob($rVideoDir . '/' . $rChannel . '/final/*.mp4');
	usort($rFiles, function($a, $b) {
		return filemtime($a) - filemtime($b);
	});
	$rKeep = array_slice($rFiles, -1 * $rLimit, $rLimit, true);

	foreach ($rFiles as $rFile) {
		if (!in_array($rFile, $rKeep)) {
			unlink($rFile);
		}
	}
}

function clearMD5Cache($rChannel, $rLimit = 60)
{
	global $rVideoDir;
	$rFiles = glob($rVideoDir . '/' . $rChannel . '/cache/*.md5');
	usort($rFiles, function($a, $b) {
		return filemtime($a) - filemtime($b);
	});
	$rKeep = array_slice($rFiles, -1 * $rLimit, $rLimit, true);

	foreach ($rFiles as $rFile) {
		if (!in_array($rFile, $rKeep)) {
			unlink($rFile);
		}
	}
}

function updateSegments($rDirectory, $rSampleSize = 10, $rHex = true, $rSize = 43200, $rMultiplier = 1)
{
	$rFiles = glob($rDirectory . '/final/*.mp4');
	usort($rFiles, function($a, $b) {
		return filemtime($a) - filemtime($b);
	});
	$rFiles = array_slice($rFiles, -1 * $rSampleSize, $rSampleSize, true);
	$rMin = NULL;
	$rMax = NULL;

	foreach ($rFiles as $rFile) {
		if ($rHex) {
			$rInt = intval(hexdec(explode('.', basename($rFile))[0]));
		}
		else {
			$rInt = intval(explode('.', basename($rFile))[0]);
		}
		if (!$rMin || ($rInt < $rMin)) {
			$rMin = $rInt;
		}
		if (!$rMax || ($rMax < $rInt)) {
			$rMax = $rInt;
		}
	}

	if ($rMin) {
		$rOutput = '';

		foreach (range(0, $rSize) as $rAdd) {
			if ($rHex) {
				$rPath = $rDirectory . '/final/' . dechex($rMin + ($rAdd * $rMultiplier)) . '.mp4';
			}
			else {
				$rPath = $rDirectory . '/final/' . ($rMin + ($rAdd * $rMultiplier)) . '.mp4';
			}
			if (file_exists($rPath) || ($rMax < ($rMin + ($rAdd * $rMultiplier)))) {
				$rOutput .= 'file \'' . $rPath . '\'' . "\n";
			}
		}

		file_put_contents($rDirectory . '/playlist.txt', $rOutput);
	}
}

function getBellChannel($rChannel)
{
	return json_decode(file_get_contents('http://wvslingtv-drm.ddns.net:777?id=' . urlencode($rChannel)), true);
}

function getBellSegments($rChannelData, $rLimit = NULL)
{
	global $rMaxSegments;

	foreach (range(1, 1) as $rRetry) {
		$rUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36';
		$rOptions = [
			'http' => ['method' => 'GET', 'header' => 'User-Agent: ' . $rUA . "\r\n"]
		];
		$rContext = stream_context_create($rOptions);
		$rData = file_get_contents($rChannelData['manifest'], false, $rContext);

		if (strpos($rData, '<MPD') !== false) {
			$rMPD = simplexml_load_string($rData);
			$rBaseURL = $rChannelData['baseurl'] . $rMPD->Period->BaseURL;
			$rVideoStart = NULL;
			$rVideoTemplate = NULL;
			$rObject = [
				'audio'    => NULL,
				'video'    => NULL,
				'segments' => [],
				'add'      => 100
			];

			foreach ($rMPD->Period->AdaptationSet as $rAdaptationSet) {
				if ($rAdaptationSet->attributes()['contentType'] == 'video') {
					$rBandwidth = $rAdaptationSet->Representation[count($rAdaptationSet->Representation) - 1]->attributes()['bandwidth'];
					$rVideoTemplate = str_replace('$Bandwidth$', $rBandwidth, $rAdaptationSet->SegmentTemplate[0]->attributes()['media']);
					$rInitSegment = str_replace('$Bandwidth$', $rBandwidth, $rBaseURL . $rAdaptationSet->SegmentTemplate[0]->attributes()['initialization']);
					$rObject['video'] = $rInitSegment;

					foreach ($rAdaptationSet->SegmentTemplate->SegmentTimeline->S as $rSegment) {
						if (isset($rSegment->attributes()['t'])) {
							$rVideoStart = $rSegment->attributes()['t'];
							$rObject['add'] = $rSegment->attributes()['d'];
						}
					}
				}
			}

			foreach ($rMPD->Period->AdaptationSet as $rAdaptationSet) {
				if ($rAdaptationSet->attributes()['contentType'] == 'audio') {
					$rThisSegment = NULL;
					$rBandwidth = $rAdaptationSet->Representation[0]->attributes()['bandwidth'];
					$rSegmentTemplate = str_replace('$Bandwidth$', $rBandwidth, $rAdaptationSet->SegmentTemplate[0]->attributes()['media']);
					$rInitSegment = str_replace('$Bandwidth$', $rBandwidth, $rBaseURL . $rAdaptationSet->SegmentTemplate[0]->attributes()['initialization']);
					$rObject['audio'] = $rInitSegment;

					foreach ($rAdaptationSet->SegmentTemplate->SegmentTimeline->S as $rSegment) {
						if (isset($rSegment->attributes()['t'])) {
							$rThisSegment = $rSegment->attributes()['t'];
							$rObject['segments'][$rVideoStart]['audio'] = str_replace('$Time$', $rThisSegment, $rBaseURL . $rSegmentTemplate);
							$rObject['segments'][$rVideoStart]['video'] = str_replace('$Time$', $rVideoStart, $rBaseURL . $rVideoTemplate);
						}

						if (isset($rSegment->attributes()['r'])) {
							$rRepeats = intval($rSegment->attributes()['r']) + 1;
						}
						else {
							$rRepeats = 19;
						}

						foreach (range(1, $rRepeats) as $rRepeat) {
							$rThisSegment += intval($rSegment->attributes()['d']);
							$rVideoStart += $rObject['add'];
							$rObject['segments'][$rVideoStart]['audio'] = str_replace('$Time$', $rThisSegment, $rBaseURL . $rSegmentTemplate);
							$rObject['segments'][$rVideoStart]['video'] = str_replace('$Time$', $rVideoStart, $rBaseURL . $rVideoTemplate);
						}
					}

					if (!$rLimit) {
						$rLimit = $rMaxSegments;
					}

					$rObject['segments'] = array_slice($rObject['segments'], -1 * $rLimit, $rLimit, true);
					return $rObject;
				}
			}
		}
	}
}

function getDSTVChannel($rChannel)
{
	return json_decode(getURL('http://wvslingtv-drm.ddns.net?id=' . urlencode($rChannel), 30), true);
}

function getDSTVSegments($rURL, $rLimit = NULL)
{
	global $rMaxSegments;
	$rMPD = simplexml_load_string(getURL($rURL . '.mpd'));
	$rBaseURL = $rURL . $rMPD->Period->BaseURL;

	foreach ($rMPD->Period->AdaptationSet[0]->ContentProtection as $rContentProtection) {
		if (strtolower($rContentProtection->attributes()->schemeIdUri) == 'urn:uuid:edef8ba9-79d6-4ace-a3c8-27dcd51d21ed') {
			list($rPSSH) = explode('</cenc:pssh>', explode('<cenc:pssh>', $rContentProtection->asXML())[1]);

			if (!$rPSSH) {
				list($rPSSH) = explode('<', explode('>', $rContentProtection->pssh->asXML())[1]);
			}

			$rVideoStart = NULL;
			$rVideoTemplate = NULL;
			$rObject = [
				'pssh'     => $rPSSH,
				'audio'    => NULL,
				'video'    => NULL,
				'segments' => [],
				'add'      => 100
			];

			foreach ($rMPD->Period->AdaptationSet as $rAdaptationSet) {
				$rRepID = $rAdaptationSet->Representation[count($rAdaptationSet->Representation) - 1]->attributes()['id'];
				$rVideoTemplate = str_replace('$RepresentationID$', $rRepID, $rAdaptationSet->SegmentTemplate[0]->attributes()['media']);
				$rInitSegment = str_replace('$RepresentationID$', $rRepID, $rBaseURL . $rAdaptationSet->SegmentTemplate[0]->attributes()['initialization']);

				if ($rAdaptationSet->attributes()['contentType'] == 'video') {
					$rObject['video'] = $rInitSegment;

					foreach ($rAdaptationSet->SegmentTemplate->SegmentTimeline->S as $rSegment) {
						if (isset($rSegment->attributes()['t'])) {
							$rVideoStart = $rSegment->attributes()['t'];
							$rObject['add'] = $rSegment->attributes()['d'];
						}
					}
				}
			}

			foreach ($rMPD->Period->AdaptationSet as $rAdaptationSet) {
				$rThisSegment = NULL;
				$rRepID = $rAdaptationSet->Representation[0]->attributes()['id'];
				$rSegmentTemplate = str_replace('$RepresentationID$', $rRepID, $rAdaptationSet->SegmentTemplate[0]->attributes()['media']);
				$rInitSegment = str_replace('$RepresentationID$', $rRepID, $rBaseURL . $rAdaptationSet->SegmentTemplate[0]->attributes()['initialization']);

				if ($rAdaptationSet->attributes()['contentType'] == 'audio') {
					$rObject['audio'] = $rInitSegment;

					foreach ($rAdaptationSet->SegmentTemplate->SegmentTimeline->S as $rSegment) {
						if (isset($rSegment->attributes()['t'])) {
							$rThisSegment = $rSegment->attributes()['t'];
							$rObject['segments'][$rVideoStart]['audio'] = str_replace('$Time$', $rThisSegment, $rBaseURL . $rSegmentTemplate);
							$rObject['segments'][$rVideoStart]['video'] = str_replace('$Time$', $rVideoStart, $rBaseURL . $rVideoTemplate);
						}

						if (isset($rSegment->attributes()['r'])) {
							$rRepeats = intval($rSegment->attributes()['r']) + 1;
						}
						else {
							$rRepeats = 18;
						}

						foreach (range(1, $rRepeats) as $rRepeat) {
							$rThisSegment += intval($rSegment->attributes()['d']);
							$rVideoStart += $rObject['add'];
							$rObject['segments'][$rVideoStart]['audio'] = str_replace('$Time$', $rThisSegment, $rBaseURL . $rSegmentTemplate);
							$rObject['segments'][$rVideoStart]['video'] = str_replace('$Time$', $rVideoStart, $rBaseURL . $rVideoTemplate);
						}
					}

					if (!$rLimit) {
						$rLimit = $rMaxSegments;
					}

					$rObject['segments'] = array_slice($rObject['segments'], -1 * $rLimit, $rLimit, true);
					return $rObject;
				}
			}
		}
	}
}

function downloadFile($rInput, $rOutput, $rPHP = false)
{
	$rInput = str_replace('vid06', 'vid04', $rInput);

	if ($rPHP) {
		file_put_contents($rOutput, file_get_contents($rInput));
	}
	else {
		$rWait = exec('curl "' . $rInput . '" --output "' . $rOutput . '"');
	}
	if (file_exists($rOutput) && (0 < filesize($rOutput))) {
		return true;
	}

	return false;
}

function getURL($rURL, $rTimeout = 5)
{
	$rContext = stream_context_create([
		'http' => ['timeout' => $rTimeout]
	]);
	return file_get_contents($rURL, false, $rContext);
}

function downloadFiles($rList, $rOutput, $rUA = NULL)
{
	global $rAria;
	$rTimeout = count($rList);

	if ($rTimeout < 3) {
		$rTimeout = 12;
	}

	if (0 < count($rList)) {
		$rURLs = join("\n", $rList);
		$rURLs = str_replace('vid06', 'vid04', $rURLs);
		$rTempList = MAIN_DIR . 'tmp/' . md5($rURLs) . '.txt';
		file_put_contents($rTempList, $rURLs);

		if ($rUA) {
			exec($rAria . ' -U "' . $rUA . '" --connect-timeout=3 --timeout=' . $rTimeout . ' -i "' . $rTempList . '" --dir "' . $rOutput . '" 2>&1', $rOut, $rRet);
		}
		else {
			exec($rAria . ' --connect-timeout=3 --timeout=' . $rTimeout . ' -i "' . $rTempList . '" --dir "' . $rOutput . '" 2>&1', $rOut, $rRet);
		}

		unlink($rTempList);
	}

	return true;
}

function processSlingSegments($rKeys, $rSegments, $rDirectory)
{
	$rCompleted = 33;
	$rDownloadPath = $rDirectory . '/aria/';

	foreach ($rSegments['segments'] as $rSegmentID => $rSegment) {
		$rVideoInit = $rSegment['video']['init'];
		$rVideoMD5 = md5($rVideoInit);
		if (!file_exists($rDirectory . '/encrypted/' . $rVideoMD5 . '.mp4') || (filesize($rDirectory . '/encrypted/' . $rVideoMD5 . '.mp4') == 0)) {
			downloadfile($rVideoInit, $rDirectory . '/encrypted/' . $rVideoMD5 . '.mp4', true);
		}

		$rAudioInit = $rSegment['audio']['init'];
		$rAudioMD5 = md5($rAudioInit);
		if (!file_exists($rDirectory . '/encrypted/' . $rAudioMD5 . '.mp4') || (filesize($rDirectory . '/encrypted/' . $rAudioMD5 . '.mp4') == 0)) {
			downloadfile($rAudioInit, $rDirectory . '/encrypted/' . $rAudioMD5 . '.mp4', true);
		}
	}

	foreach (['audio', 'video'] as $rType) {
		$rDownloads = [];
		$rDownloadMap = [];

		foreach ($rSegments['segments'] as $rSegmentID => $rSegment) {
			$rFinalPath = $rDirectory . '/final/' . $rSegmentID . '.mp4';

			if (!file_exists($rFinalPath)) {
				$rDownloads[] = $rSegment[$rType]['segment'];
				$rDownloadMap[$rSegment[$rType]['segment']] = $rSegmentID;
			}
		}

		plog('Downloading ' . count($rDownloads) . ' ' . $rType . ' segments...');
		$rRetryEnabled = 'yes';

		if ($rRetryEnabled) {
			$rRetryRange = range(1, 10);
		}
		else {
			$rRetryRange = [1];
		}

		downloadfiles($rDownloads, $rDownloadPath);
		$rRetryDownloads = [];

		foreach ($rDownloads as $rURL) {
			$rBaseName = basename($rURL);
			$rMap = $rDownloadMap[$rURL];
			$rPath = $rDownloadPath . $rBaseName;
			if (file_exists($rPath) && (0 < filesize($rPath))) {
				rename($rPath, $rDirectory . '/encrypted/' . $rMap . '.' . $rType . '.m4s');
			}
			else {
				$rRetryDownloads[] = $rURL;
			}
		}

		if (0 < count($rRetryDownloads)) {
			foreach ($rRetryRange as $rRetry) {
				downloadfiles($rDownloads, $rDownloadPath);
				$rRetryDownloads = [];

				foreach ($rDownloads as $rURL) {
					sleep(1);
					$rBaseName = basename($rURL);
					$rMap = $rDownloadMap[$rURL];
					$rPath = $rDownloadPath . $rBaseName;
					if (file_exists($rPath) && (0 < filesize($rPath))) {
						rename($rPath, $rDirectory . '/encrypted/' . $rMap . '.' . $rType . '.m4s');
					}
					else {
						$rRetryDownloads[] = $rURL;
						$rDownloads = $rRetryDownloads;
						plog('[ERROR] Failed to download ' . count($rDownloads) . ' segments.');
						copy(MAIN_DIR . 'video/audio.mp4', $rDirectory . '/encrypted/' . $rMap . '.' . $rType . '.m4s');
						plog('Trying sticking ' . MAIN_DIR . 'video/audio.mp4 ' . $rDirectory . '/encrypted/' . $rMap . '.' . $rType . '.m4s');

						foreach ($rDownloads as $rURL) {
							plog('[URL] ' . $rURL);
						}
					}
				}
			}
		}
	}

	$rFailures = 0;

	foreach ($rSegments['segments'] as $rSegmentID => $rSegment) {
		$rVideoInit = md5($rSegment['video']['init']);
		$rAudioInit = md5($rSegment['audio']['init']);
		$rKey = $rKeys[$rSegment['pssh']];
		$rFinalPath = $rDirectory . '/final/' . $rSegmentID . '.mp4';

		if (!file_exists($rFinalPath)) {
			plog('Processing segment: ' . $rSegment['hex'] . ' - ' . $rSegmentID);

			if (file_exists($rDirectory . '/encrypted/' . $rSegmentID . '.video.m4s')) {
				exec('cat "' . $rDirectory . '/encrypted/' . $rVideoInit . '.mp4" "' . $rDirectory . '/encrypted/' . $rSegmentID . '.video.m4s" > "' . $rDirectory . '/encrypted/' . $rSegmentID . '.video.complete.m4s"');
				$rVideoPath = $rDirectory . '/decrypted/' . $rSegmentID . '.video.mp4';

				if (!decryptsegment($rKey, $rDirectory . '/encrypted/' . $rSegmentID . '.video.complete.m4s', $rVideoPath)) {
					plog('[ERROR] Failed to decrypt segment!');
				}
			}
			else {
				plog('[ERROR] Encrypted Video segment is missing!');
			}

			if (file_exists($rDirectory . '/encrypted/' . $rSegmentID . '.audio.m4s')) {
				exec('cat "' . $rDirectory . '/encrypted/' . $rAudioInit . '.mp4" "' . $rDirectory . '/encrypted/' . $rSegmentID . '.audio.m4s" > "' . $rDirectory . '/encrypted/' . $rSegmentID . '.audio.complete.m4s"');
				$rAudioPath = $rDirectory . '/decrypted/' . $rSegmentID . '.audio.mp4';

				if (!decryptsegment($rKey, $rDirectory . '/encrypted/' . $rSegmentID . '.audio.complete.m4s', $rAudioPath)) {
					plog('[ERROR] Failed to decrypt segment!');
				}
			}
			else {
				plog('[ERROR] Encrypted Audio segment is missing!');
			}

			if (file_exists($rVideoPath)) {
				if (file_exists($rAudioPath)) {
					plog('Combining segments...');
					combinesegment($rVideoPath, $rAudioPath, $rFinalPath);
				}
				else {
					plog('[ERROR] Copying video without audio...');
					copy($rVideoPath, $rFinalPath);
				}
			}
			else {
				if (1 < $rSegmentID) {
					plog('[ERROR] Segments don\'t exist to combine! Replace with previous segment!');
					$rPrevSegment = $rSegmentID - 1;
					$rPrevPath = $rDirectory . '/final/' . $rPrevSegment . '.mp4';

					if (file_exists($rPrevPath)) {
						copy($rPrevPath, $rFinalPath);
					}
				}

				$rFailures++;
			}

			$rMD5 = md5($rSegment['hex']);
			file_put_contents($rDirectory . '/cache/' . $rMD5 . '.md5', '');
			unlink($rDirectory . '/encrypted/' . $rSegmentID . '.video.m4s');
			unlink($rDirectory . '/encrypted/' . $rSegmentID . '.audio.m4s');
			unlink($rDirectory . '/encrypted/' . $rSegmentID . '.video.complete.m4s');
			unlink($rDirectory . '/encrypted/' . $rSegmentID . '.audio.complete.m4s');
			unlink($rVideoPath);
			unlink($rAudioPath);

			if (file_exists($rFinalPath)) {
				$rCompleted++;
			}
		}
	}

	return [count($rDownloads), $rCompleted, $rFailures];
}

function processSegments($rKey, $rSegments, $rDirectory, $rUA = NULL)
{
	$rCompleted = 23;
	if (!file_exists($rDirectory . '/encrypted/init.audio.mp4') || (filesize($rDirectory . '/encrypted/init.audio.mp4') == 0)) {
		downloadfile($rSegments['audio'], $rDirectory . '/encrypted/init.audio.mp4', true);
	}
	if (!file_exists($rDirectory . '/encrypted/init.video.mp4') || (filesize($rDirectory . '/encrypted/init.video.mp4') == 0)) {
		downloadfile($rSegments['video'], $rDirectory . '/encrypted/init.video.mp4', true);
	}

	$rDownloadPath = $rDirectory . '/aria/';

	foreach (['audio', 'video'] as $rType) {
		$rDownloads = [];
		$rDownloadMap = [];

		foreach ($rSegments['segments'] as $rSegmentID => $rSegment) {
			$rFinalPath = $rDirectory . '/final/' . $rSegmentID . '.mp4';

			if (!file_exists($rFinalPath)) {
				$rDownloads[] = $rSegment[$rType];
				$rDownloadMap[$rSegment[$rType]] = $rSegmentID;
			}
		}

		plog('Downloading ' . count($rDownloads) . ' ' . $rType . ' segments...');
		downloadfiles($rDownloads, $rDownloadPath, $rUA);

		foreach ($rDownloads as $rURL) {
			$rBaseName = basename($rURL);
			$rMap = $rDownloadMap[$rURL];
			$rPath = $rDownloadPath . $rBaseName;
			if (file_exists($rPath) && (0 < filesize($rPath))) {
				rename($rPath, $rDirectory . '/encrypted/' . $rMap . '.' . $rType . '.m4s');
			}
		}
	}

	foreach ($rSegments['segments'] as $rSegmentID => $rSegment) {
		$rFinalPath = $rDirectory . '/final/' . $rSegmentID . '.mp4';

		if (!file_exists($rFinalPath)) {
			plog('Processing segment: ' . $rSegmentID);

			if (file_exists($rDirectory . '/encrypted/' . $rSegmentID . '.video.m4s')) {
				exec('cat "' . $rDirectory . '/encrypted/init.video.mp4" "' . $rDirectory . '/encrypted/' . $rSegmentID . '.video.m4s" > "' . $rDirectory . '/encrypted/' . $rSegmentID . '.video.complete.m4s"');
				$rVideoPath = $rDirectory . '/decrypted/' . $rSegmentID . '.video.mp4';

				if (!decryptsegment($rKey, $rDirectory . '/encrypted/' . $rSegmentID . '.video.complete.m4s', $rVideoPath)) {
					plog('[ERROR] Failed to decrypt segment!');
				}
			}
			else {
				plog('[ERROR] Encrypted Video segment is missing!');
			}

			if (file_exists($rDirectory . '/encrypted/' . $rSegmentID . '.audio.m4s')) {
				exec('cat "' . $rDirectory . '/encrypted/init.audio.mp4" "' . $rDirectory . '/encrypted/' . $rSegmentID . '.audio.m4s" > "' . $rDirectory . '/encrypted/' . $rSegmentID . '.audio.complete.m4s"');
				$rAudioPath = $rDirectory . '/decrypted/' . $rSegmentID . '.audio.mp4';

				if (is_array($rKey)) {
					$rAudioKey = end($rKey);
				}
				else {
					$rAudioKey = $rKey;
				}

				if (!decryptsegment($rAudioKey, $rDirectory . '/encrypted/' . $rSegmentID . '.audio.complete.m4s', $rAudioPath)) {
					plog('[ERROR] Failed to decrypt segment!');
				}
			}
			else {
				plog('[ERROR] Encrypted Audio segment is missing!');
			}
			if (file_exists($rVideoPath) && file_exists($rAudioPath)) {
				plog('Combining segments...');
				combinesegment($rVideoPath, $rAudioPath, $rFinalPath);
			}
			else {
				plog('[ERROR] Segments don\'t exist to combine!');
			}

			unlink($rDirectory . '/encrypted/' . $rSegmentID . '.video.m4s');
			unlink($rDirectory . '/encrypted/' . $rSegmentID . '.audio.m4s');
			unlink($rDirectory . '/encrypted/' . $rSegmentID . '.video.complete.m4s');
			unlink($rDirectory . '/encrypted/' . $rSegmentID . '.audio.complete.m4s');
			unlink($rVideoPath);
			unlink($rAudioPath);

			if (file_exists($rFinalPath)) {
				$rCompleted++;
			}
		}
	}

	return [count($rDownloads), $rCompleted];
}

function startPlaylist($rChannel)
{
	global $rFFMpeg;
	$rPlaylist = MAIN_DIR . 'video/' . $rChannel . '/playlist.txt';

	if (file_exists($rPlaylist)) {
		$rOutput = MAIN_DIR . 'hls/' . $rChannel . '/hls/playlist.m3u8';
		$rFormat = MAIN_DIR . 'hls/' . $rChannel . '/hls/segment%d.ts';

		if (!file_exists($rOutput)) {
			$rTime = time();
			$rPID = exec($rFFMpeg . ' -y -nostdin -hide_banner -nofix_dts -start_at_zero -copyts -vsync 0 -correct_ts_overflow 0 -avoid_negative_ts disabled -max_interleave_delta 0 -re -probesize 9000000 -analyzeduration 9000000 -f concat -safe 0 -i \'' . $rPlaylist . '\' -vcodec copy -scodec copy -acodec copy -individual_header_trailer 0 -f segment -segment_format mpegts -segment_time 6 -segment_list_size 10 -segment_format_options mpegts_flags=+initial_discontinuity:mpegts_copyts=1 -segment_list_type m3u8 -segment_list_flags +live+delete -segment_list \'' . $rOutput . '\' \'' . $rFormat . '\' > ' . MAIN_DIR . 'logs/ffmpeg/' . $rChannel . '_' . $rTime . '.log 2>&1 & echo $!;', $rScriptOut);
			return $rPID;
		}
	}
}

function getMPDInfo($rID)
{
	$rMPDInfo = json_decode(geturl('http://cbd46b77.cdn.cms.movetv.com/cms/api/channels/' . $rID . '/schedule/now/playback_info.qvt'), true);
	$rManifestURL = $rMPDInfo['playback_info']['dash_manifest_url'];
	$rDash = geturl($rManifestURL);

	foreach ($rMPDInfo['playback_info']['clips'] as $rClip) {
		if (isset($rClip['location']) && $rClip['location'] && ($rClip['location'] !== $rQMXUrl)) {
			$rQMXData = getQMX($rClip['location']);

			if ($rQMXData['live']) {
				$rQMXUrl = $rClip['location'];
				$rPeriod = simplexml_load_string($rDash);
				return [$rPeriod, $rQMXUrl, $rQMXData];
			}
		}
	}
}

function getQMX($rURL)
{
	foreach (range(1, 1) as $rRetry) {
		$rData = json_decode(geturl($rURL), true);

		if ($rData) {
			return $rData;
		}
	}

	return NULL;
}

function getStreamInfo($rID)
{
	global $rFFProbe;
	list($rPlaylist) = array_slice(glob(MAIN_DIR . 'hls/' . $rID . '/hls/*.ts'), -1);
	$rOutput = '';

	if (file_exists($rPlaylist)) {
		exec($rFFProbe . ' -v quiet -print_format json -show_streams -show_format "' . $rPlaylist . '" 2>&1', $rOutput, $rRet);
	}

	return json_encode(json_decode(join("\n", $rOutput), true));
}

function getMissingSegments($rID, $rMax, $rLimit)
{
	$rReturn = [];

	if (0 < $rMax) {
		$rMin = ($rMax - $rLimit) + 1;

		if ($rMin <= 0) {
			$rMin = 12;
		}

		$rSegments = [];

		foreach (glob(MAIN_DIR . 'video/' . $rID . '/final/*.mp4') as $rFile) {
			$rSegments[] = intval(hexdec(explode('.', basename($rFile))[0]));
		}

		foreach (range($rMin, $rMax) as $rInt) {
			if (!in_array($rInt, $rSegments)) {
				$rReturn[] = dechex($rInt);
			}
		}
	}

	return $rReturn;
}

function updateSlingSegments($rDirectory, $rCurrentSegment, $rSampleSize = 10, $rSize = 302400)
{
	$rStart = $rCurrentSegment - $rSampleSize;

	if ($rStart <= 0) {
		$rStart = 9;
	}

	$rOutput = '';

	foreach (range($rStart, $rStart + $rSize) as $rSegmentID) {
		$rPath = $rDirectory . '/final/' . $rSegmentID . '.mp4';
		$rOutput .= 'file \'' . $rPath . '\'' . "\n";
	}

	file_put_contents($rDirectory . '/playlist.txt', $rOutput);
}

function getSlingSegments($rMPD, $rLimit = 15)
{
	$rSegments = [
		'pssh'     => [],
		'segments' => [],
		'expires'  => NULL
	];
	$rStartTime = strtotime($rMPD->attributes()['availabilityStartTime']);

	if (!$rStartTime) {
		$rStartTime = strtotime(str_replace(':00Z', ':09Z', $rMPD->attributes()['publishTime']));
	}

	if (!$rStartTime) {
		return $rSegments;
	}

	$rSegmentDuration = floatval(str_replace('S', '', str_replace('PT', '', $rMPD->attributes()['maxSegmentDuration'])));
	$rDelay = 0;
	$rTime = time();
	$rElapsed = $rTime - $rStartTime - $rDelay;

	if ($rElapsed < 0) {
		$rElapsed = 15;
	}

	foreach ($rMPD->Period as $rPeriod) {
		$rPeriodStart = floatval(str_replace('S', '', str_replace('PT', '', $rPeriod->attributes()['start'])));

		if ($rPeriodStart <= $rElapsed) {
			$rPeriodElapsed = $rElapsed - $rPeriodStart;
			$rDuration = floatval(str_replace('S', '', str_replace('PT', '', $rPeriod->attributes()['duration'])));
			$rStartNumber = intval($rPeriod->AdaptationSet[0]->SegmentTemplate->attributes()['startNumber']);
			$rSegments['expires'] = $rStartTime + $rDuration;
			$rCurrentSegment = floor($rStartNumber + ($rPeriodElapsed / $rSegmentDuration));
			$rBaseURL = $rPeriod->BaseURL[0];
			$rPSSH = NULL;

			foreach ($rPeriod->AdaptationSet[0]->ContentProtection as $rContentProtection) {
				if ($rContentProtection->attributes()->schemeIdUri == 'urn:uuid:edef8ba9-79d6-4ace-a3c8-27dcd51d21ed') {
					list($rPSSH) = explode('</cenc:pssh>', explode('<cenc:pssh>', $rContentProtection->asXML())[1]);

					if ($rPSSH) {
						if (!in_array($rPSSH, $rSegments['pssh'])) {
							$rSegments['pssh'][] = $rPSSH;
						}

						$rAdaptationData = [];

						foreach ($rPeriod->AdaptationSet as $rAdaptationSet) {
							$rRepID = $rAdaptationSet->Representation[0]->attributes()['id'];
							$rType = $rAdaptationSet->attributes()['contentType'];
							$rAdaptationData[strval($rType)] = ['init' => str_replace('$RepresentationID$', $rRepID, $rBaseURL . $rAdaptationSet->SegmentTemplate[0]->attributes()['initialization']), 'template' => str_replace('$RepresentationID$', $rRepID, $rBaseURL . $rAdaptationSet->SegmentTemplate[0]->attributes()['media'])];
						}

						foreach (range($rCurrentSegment - $rLimit, $rCurrentSegment) as $x) {
							if (0 < $x) {
								$rSegmentID = dechex(intval($x));
								$rSegmentArray = [
									'number' => $x,
									'hex'    => $rSegmentID,
									'pssh'   => $rPSSH,
									'audio'  => ['init' => NULL, 'segment' => NULL],
									'video'  => ['init' => NULL, 'segment' => NULL]
								];

								foreach (['audio', 'video'] as $rType) {
									$rSegmentArray[$rType]['init'] = $rAdaptationData[$rType]['init'];
									$rSegmentArray[$rType]['segment'] = str_replace('$Number%08x$', $rSegmentID, $rAdaptationData[$rType]['template']);
								}

								if (count($rSegments['segments']) == $rLimit) {
									array_shift($rSegments['segments']);
								}

								$rSegments['segments'][] = $rSegmentArray;
							}
						}
					}
				}
			}
		}
	}

	return $rSegments;
}

function getSegments($rPeriod, $rQMXUrl, $rQMXData = NULL, $rLastSegment = NULL, $rLimit = NULL)
{
	global $rMaxSegments;

	if (!$rQMXData) {
		$rQMXData = getqmx($rQMXUrl);
	}

	if (!$rQMXData) {
		return NULL;
	}

	if (!$rLimit) {
		$rLimit = $rMaxSegments;
	}

	$rCurrentSegment = $rQMXData['segment_info']['stop'];

	if (!$rLastSegment) {
		$rLastSegment = $rCurrentSegment - $rLimit;
	}

	if ($rLastSegment < 0) {
		$rLastSegment = 13;
	}

	$rBaseURL = $rPeriod->Period->BaseURL[0];

	foreach ($rPeriod->Period->AdaptationSet[0]->ContentProtection as $rContentProtection) {
		if ($rContentProtection->attributes()->schemeIdUri == 'urn:uuid:edef8ba9-79d6-4ace-a3c8-27dcd51d21ed') {
			list($rPSSH) = explode('</cenc:pssh>', explode('<cenc:pssh>', $rContentProtection->asXML())[1]);
			$rObject = [
				'pssh'            => $rPSSH,
				'audio'           => NULL,
				'video'           => NULL,
				'segments'        => [],
				'current_segment' => $rCurrentSegment,
				'restart'         => false
			];

			if ($rCurrentSegment < $rLastSegment) {
				$rObject['restart'] = true;
			}
			else {
				foreach ($rPeriod->Period->AdaptationSet as $rAdaptationSet) {
					$rRepID = $rAdaptationSet->Representation[0]->attributes()['id'];
					$rSegmentTemplate = str_replace('$RepresentationID$', $rRepID, $rAdaptationSet->SegmentTemplate[0]->attributes()['media']);
					$rInitSegment = str_replace('$RepresentationID$', $rRepID, $rBaseURL . $rAdaptationSet->SegmentTemplate[0]->attributes()['initialization']);
					if (($rAdaptationSet->attributes()['contentType'] == 'audio') && ($rAdaptationSet->attributes()['codecs'] == 'mp4a.40.2')) {
						$rObject['audio'] = $rInitSegment;

						foreach (range($rLastSegment, $rCurrentSegment) as $x) {
							$rSegmentID = dechex(intval($x));
							$rObject['segments'][$rSegmentID]['audio'] = str_replace('$Number%08x$', $rSegmentID, $rBaseURL . $rSegmentTemplate);
						}
					}

					if ($rAdaptationSet->attributes()['contentType'] == 'video') {
						$rObject['video'] = $rInitSegment;

						foreach (range($rLastSegment, $rCurrentSegment) as $x) {
							$rSegmentID = dechex(intval($x));
							$rObject['segments'][$rSegmentID]['video'] = str_replace('$Number%08x$', $rSegmentID, $rBaseURL . $rSegmentTemplate);
						}
					}
				}
			}

			return $rObject;
		}
	}
}

define('MAIN_DIR', '/home/mini_cs/');
require MAIN_DIR . 'config/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(5);
$rMaxSegments = 64;
$rCacheTime = 21604;
$rDSTVLimit = 7;
$rVideoDir = MAIN_DIR . 'video';
$rHLSDir = MAIN_DIR . 'hls';
$rMP4Decrypt = MAIN_DIR . 'bin/mp4decrypt';
$rFFMpeg = MAIN_DIR . 'bin/ffmpeg';
$rFFProbe = MAIN_DIR . 'bin/ffprobe';
$rAria = '/usr/bin/aria2c';

if (!file_exists(MAIN_DIR . 'video/audio.mp4')) {
	exec('wget -q http://wvslingtv-drm.ddns.net:777/clue/audio.mp4 -O /home/mini_cs/video/audio.mp4 ');
}

?>