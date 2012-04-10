<?php

//функция создания кэш-файла (с удалением уже имеющегосяs)
function OBB_Cache_PutCacheFile ($FilePath, $FileContent) {
	$FileHandler = fopen ($FilePath, 'w+');
	flock ($FileHandler, LOCK_EX);
	ftruncate ($FileHandler, 0);
	fputs  ($FileHandler, $FileContent);
	fflush ($FileHandler);
	flock  ($FileHandler, LOCK_UN);
	fclose ($FileHandler);
	return (TRUE);
}

//функция взятия контента кэш-файла
function OBB_Cache_GetCacheFile ($FilePath) {
	$CacheContent = @file_get_contents ($FilePath);
	return ($CacheContent);
}

//функция удаления кэш-файла
function OBB_Cache_DeleteCacheFile ($FilePath) {
	if (file_exists ($FilePath)) {
		unlink ($FilePath);
	}
	return;
}

//функция обновления кэш-массива
function OBB_Cache_PutCacheArray ($FilePath, $AddingValuesArray) {    
	$AddingKey = $AddingValuesArray[0];
	$AddingVal = $AddingValuesArray[1];    
	$CacheArray = OBB_Cache_GetCacheArray ($FilePath);
	$CacheArray[$AddingKey] = $AddingVal;
	$CacheString = serialize ($CacheArray);    
	OBB_Cache_PutCacheFile ($FilePath, $CacheString);    
	return (TRUE);
}

//функция получения кэш-массива
function OBB_Cache_GetCacheArray ($FilePath) {
	$CacheString = OBB_Cache_GetCacheFile ($FilePath);
	$CacheArray = unserialize ($CacheString);
	return ($CacheArray);
}

?>