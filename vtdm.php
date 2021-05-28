<?php

/*
 * Файл для удаления модулей.
 * Файл необходимо поместить в корневую папку vTigecrm
 * Запустить через браузер.
 * В адресной строки добавить vtigercrm/vtdm.php?modulo=ИМЯ_МОДУЛЯ
 * Название модуля должно соответствовать названию в URL при открытии страниц модуля
 */


include_once 'includes/Loader.php';
include_once 'include/utils/utils.php';
include_once('vtlib/Vtiger/Module.php');
vimport('includes.http.Request');
vimport('includes.runtime.Globals');
vimport('includes.runtime.BaseModel');
vimport ('includes.runtime.Controller');
vimport('includes.runtime.LanguageHandler');


$moduloselezionato = $_GET['modulo'];
$Vtiger_Utils_Log = true;

if(!$moduloselezionato) {
	echo "Ошибка, нужно внести имя модуля как в следующем примере www.site.com/vtdm.php?modulo=ИМЯ_МОДУЛЯ";
	die();
}

$moduleInstance = Vtiger_Module::getInstance($moduloselezionato);
$moduleName = $moduleInstance->name;

if($moduleInstance) {

	echo "Модуль " . $moduleName . " найден". "<br>";
	
	echo "<br>";
	echo "Началось удаление таблиц модуля из базы данных". "<br>";
	$query = "DELETE FROM vtiger_settings_field WHERE name = ?";
	$result = $adb->pquery($query,array($moduleName));
	echo "Удалены настройки модуля из таблицы vtiger_settings_field <br>";

	$moduleNameLowercase =	strtolower($moduleName);
	$sql = "show tables like ?;";
	$result = $adb->pquery($sql, array('%' . $moduleNameLowercase .'%'));
	while ($row = $adb->fetch_array($result)) {
		$module_tables[] = $row[0];
	}

	foreach ($module_tables as $table) {
		$sql2 = "drop table " . $table .  ";";
		$result2 = $adb->pquery($sql2);
		echo "Удалена таблица - " . $table . "<br>";
	}


	echo "<br>";
	echo "Началось удаление папок". "<br>";
	$dirModules='modules/' . $moduleName;
	if (is_dir($dirModules)) {
		delDir($dirModules);
		echo "Удалена папка - " . $dirModules . "<br>";
	}

	$dirv7Layouts='layouts/v7/modules/' . $moduleName;
	if (is_dir($dirv7Layouts)) {
		delDir($dirv7Layouts);
		echo "Удалена папка - " . $dirv7Layouts . "<br>";
	}

	$dirv7LayoutsSettings='layouts/v7/modules/Settings/' . $moduleName;
	if (is_dir($dirv7LayoutsSettings)) {
		delDir($dirv7LayoutsSettings);
		echo "Удалена папка - " . $dirv7LayoutsSettings . "<br>";
	}

	$dirvlayoutLayouts='layouts/vlayout/modules/' . $moduleName;
	if (is_dir($dirvlayoutLayouts)) {
		delDir($dirvlayoutLayouts);
		echo "Удалена папка - " . $dirvlayoutLayouts . "<br>";
	}

	$dirModulesSettings='modules/Settings/' . $moduleName;
	if (is_dir($dirModulesSettings)) {
		delDir($dirModulesSettings);
		echo "Удалена папка - " . $dirModulesSettings . "<br>";
	}

	$dirLayoutsSettings='layouts/v7/modules/Settings/' . $moduleName;
	if (is_dir($dirLayoutsSettings)) {
		delDir($dirLayoutsSettings);
		echo "Удалена папка - " . $dirLayoutsSettings . "<br>";
	}

	if (is_dir($dirLayoutsSettings)) {
		delDir($dirLayoutsSettings);
		echo "Удалена папка - " . $dirLayoutsSettings . "<br>";
	}

	echo "<br>";
	echo "Началось удаление языковых файлов". "<br>";
	$ru_ruLanguageFile = 'languages/ru_ru/' . $moduleName . '.php';
	if (unlink($ru_ruLanguageFile)) {  
	    echo "Удален файл - languages/ru_ru/" . $moduleName . '.php'. "<br>";  
	}

	$en_usLanguageFile = 'languages/en_us/' . $moduleName . '.php';
	if (unlink($en_usLanguageFile)) {  
	    echo "Удален файл - languages/en_us/" . $moduleName . '.php'. "<br>";  
	}  

	$ru_ruLanguageSettingsFile = 'languages/ru_ru/Settings/' . $moduleName . '.php';
	if (unlink($ru_ruLanguageSettingsFile)) {  
	    echo "Удален файл - languages/ru_ru/Settings/" . $moduleName . '.php'. "<br>";  
	} 

	$en_usLanguageSettingsFile = 'languages/en_us/Settings/' . $moduleName . '.php';
	if (unlink($en_usLanguageSettingsFile)) {  
	    echo "Удален файл - languages/en_us/Settings/" . $moduleName . '.php'. "<br>";  
	}  
	
	echo "<br>";
	echo "Началось удаление метаданных из тигры". "<br>";
	$moduleInstance->delete();

}
	else {
			echo "Ошибка, модуль не найден!". "<br>";
			echo "Нужно внести имя модуля как в следующем примере www.site.com/vtdm.php?modulo=ИМЯ_МОДУЛЯ". "<br>";
			echo "Название модуля должно соответствовать названию в URL при открытии страниц модуля". "<br>";
			echo "Файл vtdm.php необходимо поместить в корневую папку vTiger";
}



/**
 * удалить директорию со всеми вложенными файлами
 * @param $dir
 * @return bool
 */
function delDir($dir) {
    $files = array_diff(scandir($dir), ['.','..']);
    foreach ($files as $file) {
        (is_dir($dir.'/'.$file)) ? delDir($dir.'/'.$file) : unlink($dir.'/'.$file);
    }
    return rmdir($dir);
}