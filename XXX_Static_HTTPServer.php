<?php

abstract class XXX_Static_HTTPServer
{
	public static function processFile ($file = '')
	{
		$file = XXX_MPC_Router::cleanRoute($file);
			
		$file = XXX_Static_Publisher::mapFile($file);
			
		$file = XXX_Path_Local::extendPath(XXX_Static_Publisher::$destinationPathPrefix, $file);
		
		return $file;
	}
	
	public static function serveFile ($file = '')
	{
		$file = self::processFile($file);
		
		XXX_HTTPServer_Client_Output::serveFile($file);
	}
	
	public static function serveFiles ($files = array())
	{
		if (!XXX_Type::isArray($files))
		{
			$files = XXX_String::splitToArray($files, '|');
		}
		
		$newFiles = array();
		
		foreach ($files as $file)
		{
			$newFiles[] = self::processFile($file);
		}
		
		$files = $newFiles;
		
		XXX_HTTPServer_Client_Output::serveFiles($files);
	}
}

?>