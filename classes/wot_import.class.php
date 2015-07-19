<?php

/**
 * Handles the import of the data from the files into the database.
 *
 * @version 1.0
 * @author stephen
 */
final class wot_import
{
	public function __construct($folderLocation, $versionNumber)
	{

	}

	/*
	 * Enumerate the directories 
	 * 
	 * 
	 * @$path String Base file path location
	 * @target String 
	*/
	function decodeDirectory($path, $target, $base = null)
	{
		if (is_null($base)) {
			$base = $path;
		}

		$handle = opendir($path);
		
		while(($file = readdir($handle)) !== false) {
			
			if ($file == '.' || $file == '..')
			{
				continue;
			}

			$file = $path . '/' . $file;
			
			if (!file_exists($file)) 
			{
				Debug::log("Problem: {$path} {$target} {$base} {$file}", LEVEL_ERRORS);
			}
			
			if (is_dir($file)) 
			{
				decodeDirectory($file, $target, $base);
			} 
			else 
			{
				$path_target = $target . substr($path, strlen($base)) . '/';
				if (!file_exists($path_target)) 
				{
					mkdir($path_target, 777, true);
				}
				
				$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
				$name = pathinfo($file, PATHINFO_BASENAME);
				if ($ext == 'xml') 
				{
					WotXML::decodePackedFile($file, $name, $path_target . $name);
				}
			}
		}
	}
}

