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
					'swf',
					'woff'
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
					trigger_error('Unable to write destination file: "' . $destinationFilePath . '".', E_USER_ERROR);
				}
			}
			else
			{
				$result = true;
			}
		}
		else
		{
			trigger_error('Source file: "' . $sourceFilePath . '" doesn\'t exist or isn\'t available.', E_USER_ERROR);
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
			trigger_error('Source directory: "' . $sourceDirectoryPath . '" doesn\'t exist or isn\'t available.', E_USER_ERROR);	
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
		
		return $result;
	}
	
	public static function publishI18n ($sourcePath = '', $destinationPath = '')
	{
		global $XXX_I18n_Translations, $XXX_I18n_Localizations;
		
		$i18nPaths = self::getI18nPaths($sourcePath, $destinationPath);
		
		// translations
		
			foreach ($i18nPaths['translations'] as $translation)
			{
				$before = $XXX_I18n_Translations[$translation['translation']];
				
				$XXX_I18n_Translations[$translation['translation']] = array();
				
				include $translation['sourcePath'];
				
				$tempTranslations = $XXX_I18n_Translations[$translation['translation']];
				
				$content = '
				XXX_DOM_Ready.addEventListener(function ()
				{
					if (!XXX_I18n_Translations.' . $translation['translation'] . ')
					{
						XXX_I18n_Translations.' . $translation['translation'] . ' = {};
					}
					
					XXX_I18n_Translations.' . $translation['translation'] . ' = XXX_Array.merge(XXX_I18n_Translations.' . $translation['translation'] . ', ' . XXX_String_JSON::encode($tempTranslations) . ');
				});';
				
				$XXX_I18n_Translations[$translation['translation']] = $before;
				
				XXX_FileSystem_Local::writeFileContent($translation['destinationPath'], $content);	
			}
		
		// localizations
		
			foreach ($i18nPaths['localizations'] as $localization)
			{
				$before = $XXX_I18n_Localizations[$localization['localization']];
				
				$XXX_I18n_Localizations[$localization['localization']] = array();
				
				include $localization['sourcePath'];
				
				$tempLocalizations = $XXX_I18n_Localizations[$localization['localization']];
				
				$content = '
				XXX_DOM_Ready.addEventListener(function ()
				{
					if (!XXX_I18n_Localizations.' . $localization['localization'] . ')
					{
						XXX_I18n_Localizations.' . $localization['localization'] . ' = {};
					}
					
					XXX_I18n_Localizations.' . $localization['localization'] . ' = XXX_Array.merge(XXX_I18n_Localizations.' . $localization['localization'] . ', ' . XXX_String_JSON::encode($tempLocalizations) . ');
				});';
				
				$XXX_I18n_Localizations[$localization['localization']] = $before;
				
				XXX_FileSystem_Local::writeFileContent($localization['destinationPath'], $content);	
			}
	}
	
	public static function getI18nPaths ($sourcePath = '', $destinationPath = '')
	{
		$result = array		
		(
			'translations' => array(),
			'localizations' => array()
		);
		
		$sourceI18nPath = XXX_Path_Local::extendPath($sourcePath, 'i18n');
		$destinationI18nPath = XXX_Path_Local::extendPath($destinationPath, 'i18n');
		
		if (XXX_FileSystem_Local::doesDirectoryExist($sourceI18nPath))
		{
			// translations
			
				$sourceTranslationsPath = XXX_Path_Local::extendPath($sourceI18nPath, 'translations');
				$destinationTranslationsPath = XXX_Path_Local::extendPath($destinationI18nPath, 'translations');
				
				if (XXX_FileSystem_Local::doesDirectoryExist($sourceTranslationsPath))
				{
					$directoryContent = XXX_FileSystem_Local::getDirectoryContent($sourceTranslationsPath, false);
				
					if ($directoryContent !== false)
					{
						if (XXX_Array::getFirstLevelItemTotal($directoryContent['directories']))
						{
							foreach ($directoryContent['directories'] as $directory)
							{
								$translation = $directory['directory'];
																
								$sourceTranslationPath = XXX_Path_Local::extendPath($sourceTranslationsPath, array($translation, 'translations.' . $translation . '.php'));
								$destinationTranslationPath = XXX_Path_Local::extendPath($destinationTranslationsPath, array($translation, 'translations.' . $translation . '.js'));
								
								if (XXX_FileSystem_Local::doesFileExist($sourceTranslationPath))
								{
									$result['translations'][] = array
									(
										'translation' => $translation,
										'sourcePath' => $sourceTranslationPath,
										'destinationPath' => $destinationTranslationPath
									);
								}
							}
						}
					}
				}
			
			// localizations
				
				$sourceLocalizationsPath = XXX_Path_Local::extendPath($sourceI18nPath, 'localizations');
				$destinationLocalizationsPath = XXX_Path_Local::extendPath($destinationI18nPath, 'localizations');
				
				if (XXX_FileSystem_Local::doesDirectoryExist($sourceLocalizationsPath))
				{
					$directoryContent = XXX_FileSystem_Local::getDirectoryContent($sourceLocalizationsPath, false);
				
					if ($directoryContent !== false)
					{
						if (XXX_Array::getFirstLevelItemTotal($directoryContent['directories']))
						{
							foreach ($directoryContent['directories'] as $directory)
							{
								$localization = $directory['directory'];
								
								$sourceLocalizationPath = XXX_Path_Local::extendPath($sourceLocalizationsPath, array($localization, 'localizations.' . $localization . '.php'));
								$destinationLocalizationPath = XXX_Path_Local::extendPath($destinationLocalizationsPath, array($localization, 'localizations.' . $localization . '.js'));
								
								if (XXX_FileSystem_Local::doesFileExist($sourceLocalizationPath))
								{
									$result['localizations'][] = array
									(
										'localization' => $localization,
										'sourcePath' => $sourceLocalizationPath,
										'destinationPath' => $destinationLocalizationPath
									);
								}
							}
						}
					}
				}
		}
		
		$sourceModulesPath = XXX_Path_Local::extendPath($sourcePath, 'modules');
		$destinationModulesPath = XXX_Path_Local::extendPath($destinationPath, 'modules');
		
		if (XXX_FileSystem_Local::doesDirectoryExist($sourceModulesPath))
		{			
			$directoryContent = XXX_FileSystem_Local::getDirectoryContent($sourceModulesPath, false);
			
			if ($directoryContent !== false)
			{
				if (XXX_Array::getFirstLevelItemTotal($directoryContent['directories']))
				{
					foreach ($directoryContent['directories'] as $directory)
					{
						$tempResult = self::getI18nPaths(XXX_Path_Local::extendPath($sourceModulesPath, array($directory['directory'])), XXX_Path_Local::extendPath($destinationModulesPath, array($directory['directory'])));
						
						if ($tempResult)
						{
							foreach ($tempResult['translations'] as $translation)
							{
								$result['translations'][] = $translation;
							}
							
							foreach ($tempResult['localizations'] as $localization)
							{
								$result['localizations'][] = $localization;
							}
						}
					}
				}
			}
		}
				
		if (XXX_Array::getFirstLevelItemTotal($result['translations']) == 0 && XXX_Array::getFirstLevelItemTotal($result['localizations']) == 0)
		{
			$result = false;
		}		
		
		return $result;
	}
	
	public static function publishJSVariableToProject ($project = '', $key = '', $value = '', $domReady = true, $resultFile = '', $publishProfile = '')
	{
		$content = 'var ' . $key . ' = ' . XXX_String_JSON::encode($value) . ';' . XXX_OperatingSystem::$lineSeparator;
		
		if ($domReady)
		{
			$temp = '';
			$temp .= XXX_String::$lineSeparator;
			$temp .= 'XXX_DOM_Ready.addEventListener(function ()' . XXX_String::$lineSeparator;
			$temp .= '{' . XXX_String::$lineSeparator;
			$temp .= $content . XXX_String::$lineSeparator;
			$temp .= '});' . XXX_String::$lineSeparator;
			$temp .= XXX_String::$lineSeparator;
			
			$content = $temp;
		}
		
		$temporaryFilePath = XXX_Path_Local::extendPath(XXX_OperatingSystem::$temporaryFilesPathPrefix, array($project . '_' . $resultFile));
		
		XXX_FileSystem_Local::writeFileContent($temporaryFilePath, $content);
		
		$item = array
		(
			'sourcePath' => $temporaryFilePath,
			'destinationPath' => XXX_Path_Local::extendPath(XXX_Path_Local::$deploymentSourcePathPrefix, array('httpServerJail', 'static', $project, $resultFile)),
			'publishProfile' => $publishProfile
		);
		
		$result = self::publishItem($item);
		
		return $result;
	}
	
	public static function publishMergedFilesFromOtherProject ($project = '', $deployIdentifier = 'latest', $mergeFiles = array(), $resultFile = '', $publishProfile = '')
	{
		$content = XXX_FileSystem_Local::getMergedFilesContent(XXX_Path_Local::composeOtherProjectDeploymentSourcePathPrefix($project, $deployIdentifier), $mergeFiles, XXX_String::$lineSeparator);
		XXX_FileSystem_Local::writeFileContent(XXX_OperatingSystem::$temporaryFilesPathPrefix . $project . '_' . $resultFile, $content);
		
		$item = array
		(
			'sourcePath' => XXX_OperatingSystem::$temporaryFilesPathPrefix . $project . '_' . $resultFile,
			'destinationPath' => XXX_Path_Local::extendPath(XXX_Path_Local::$deploymentSourcePathPrefix, array('httpServerJail', 'static', $project, $resultFile)),
			'publishProfile' => $publishProfile
		);
		
		$result = self::publishItem($item);
		
		return $result;
	}
	
	public static function clear ()
	{
		XXX_FileSystem_Local::emptyDirectory(XXX_Path_Local::extendPath(XXX_Path_Local::$deploymentSourcePathPrefix, array('httpServerJail', 'static')));
	}
}

?>