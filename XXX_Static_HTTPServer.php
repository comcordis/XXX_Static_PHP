<?php

abstract class XXX_Static_HTTPServer
{
	public static function singleFile ($file)
	{
		$file = XXX_MPC_Router::cleanRoute($file);
		
		XXX_HTTPServer_Client_Output::mimicStaticFileServing($file);
	}
	
	public static function combinedFiles ($files = array(), $fileType = '', $compress = false)
	{
		if (XXX_Type::isEmptyArray($files))
		{
			$files = XXX_HTTPServer_Client_Input::getURIVariable('files');
		}
		
		if ($fileType == '')
		{
			$fileType = XXX_HTTPServer_Client_Input::getURIVariable('fileType');
		}
		
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
		
		$destinationPath = XXX_Static_Publisher::$destinationPathPrefix;
		
		$newFiles = array();
		
		foreach ($files as $file)
		{
			$file = XXX_MPC_Router::cleanRoute($file);
			
			$file = XXX_Static_Publisher::mapFile($file);
			
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
		
		if(XXX_HTTPServer_Client_Input::$onlyIfModifiedSinceTimestamp == $fileModifiedTimestamp)
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
			
			if ($compress)
			{
				if ($fileType == 'css')
				{
					$fileContent = YUI_Compressor::compressCSSString($fileContent);
				}
				else if ($fileType == 'js')
				{
					$fileContent = YUI_Compressor::compressJSString($fileContent);
				}
			}
			
			if ($fileContent == '')
			{
				XXX_HTTPServer_Client_Output::sendHeader('HTTP/1.0 404 Not Found');
			}
			else
			{
				$byteSize = XXX_String::getByteSize($fileContent);
				
				XXX_HTTPServer_Client_Output::prepareForFileServingOrDownload(true);
				
				XXX_HTTPServer_Client_Output::sendHeader('Last-Modified: '. gmdate('D, d M Y H:i:s', $fileModifiedTimestamp) . ' GMT');
				XXX_HTTPServer_Client_Output::sendHeader('Expires: '. gmdate('D, d M Y H:i:s', time() + (86400 * 365)) . ' GMT');
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