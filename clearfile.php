<?
const FILEPATH = 'C:\\Users\\Pike\\Desktop\\векторы\\';
const DESKTOPPATH = 'C:\\Users\\Pike\\Desktop\\';

const FILENAME = '1135-1222.txt';
const NEW_FILENAME = "new_".FILENAME;

	
	$source = file(FILENAME);
	$points = [];
	file_put_contents(DESKTOPPATH.NEW_FILENAME,'');
	
	foreach ($source as $row) {
		$row=str_replace("_C","",$row,$cnt);
		if ($cnt>0) {
			echo $row."<br>";
			file_put_contents(DESKTOPPATH.NEW_FILENAME,$row,FILE_APPEND);
		}
	}