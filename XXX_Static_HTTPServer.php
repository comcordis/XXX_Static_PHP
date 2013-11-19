<?php

abstract class XXX_Static_HTTPServer
{
	public static function singleFile ($pathParts = array())
	{
		XXX_HTTPServer_Client_Output::mimicStaticFileServing($pathParts);
	}
	
	public static function combinedFiles ($files = array(), $fileType = '')
	{
		$mimeType = 'application/octet-stream';
		switch ($fileType)
		{
			case 'js':
				$mimeType = 'text/javascript';
				break;
			case 'css':
				$mimeType = 'text/css';
				break;			
		}
		
		if (!XXX_Type::isArray($files))
		{
			$files = XXX_String::splitToArray($files, '|');
		}
		
		/*
		$files = XXX_HTTPServer_Client_Input::getURIVariable('files');
		$fileType = XXX_HTTPServer_Client_Input::getURIVariable('fileType');
		*/
		
		$destinationPath = XXX_Static_Publisher::$destinationPathPrefix;
		
		$newFiles = array();
		
		foreach ($files as $file)
		{
			$tempFile = XXX_Path_Local::extendPath($destinationPath, $file);
			
			$newFiles[] = $tempFile;
		}
		
		$files = $newFiles;
		
		
		
		$fileModifiedTimestamp = 0;
		
		foreach ($files as $file)
		{
			$tempFileModifiedTimestamp = XXX_FileSystem_Local::getFileModifiedTimestamp($file);
			
			$fileModifiedTimestamp = XXX_Number::highest($fileModifiedTimestamp, $tempFileModifiedTimestamp);
		}
		
		if ($fileModifiedTimestamp == 0)
		{
			$fileModifiedTimestamp = XXX_TimestampHelpers::getCurrentTimestamp();
		}
		
		if(XXX_HTTPServer_Client_Input::$onlyIfModifiedSinceTimestamp == $fileModifiedTimestamp && 1 == 2)
		{
			XXX_HTTPServer_Client_Output::sendNotModifiedHeader();
		}
		else
		{
			$fileContent = '';
			
			foreach ($files as $file)
			{
				$tempFileContent = XXX_FileSystem_Local::getFileContent($file);
				
				if ($tempFileContent)
				{
					$fileContent .= $tempFileContent;
					$fileContent .= "\r\n";
				}
			}
			
			if ($fileContent == '')
			{
				XXX_HTTPServer_Client_Output::sendHeader('HTTP/1.0 404 Not Found');
			}
			else
			{
				$byteSize = XXX_String::getByteSize($fileContent);
				
				XXX_HTTPServer_Client_Output::prepareForFileServingOrDownload();
				
				XXX_HTTPServer_Client_Output::sendHeader('Last-Modified: '. gmdate('D, d M Y H:i:s', $fileModifiedTimestamp) . ' GMT');
				XXX_HTTPServer_Client_Output::sendHeader('Content-Type: ' . $mimeType);
				XXX_HTTPServer_Client_Output::sendHeader('Content-Length: ' . $byteSize);
				
				if (class_exists('XXX_HTTP_Cookie_Session'))
				{
					XXX::dispatchEventToListeners('beforeSaveSession');
					XXX_HTTP_Cookie_Session::save();
				}
				
				XXX_HTTPServer_Client_Output::sendHeader('Connection: close');
		
				echo $fileContent;
			}
		}
	}
}

?>