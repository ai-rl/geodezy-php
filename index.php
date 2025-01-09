<?
const FILEPATH = 'C:\\Users\\Pike\\Desktop\\';
const FILENAME = 'test.txt';
const NEW_FILENAME = "new_".FILENAME;

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
// возвращает значение угла в долях градуса и третий параметр функции $s
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
	$x0 = $zeroPoint['x'];
	$y0 = $zeroPoint['y'];
	$aRad = $a/180 * M_PI;
	$newPoint = [];
	
	foreach ($points as $point) {
		$x = $x0 + $point['x']*cos($aRad) - $point['y']*sin($aRad);
		$y = $y0 + $point['x']*sin($aRad) + $point['y']*cos($aRad);
		$newPoints[] = ['x'=>$x,'y'=>$y];
	}
	
	return $newPoints;
}


//***********************************************************
//**********************НАЧАЛО КОДА**************************
//***********************************************************
// читаем точки из файла TXT и записываем их в массив
	$source = file(FILEPATH.FILENAME);
	$points = [];
	
	foreach ($source as $point) {
		list($name,$coordX,$coordY)=explode(",",$point);
		$name = str_replace('ОП','OP',$name);
		$points[] = ['name'=>trim($name),
					'x'=>trim($coordX),
					'y'=>trim($coordY)];
	}
	unset($source);
	
	sort($points);
//	$points = array_reverse($points);

	$cntPoints = count($points);
	
	echo "<pre>";
/*	for ($i=1;$i<$cntPoints-1;$i++) {
		print_r( Deg2Dms(VectorsAngleDeg($points[$i-1],$points[$i],$points[$i+1])));
	}
*/
	
// массив точек, которые мы хотим повернуть
	$dopPoints = [	array('x'=>-25,'y'=>0),
					array('x'=>0,'y'=>-25),
					array('x'=>25,'y'=>0),
					array('x'=>0,'y'=>25)];

					
// создаем файл для хранения новых точек
	$headCSV = "Name;X;Y;H;Cod\n";
	file_put_contents(FILEPATH.NEW_FILENAME,'');
					
					
	for ($i=0;$i<=$cntPoints-1;$i++) {
	
// ищем разницу дирекционников, берем среднее значение и получаем дирекционный угол оси опоры
		if($i==0) {
			$newDirect = OGZ($points[$i],$points[$i+1]);
		}elseif ($i==$cntPoints-1) {
			$newDirect = OGZ($points[$i-1],$points[$i]);
		}else{
			$direct1 = OGZ($points[$i-1],$points[$i]);
			$direct2 = OGZ($points[$i],$points[$i+1]);
			$razn = $direct2 - $direct1;
			if ($razn > 180)
				$newDirect = $direct1 + $razn/2 + 180;
			elseif ($razn < -180)
				$newDirect = $direct1 + $razn/2 - 180;
			else
				$newDirect = $direct1 + $razn/2;
		}
		
// производим поворот и записываем точки в файл		
		$rotatedPoints = PovorotSK ($dopPoints,$points[$i],$newDirect);
	
		$pointCSV = "{$points[$i]['name']}_C,{$points[$i]['x']},{$points[$i]['y']}\n";
		file_put_contents(FILEPATH.NEW_FILENAME,$pointCSV,FILE_APPEND);
		echo $pointCSV;
		$j=1;
		foreach($rotatedPoints as $rotatedPoint) {
			$roundX = round($rotatedPoint['x'],3);
			$roundY = round($rotatedPoint['y'],3);
			$pointCSV = "{$points[$i]['name']}_VZ{$j},$roundX,$roundY\n";
			file_put_contents(FILEPATH.NEW_FILENAME,$pointCSV,FILE_APPEND);
			echo $pointCSV;
			$j++;
		}
	}
	
?>