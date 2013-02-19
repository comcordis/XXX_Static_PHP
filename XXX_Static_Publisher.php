<?php

// TODO add  timestamp of publishing in directory path, write a temp file to include each time with that timestamp... NO old references will not work properly with a new deploy iteration

abstract class XXX_Static_Publisher
{
	const CLASS_NAME = 'XXX_Static_Publisher';
	
	public static $publishProfiles = array
	(
		'default' => array
		(
			'files' => array
			(					
				'compress' => array
				(
					'js' => true,
					'css' => true
				),
				
				'allowExtensions' => array
				(
					'js',
					'html',
					'htm',
					'css',
					'htc',
					'jpg',
					'xml',
					'gif',
					'png',
					'ico',
					'swf'
				),
				
				'denyExtensions' => array
				(
					'psd',
					'fla',
					'ini',
					'as',
					'php',
					'db',
					'sql',
					'tmp',
					'cer',
					'pub'
				)
			),
			
			'directories' => array
			(
				'allowNames' => array
				(
				),
				
				'denyNames' => array
				(
					'.git',
					'httpServerJail',
					'cache',
					'controllers',
					'models',
					'templates',
					'serverSide',
					'commandLine'
				)
			)
		)
	);
	
	public static $selectedPublishProfile = false;
	
	public static $publishGroups = array(); 
	
	public static function addPublishProfile ($name = '', $publishProfile = array())
	{
		self::$publishProfiles[$name] = $publishProfile;
	}
	
	public static function enablePublishProfile ($profile = 'default')
	{
		self::$selectedPublishProfile = $profile;
	}
	
	public static function disablePublishProfile ()
	{
		self::$selectedPublishProfile = false;
	}
	
	public static function doesFilePassPublishProfile ($path = '')
	{
		$result = true;
		
		if (self::$selectedPublishProfile)
		{			
			$extension = XXX_FileSystem_Local::getFileExtension($path);
			
			$filesPublishProfile = self::$publishProfiles[self::$selectedPublishProfile]['files'];
			
			if (XXX_Array::getFirstLevelItemTotal($filesPublishProfile['allowExtensions']))
			{
				if (!XXX_Array::hasValue($filesPublishProfile['allowExtensions'], $extension))
				{
					$result = false;
				}
			}
			else if (XXX_Array::getFirstLevelItemTotal($filesPublishProfile['denyExtensions']))
			{
				if (XXX_Array::hasValue($filesPublishProfile['denyExtensions'], $extension))
				{
					$result = false;
				}
			}
		}
		
		return $result;
	}
	
	
	public static function doesDirectoryPassPublishProfile ($path = '')
	{
		$result = true;
		
		if (self::$selectedPublishProfile)
		{
			$name = XXX_Path_Local::getIdentifier($path);
			
			$directoriesPublishProfile = self::$publishProfiles[self::$selectedPublishProfile]['directories'];
			
			if (XXX_Array::getFirstLevelItemTotal($directoriesPublishProfile['allowNames']) > 0)
			{
				if (!XXX_Array::hasValue($directoriesPublishProfile['allowNames'], $name))
				{
					$result = false;
				}
			}
			else if (XXX_Array::getFirstLevelItemTotal($directoriesPublishProfile['denyNames']) > 0)
			{
				if (XXX_Array::hasValue($directoriesPublishProfile['denyNames'], $name))
				{
					$result = false;
				}
			}
		}
		
		return $result;
	}
		
	public static function publishFile ($sourceFilePath = '', $destinationFilePath = '', $deleteSourceFile = false)
	{
		$result = false;
		
		if (XXX_FileSystem_Local::doesFileExist($destinationFilePath))
		{
			XXX_FileSystem_Local::deleteFile($destinationFilePath);
		}
		
		if (XXX_FileSystem_Local::doesFileExist($sourceFilePath))
		{
			$path = $sourceFilePath;
			$newPath = $destinationFilePath;
			
			if (self::doesFilePassPublishProfile($path))
			{
				$newPathExists = XXX_FileSystem_Local::ensurePathExistenceByDestination($newPath);
				
				if ($newPathExists)
				{
					if (XXX_FileSystem_Local::doesFileExist($path))
					{
						$newPathExists = XXX_FileSystem_Local::doesFileExist($newPath);
						
						$clear = true;
						
						if ($newPathExists)
						{
							if (!XXX_FileSystem_Local::deleteFile($newPath))
							{
								$clear = false;
							}
						}
						
						if ($clear)
						{
							$processed = false;
							/*
							$extension = XXX_FileSystem_Local::getFileExtension($path);
							
							$filesPublishProfile = self::$publishProfiles[self::$selectedPublishProfile]['files'];			
							
							switch ($extension)
							{
								case 'js':
									if ($filesPublishProfile['compress']['js'])
									{
										$processed = XXX_JS_Compressor::compressFile($path, $newPath, true, false, false);
									}
									break;
								case 'css':
									if ($filesPublishProfile['compress']['css'])
									{
										$processed = XXX_CSS_Compressor::compressFile($path, $newPath, false);
									}
									break;
								// TODO smush/optimize images
							}
							*/
							if (!$processed)
							{
								$result = XXX_FileSystem_Local::copyFile($path, $newPath);
							}
						}
					}
				}
			 	
				if (XXX_FileSystem_Local::doesFileExist($destinationFilePath))
				{
					$result = true;
					
					$temporaryFile = XXX_String::beginsWith($sourceFilePath, XXX_OperatingSystem::$temporaryFilesPathPrefix); 
					
					if ($deleteSourceFile || $temporaryFile)
					{
						XXX_FileSystem_Local::deleteFile($sourceFilePath);
					}
				}
				else
				{
					trigger_error('Unable to write destination file: "' . $destinationFilePath . '".');
				}
			}
			else
			{
				$result = true;
			}
		}
		else
		{
			trigger_error('Source file: "' . $sourceFilePath . '" doesn\'t exist or isn\'t available.');
		}
		
		return $result;
	}
	
	public static function publishDirectory ($sourceDirectoryPath = '', $destinationDirectoryPath = '', $emptyDestinationDirectory = false, $deleteSourceDirectory = false)
	{
		$result = true;
		
		if (XXX_FileSystem_Local::doesDirectoryExist($sourceDirectoryPath))
		{
			if ($emptyDestinationDirectory)
			{			
				if (XXX_FileSystem_Local::doesDirectoryExist($destinationDirectoryPath))
				{
					XXX_FileSystem_Local::emptyDirectory($destinationDirectoryPath);
				}
			}
			
			$path = $sourceDirectoryPath;
			$newPath = $destinationDirectoryPath;
			$deleteSourceFile = $deleteSourceDirectory;
			
			if (self::doesDirectoryPassPublishProfile($path))
			{
				$result = false;
				
				if (XXX_FileSystem_Local::doesDirectoryExist($path))
				{
					$directoryContent = XXX_FileSystem_Local::getDirectoryContent($path, false);
					
					if (XXX_FileSystem_Local::ensurePathExistence($newPath))
					{
						if ($directoryContent !== false)
						{
							$result = true;
							
							if (XXX_Array::getFirstLevelItemTotal($directoryContent['directories']))
							{
								foreach ($directoryContent['directories'] as $directory)
								{									
									if (!self::publishDirectory($directory['path'], XXX_Path_Local::extendPath($newPath, $directory['directory']), $emptyDestinationDirectory, $deleteSourceDirectory))
									{
										$result = false;
									}
								}
							}
							
							if (XXX_Array::getFirstLevelItemTotal($directoryContent['files']))
							{
								foreach ($directoryContent['files'] as $file)
								{			
									if (!self::publishFile($file['path'], XXX_Path_Local::extendPath($newPath, $file['file']), $deleteSourceFile))
									{
										$result = false;
									}
								}
							}
						}
					}
				}
			}
			else
			{
				$result = true;
			}
			
			if ($deleteSourceDirectory)
			{
				XXX_FileSystem_Local::deleteDirectory($sourceDirectoryPath);
			}
		}
		else
		{
			trigger_error('Source directory: "' . $sourceDirectoryPath . '" doesn\'t exist or isn\'t available.');	
		}		
		
		if ($disablePublishProfile)
		{
			self::disablePublishProfile();
		}
		
		return $result;
	}
	
	public static function publishItem ($item = array())
	{
		$result = false;
		
		if ($item['composeFunction'] != '')
		{
			$result = $item['composeFunction']();
		}
		else
		{
			$result = true;
		}
		
		if ($result)
		{
			if ($item['publishProfile'])
			{				
				self::enablePublishProfile($item['publishProfile']);
			}
			else
			{
				self::disablePublishProfile();
			}
			
			if ($item['type'] == 'directory')
			{
				$tempResult = self::publishDirectory($item['sourcePath'], $item['destinationPath'], $item['emptyDestinationDirectory'], $item['deleteSourceDirectory']);
			}
			else if ($item['type'] == 'file')
			{
				$tempResult = self::publishFile($item['sourcePath'], $item['destinationPath'], $item['deleteSourceFile']);
			}
			
			self::disablePublishProfile();
			
			if ($tempResult)
			{	
				$result = true;
			}
		}
		
		return $result;
	}
	
	public static function publishGroup ($groupName = '')
	{
		$result = false;
		
		if (XXX_Array::hasKey(self::$publishGroups, $groupName))
		{
			$result = true;
			
			for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal(self::$publishGroups[$groupName]); $i < $iEnd; ++$i)
			{
				$item = self::$publishGroups[$groupName][$i];
				
				if ($result)
				{
					$tempResult = self::publishItem($item);
					
					if (!$tempResult)
					{	
						$result = false;
					}
				}
			}
		}
		
		return $result;
	}
	
	public static function publish ()
	{
		foreach (self::$publishGroups as $groupName => $items)
		{
			self::publishGroup($groupName);
		}
	}
	
	public static function addGroup ($groupName = '')
	{
		$result = false;
		
		if (!XXX_Array::hasKey(self::$publishGroups, $groupName))
		{
			self::$publishGroups[$groupName] = array();
		}
		
		return $result;
	}
	
	public static function addItem ($groupName = '', $item = array())
	{
		$result = false;
		
		self::addGroup($groupName);
		
		if (XXX_Array::hasKey(self::$publishGroups, $groupName))
		{
			if ($item['publishProfile'] == '')
			{
				$item['publishProfile'] = 'default';
			}
			
			if ($item['type'] == '')
			{
				if (XXX_FileSystem_Local::doesDirectoryExist($item['sourcePath']))
				{
					$item['type'] = 'directory';
				}
				else
				{
					$item['type'] = 'file';
				}
			}
			
			self::$publishGroups[$groupName][] = $item;
		}
		
		return $result;
	}
}

?>