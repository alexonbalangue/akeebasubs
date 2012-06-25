<?php

$component = 'com_akeebasubs';
$txproject = 'akeebasubs';
$root = dirname(__FILE__);

function fix_file($file)
{
	echo basename($file)."\n";
	$fp = fopen($file, 'rt');
	if($fp == false) die('Could not open file.');
	$out = '';
	while(!feof($fp)) {
		$line = fgets($fp);
		$trimmed = trim($line);

		// Transform comments
		if(substr($trimmed,0,1) == '#') {
			$out .= ';'.substr($trimmed,1)."\n";
			continue;
		}

		if(substr($trimmed,0,1) == ';') {
			$out .= "$trimmed\n";
			continue;
		}

		// Detect blank lines
		if(empty($trimmed)) {
			$out .= "\n";
			continue;
		}

		// Process key-value pairs
		list($key, $value) = explode('=', $trimmed, 2);
		$value = trim($value, '"');
		$value = str_replace('\\"', "'", $value);
		$value = str_replace('"_QQ_"', "'", $value);
		$value = str_replace('"', "'", $value);
		$key = strtoupper($key);
		$key = trim($key);
		$out .= "$key=\"$value\"\n";
	}
	fclose($fp);

	file_put_contents($file, $out);
}

function scan_leaf($slugArray, $rootDir)
{
	foreach(new DirectoryIterator($rootDir) as $oLang)
	{
		if(!$oLang->isDir()) continue;
		if($oLang->isDot()) continue;
		$lang = $oLang->getFilename();
		
		$files = glob($rootDir.'/'.$lang.'/*.ini');
		foreach($files as $f) {
			fix_file($f);
		}
	}
}

function real_scan_leaf($slugArray, $rootDir) {
	global $root, $txproject;
	
	$files = glob($rootDir.'/en-GB/*.ini');
	$slug = implode($slugArray, '_');
	foreach($files as $f) {
		
		if(substr($f, -8) == '.sys.ini') {
			$slug .= '_sys';
		} elseif(substr($f, -9) == '.menu.ini') {
			$slug .= '_menu';
		} else {
			$slug .= '_main';
		}
		
		$file_proto = basename($f);
		$file_proto = substr($file_proto, 5);
		$file_proto = $rootDir.'/<lang>/<lang>'.$file_proto;
		$file_proto = substr($file_proto, strlen($root)+1);
		
		echo $rootDir."\n";
		$cmd = "tx set --auto-local -r $txproject.$slug '$file_proto' --source-lang en-GB";
		$cmd .= ' --execute';
		
		passthru($cmd);
	}
}

$myRoot = $root.'/translations';
foreach(new DirectoryIterator($myRoot) as $oArea)
{
	if(!$oArea->isDir()) continue;
	if($oArea->isDot()) continue;
	$area = $oArea->getFilename();
	
	$areaDir = $myRoot.'/'.$area;
	$slug = array();
	switch($area) {
		case 'component':
			$slug[] = $component;
			break;
		
		case 'modules':
			$slug[] = 'mod';
			break;
		
		case 'plugins':
			$slug[] = 'plg';
			break;
		
		default:
			break;
	}
	
	if(empty($slug)) continue;
	
	foreach(new DirectoryIterator($areaDir) as $oFolder)
	{
		if(!$oFolder->isDir()) continue;
		if($oFolder->isDot()) continue;
		$folder = $oFolder->getFilename();
		
		$slug[] = $folder;
		$folderDir = $areaDir.'/'.$folder;
		
		if(is_dir($folderDir.'/en-GB')) {
			// A component
			scan_leaf($slug, $folderDir);
		} else {
			// A module or plugin
			foreach(new DirectoryIterator($folderDir) as $oExtension)
			{
				if(!$oExtension->isDir()) continue;
				if($oExtension->isDot()) continue;
				$extension = $oExtension->getFilename();
				
				$slug[] = $extension;
				$extensionDir = $folderDir.'/'.$extension;
				if(is_dir($extensionDir.'/en-GB')) {
					scan_leaf($slug, $extensionDir);
				}
				array_pop($slug);
			}
		}
		
		array_pop($slug);
	}
}