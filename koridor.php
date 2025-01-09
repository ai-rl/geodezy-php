<?
const FILEPATH = 'C:\\Users\\Pike\\Desktop\\';
const FILENAME = '41-18_trassa1.csv';
const FILENAME2 = '41-18_poper1.csv';
const NEW_FILENAME = "KORIDOR_".FILENAME;

// принимает точки в виде массива с ключами 'x' и 'y'
function VectorsAngleDeg ($point1,$point2,$point3) {
	$x1 = $point1['x']-$point2['x'];
	$y1 = $point1['y']-$point2['y'];
	$x2 = $point3['x']-$point2['x'];
	$y2 = $point3['y']-$point2['y'];
	
		$a = $x1*$x2 + $y1*$y2;
	$b = sqrt($x1*$x1+$y1*$y1)*sqrt($x2*$x2+$y2*$y2);
	if ($b!=0)
		$c = $a / $b;
	else 
		$c = 0;
	
	
	return acos($c)/M_PI*180;
}

// доли градуса в г.м.с
function Deg2Dms ($data) {
	if ($data<0) {
		$znak = -1;
		$data = abs($data);
	} else $znak = 1;
	$d = floor($data);
	$m = floor(($data-$d)*60);
	$s = round(($data - $d - $m/60)*3600,3);
	
	$d = $d*$znak;
	$strDms = $d."d".$m."m".$s."s";
	$angle = ['d'=>$d, 'm'=>$m, 's'=>$s];
	
	return $strDms;
}

// г.м.с в доли градуса
function Dms2Deg ($angle) {
	$d = $angle['d'];
	$m = $angle['m'];
	$s = $angle['s'];
	
	if ($d<0) {
		$znak = -1;
		$d = abs($d);
	} else $znak = 1;
	
	$deg = $d + $m/60 + $s/3600;
	$deg = $deg * $znak;
	return $deg;
}

// Прямая Геодезическая Задача
function PGZ ($X1,$Y1,$S,$a) {
	$point2 = [];
	$aRad = $a/180*M_PI;
	$point2['x'] = $X1 + $S*cos($aRad);
	$point2['y'] = $Y1 + $S*sin($aRad);
	return $point2;
}

// Обратная Геодезическая Задача
// возвращает значение угла в долях градуса $a и длину $s
function OGZ ($point1,$point2,&$s=0) {
//try{
	$dx = $point2['x'] - $point1['x'];
	$dy = $point2['y'] - $point1['y'];
	$s = sqrt($dx*$dx+$dy*$dy);
	if ($dy == 0) 
		if ($dx>=0) return 0;
		else return 180;
	if ($dx == 0)
		if ($dy>0) return 90;
		else return 270;
	
	$r = abs(atan($dy/$dx)/M_PI*180);
	if ($dy>0 && $dx>0) $a = $r;
	if ($dy>0 && $dx<0) $a = 180 - $r;
	if ($dy<0 && $dx<0) $a = 180 + $r;
	if ($dy<0 && $dx>0) $a = 360 - $r;
	
	return $a;
//} catch() {}
}

// пересчет новых координат для точек в Условной СК
function PovorotSK ($points,$zeroPoint,$a) {
	unset($points['pk']);
	$x0 = $zeroPoint['x'];
	$y0 = $zeroPoint['y'];
	$aRad = $a/180 * M_PI;
	$newPoint = [];
	
	foreach ($points as $point) {
		$x = $x0 + $point['x']*cos($aRad) - $point['y']*sin($aRad);
		$y = $y0 + $point['x']*sin($aRad) + $point['y']*cos($aRad);
		$newPoints[] = ['x'=>$x,'y'=>$y,'z'=>$point['z']];
	}
	
	return $newPoints;
}


// поиск пикета по трассе
function PoiskPiketa ($trassa,$piket,&$a=0) {

$cnt = count($trassa);
$i = 0;
$summa = 0;

while ( ($i < $cnt-2) and ($summa<$piket) ) {
		$point1 = $trassa[$i];
		$point2 = $trassa[$i+1];
		$a = OGZ ($point1,$point2,$s);
		$summa = $summa + $s;
		$rumb = $piket - $summa;
		$i++;
	}
	$point1 = $trassa[$i];
	$point2 = $trassa[$i+1];


	
	
	$pointPiket = PGZ ($point1['x'],$point1['y'],$rumb,OGZ($point1,$point2));
	
	return $pointPiket;
}



//***********************************************************
//**********************НАЧАЛО КОДА**************************
//***********************************************************
	echo "<pre>";
// читаем трассу из файла TXT и записываем её в массив
	$source = file(FILEPATH.FILENAME);
	$trassa = [];
	
	foreach ($source as $point) {
		list($name,$coordX,$coordY)=explode(";",$point);
		$name = str_replace('ОП','OP',$name);
		$trassa[] = ['name'=>trim($name),
					'x'=>trim($coordX),
					'y'=>trim($coordY)];
	}
	unset($source);
	
	sort($trassa);

// на случай, если трассу надо обратить 
//	$trassa = array_reverse($trassa); 
	$cntPoints = count($trassa);
	
	
	// читаем попекречники из файла TXT и записываем их в массив
	$source = file(FILEPATH.FILENAME2);
	$popere4niki = [];
	
	foreach ($source as $line) {
		$stroka = explode(";",$line);
		$piket = $stroka[0];
		$popere4niki[$piket] = ['pk'=>$piket];
//		echo $piket.' - ';
		$stroka = array_diff($stroka, array(''));
		$cntPoints2 = (count($stroka)-1)/2;
//		echo $cntPoints2.'<br>';
		
// создаём многомерный массив с поперечниками
			for ($i=1; $i<=$cntPoints2; $i++) {
			$popere4niki[$piket][$i] =['x'=>0,'y'=>trim($stroka[$i*2-1]),'z'=>trim($stroka[$i*2])];
		}
//		print_r ($popere4niki[$piket]);
	}
	unset($source);
	
	
	
// создаем файл для хранения новых точек
	$headCSV = "Name;X;Y;H;Cod\n";
	file_put_contents(FILEPATH.NEW_FILENAME,'');
	
	$j = 0;
	foreach ($popere4niki as $popere4) {
		$a = 0;
		$piketXY = PoiskPiketa ($trassa,$popere4['pk'],$a);
		echo $a.' - ';
		// поворачиваем поперечники 
		$newPoints = PovorotSK ($popere4,$piketXY,$a);
		print_r($newPoints);
		foreach ($newPoints as $newPoint) {
			$x = round($newPoint['x'],3);
			$y = round($newPoint['y'],3);
			$z = round($newPoint['z'],3);
			$CSV = "{$j},{$x},{$y},{$z}\n";
			file_put_contents(FILEPATH.NEW_FILENAME,$CSV,FILE_APPEND);
		}
		$j++;
	}
	
	
?>