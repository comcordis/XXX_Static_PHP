<?php

// TODO css replace url() with checksum stuff

abstract class XXX_Static_Publisher
{
	const CLASS_NAME = 'XXX_Static_Publisher';
	
	public static $destinationPathPrefix = '';
	
	public static $cacheMapping = array();
	
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
	
	public static function initialize ()
	{
		if (XXX::$deploymentInformation['localDevelopmentBox'])
		{
			self::$destinationPathPrefix = XXX_Path_Local::extendPath(XXX_Path_Local::$dataProjectsPathPrefix, array('Comcordis_Static', XXX::$deploymentInformation['project']));
		}
		else
		{
			self::$destinationPathPrefix = XXX_Path_Local::extendPath(XXX_Path_Local::$dataProjectsPathPrefix, array('Comcordis_Static', XXX::$deploymentInformation['deployEnvironment'], XXX::$deploymentInformation['project']));
		}
				
		$staticCacheMappingPath = XXX_Path_Local::extendPath(self::$destinationPathPrefix, 'static.cacheMapping.php');
		
		if (is_file($staticCacheMappingPath))
		{
			include_once $staticCacheMappingPath;
			
			global $XXX_Static_cacheMapping;
			
			self::$cacheMapping = $XXX_Static_cacheMapping;
		}
	}
	
	public static function mapFile ($originalFilePath = '')
	{
		$result = $originalFilePath;
		
		$filePathWithChecksum = self::$cacheMapping[$originalFilePath];
		
		if ($filePathWithChecksum)
		{
			$result = $filePathWithChecksum;
		}
		
		return $result;
	}
	
	public static function prefixAndMapFile ($originalFilePath = '')
	{
		$result = '';
		
		$result .= XXX_URI::$currentHTTPServerProtocolPrefix . XXX_URI::$staticURIPathPrefix;
		
		$result .= self::mapFile($originalFilePath);
		
		return $result;
	}
		
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
							
							1. Strip destinationPathPrefix
							
							2. Normalize path, strip directory separator from begin
							
							3. Replace directory separator with URI
														
							*/						
							
							$pathToIdentifier = XXX_Path_Local::getParentPath($newPath);
							
							$fileName = XXX_FileSystem_Local::getFileName($newPath);
							$extension = XXX_FileSystem_Local::getFileExtension($newPath);
							
							$checksum = XXX_String::getPart(XXX_FileSystem_Local::getFileChecksum($path, 'md5'), 0, 12);
							
							$newPathWithChecksum = XXX_Path_Local::extendPath($pathToIdentifier, $fileName . '.' . $checksum . '.' . $extension);
							
							
							$destinationPathPrefixCharacterLength = XXX_String::getCharacterLength(self::$destinationPathPrefix . XXX_OperatingSystem::$directorySeparator);
							
							$shortenedNewPath = XXX_String::getPart($newPath, $destinationPathPrefixCharacterLength);
							$shortenedNewPathWithChecksum = XXX_String::getPart($newPathWithChecksum, $destinationPathPrefixCharacterLength);
							
							$shortenedNewPath = XXX_String::replace($shortenedNewPath, XXX_OperatingSystem::$directorySeparator, '/');
							$shortenedNewPathWithChecksum = XXX_String::replace($shortenedNewPathWithChecksum, XXX_OperatingSystem::$directorySeparator, '/');
							
							/*					
							echo $path . '<br>';
							echo '- ' . $newPath . '<br>';
							echo '- ' . $newPathWithChecksum . '<br>';
							echo '- ' . $shortenedNewPath . '<br>';
							echo '- ' . $shortenedNewPathWithChecksum . '<br>';
							
							echo '<hr>';
							*/
							
							self::$cacheMapping[$shortenedNewPath] = $shortenedNewPathWithChecksum;
							
							$newPathWithChecksumAlreadyExists = XXX_FileSystem_Local::doesFileExist($newPathWithChecksum);
							
							if ($newPathWithChecksumAlreadyExists)
							{
								$processed = true;
							}
							else
							{
								if (class_exists('YUI_Compressor'))
								{
									$extension = XXX_FileSystem_Local::getFileExtension($path);
								
									$filesPublishProfile = self::$publishProfiles[self::$selectedPublishProfile]['files'];			
									
									switch ($extension)
									{
										case 'js':
											if ($filesPublishProfile['compress']['js'])
											{
												$processed = YUI_Compressor::compressJSFile($path, $newPathWithChecksum);
											}
											break;
										case 'css':
											if ($filesPublishProfile['compress']['css'])
											{
												$processed = YUI_Compressor::compressCSSFile($path, $newPathWithChecksum);
											}
											break;
										// TODO smush/optimize images
									}
								}
							}
							
							if (!$processed)
							{
								$result = XXX_FileSystem_Local::copyFile($path, $newPathWithChecksum);
							}
							
							if ($newPathWithChecksumAlreadyExists)
							{
								if (!XXX_FileSystem_Local::doesFileExist($newPath))
								{
									$result = XXX_FileSystem_Local::copyFile($path, $newPath);
								}
							}
							else
							{
								$result = XXX_FileSystem_Local::copyFile($path, $newPath);
							}
						}
					}
				}
			 	
				if (XXX_FileSystem_Local::doesFileExist($destinationFilePath) || $processed)
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
	
	public static function getCurrentI18nURIs ($project = '', $type = 'translations')
	{
		$result = array();
		
		foreach (XXX_Path_Local::$loadedIncludeFiles[$type] as $path)
		{
			$relativePath = false;
			$type = false;
			
			if (XXX_String::beginsWith($path, XXX_Path_Local::$deploymentSourcePathPrefix))
			{
				$relativePath = XXX_String::getPart($path, XXX_String::getCharacterLength(XXX_Path_Local::$deploymentSourcePathPrefix));
				
				$type = 'deploymentSourcePathPrefix';
			}
			else if (XXX_String::beginsWith($path, XXX_Path_Local::$deploymentDataPathPrefix))
			{
				$relativePath = XXX_String::getPart($path, XXX_String::getCharacterLength(XXX_Path_Local::$deploymentDataPathPrefix));
				
				$type = 'deploymentDataPathPrefix';
			}
			else if (XXX_String::beginsWith($path, XXX_Path_Local::$sourcesProjectsPathPrefix))
			{
				$relativePath = XXX_String::getPart($path, XXX_String::getCharacterLength(XXX_Path_Local::$sourcesProjectsPathPrefix));
				
				$type = 'sourcePathPrefix';
			}
			
			$relativeURI = XXX_String::replace($relativePath, array('\\', '.php'), array('/', '.js'));
			
				$relativeURI = XXX_String::replace($relativeURI, array('latest/'), array(''));
				$relativeURI = XXX_String::replace($relativeURI, array('production/'), array(''));
				$relativeURI = XXX_String::replace($relativeURI, array('staging/'), array(''));
				$relativeURI = XXX_String::replace($relativeURI, array('acceptance/'), array(''));
				$relativeURI = XXX_String::replace($relativeURI, array('integration/'), array(''));
				$relativeURI = XXX_String::replace($relativeURI, array('development/'), array(''));
			
			switch ($type)
			{
				case 'deploymentSourcePathPrefix':
					$relativeURI = $project . '/' . $relativeURI;			
					break;
				case 'deploymentDataPathPrefix':					
					$relativeURI = $project . '/data/' . $relativeURI;		
					break;
				case 'sourcePathPrefix':	
					break;
			}
			
			$uri = XXX_Static_Publisher::prefixAndMapFile($relativeURI);
			
			$result[] = array
			(
				'path' => $path,
				'type' => $type,
				'relativePath' => $relativePath,
				'relativeURI' => $relativeURI,
				'uri' => $uri
			);
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
			'destinationPath' => XXX_Path_Local::extendPath(self::$destinationPathPrefix, array($project, $resultFile)),
			'publishProfile' => $publishProfile
		);
		
		$result = self::publishItem($item);
		
		return $result;
	}
	
	public static function publishMergedFilesFromOtherProject ($project = '', $deployIdentifier = false, $mergeFiles = array(), $resultFile = '', $publishProfile = '')
	{
		$deployIdentifier = XXX_Path_Local::normalizeOtherProjectDeploymentDeployIdentifier($project, $deployIdentifier);
		
		$content = XXX_FileSystem_Local::getMergedFilesContent(XXX_Path_Local::composeOtherProjectDeploymentSourcePathPrefix($project, $deployIdentifier), $mergeFiles, XXX_String::$lineSeparator);
		
		XXX_FileSystem_Local::writeFileContent(XXX_OperatingSystem::$temporaryFilesPathPrefix . $project . '_' . $resultFile, $content);
		
		$item = array
		(
			'sourcePath' => XXX_OperatingSystem::$temporaryFilesPathPrefix . $project . '_' . $resultFile,
			'destinationPath' => XXX_Path_Local::extendPath(self::$destinationPathPrefix, array($project, $resultFile)),
			'publishProfile' => $publishProfile
		);
		
		$result = self::publishItem($item);
		
		return $result;
	}
	
	public static function publishAlreadyPublishedMergedFiles ($alreadyPublishedMergeFiles = array(), $resultFile = '', $publishProfile = '')
	{
		$newFiles = array();
		
		foreach ($alreadyPublishedMergeFiles as $file)
		{
			$file = XXX_MPC_Router::cleanRoute($file);
			
			$file = XXX_Static_Publisher::mapFile($file);
			
			$newFiles[] = $file;
		}
		
		$alreadyPublishedMergeFiles = $newFiles;
		
		$content = XXX_FileSystem_Local::getMergedFilesContent(self::$destinationPathPrefix, $alreadyPublishedMergeFiles, XXX_String::$lineSeparator);
		
		XXX_FileSystem_Local::writeFileContent(XXX_OperatingSystem::$temporaryFilesPathPrefix . XXX::$deploymentInformation['project'] . '_' . $resultFile, $content);
		
		$item = array
		(
			'sourcePath' => XXX_OperatingSystem::$temporaryFilesPathPrefix . XXX::$deploymentInformation['project'] . '_' . $resultFile,
			'destinationPath' => XXX_Path_Local::extendPath(self::$destinationPathPrefix, array(XXX::$deploymentInformation['project'], $resultFile)),
			'publishProfile' => $publishProfile
		);
		
		$result = self::publishItem($item);
		
		return $result;
	}
	
	public static function publishOtherProject ($project = '', $deployIdentifier = false, $publishProfile = '')
	{
		$deployIdentifier = XXX_Path_Local::normalizeOtherProjectDeploymentDeployIdentifier($project, $deployIdentifier);
		
		XXX_Path_Local::includeOtherProjectDeploymentSourceFile($project, 'publish.static.php', $deployIdentifier);
	}
	
	public static function clear ()
	{
		XXX_FileSystem_Local::emptyDirectory(self::$destinationPathPrefix);
	}
	
	public static function saveCacheMapping ()
	{
		$originalArrayLayoutMethod = XXX_Type::$arrayLayoutMethod;
		$originalComments = XXX_Type::$comments;
		
		XXX_Type::$arrayLayoutMethod = 'lean';
		XXX_Type::$comments = false;
		
		$variableName = '$XXX_Static_cacheMapping';
			
		XXX_Client_Output::startBuffer();
		
			echo '<?php' . XXX_OperatingSystem::$lineSeparator;
			
			echo XXX_OperatingSystem::$lineSeparator;
			
			echo 'global $XXX_Static_cacheMapping;';
			
			echo XXX_OperatingSystem::$lineSeparator;
			
			echo XXX_OperatingSystem::$lineSeparator;
							
			XXX_Type::peakAtVariableSub(self::$cacheMapping, 0, $variableName);
			
			echo XXX_OperatingSystem::$lineSeparator . '?>';
		
		$content = XXX_Client_Output::getBufferContent();
		
		XXX_FileSystem_Local::writeFileContent(XXX_Path_Local::extendPath(self::$destinationPathPrefix, 'static.cacheMapping.php'), $content);
		
		XXX_Type::$arrayLayoutMethod = $originalArrayLayoutMethod;
		XXX_Type::$comments = $originalComments;
		
	}
	
	public static function correctOwnerAndPermissions ()
	{
		XXX_FileSystem_Local::setDirectoryOwnerAdvanced(self::$destinationPathPrefix, 'apache', 'apache', true, true);
		
		XXX_FileSystem_Local::setDirectoryPermissions(self::$destinationPathPrefix, '770', true);			
		XXX_FileSystem_Local::setFilePermissionsInDirectory(self::$destinationPathPrefix, '660', true);
	}
}

?>