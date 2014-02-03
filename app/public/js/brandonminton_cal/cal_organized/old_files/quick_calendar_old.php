<?php
/*
 * INSTALLATION:
 * 1. Save the code in a file call quick_calendar.php. Then Insert this file into anywhere where 
 * you want the calendar to appear. Use:
 *
 *      require_once('quick_calendar.php');
 *
 *		or if you save the file elsewhere, require_once('dir_path/quick_calendar.php')
 *
 * 2. Create a table in your database. If you are using your own table, you need to map the fields
 * appropriately.
 *
 * CREATE TABLE `calendar` ( 
 * `id` INT NOT NULL AUTO_INCREMENT ,
`* day` VARCHAR( 2 ) NOT NULL ,
 * `month` VARCHAR( 2 ) NOT NULL ,
 * `year` VARCHAR( 4 ) NOT NULL ,
 * `link` VARCHAR( 255 ) NOT NULL ,
 * `desc` TEXT NOT NULL ,
 * PRIMARY KEY ( `id` ) 
 * );
 *
 * 3. Configure the db and path access below. Use any db of your choice. You can also configure 
 * the CSS to change the look and feel of the calendar.
 */

// This year
$y = date('Y');
// This month
$m = date('n');
// This Day
$d = date('j');
$today = array('day'=>$d, 'month'=>$m, 'year'=>$y);
// If user specify Day, Month and Year, reset the var
if (isset($_GET['m'])) {
	$y = $_GET['y'];
	$m = $_GET['m'];
}

// CONFIGURE THE DB ACCESS
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '2b6m4m2b';
$database = "test";
$dbConnect = mysql_connect($dbhost, $dbuser, $dbpass);
if (!$dbConnect) {
   die('Could not connect: ' . mysql_error());
}
$db_selected = mysql_select_db($database, $dbConnect);
if (!$db_selected) {
   die ('db selection error : ' . mysql_error());
}

// name of table
$tableName = 'calendar';

// name of css
$css = 'calendar';

// Location of the calendar script file from the root
$ajaxPath = './quick_calendar.php';

// END OF CONFIGURATION. YOU CAN CHANGE THE CSS. THE OTHER CODES CAN BE KEPT AS DEFAULT IF YOU WANT.

$sql = "SELECT * FROM $tableName WHERE (month='$m' AND year='$y') || (month='*' AND year='$y') || (month='$m' AND year='*') || (month='*' AND year='*')";

$rs = mysql_query($sql);
$links = array(); 
while ($rw = mysql_fetch_array($rs)) {
	extract($rw);
	$links[] = array('day'=>$day, 'month'=>$month, 'year'=>$year, 'link'=>$link, 'desc'=>$desc, 'what'=>$what);
}
//print_r($links);

?>
<?php 
// if called via ajax, dont display style sheet and javascript again
if (!isset($_GET['ran'])) {
?>
<link rel="stylesheet" type="text/css" href="./calendar.css" />
<script language="javascript">

function createQCObject() { 
   var req; 
   if(window.XMLHttpRequest){ 
      // Firefox, Safari, Opera... 
      req = new XMLHttpRequest(); 
   } else if(window.ActiveXObject) { 
      // Internet Explorer 5+ 
      req = new ActiveXObject("Microsoft.XMLHTTP"); 
   } else { 
      alert('Problem creating the XMLHttpRequest object'); 
   } 
   return req; 
} 

// Make the XMLHttpRequest object 
var http = createQCObject(); 

function displayQCalendar(m,y) {
	var ran_no=(Math.round((Math.random()*9999))); 
	http.open('get', '<?= $ajaxPath; ?>?m='+m+'&y='+y+'&ran='+ran_no);
   	http.onreadystatechange = function() {
		if(http.readyState == 4 && http.status == 200) { 
      		var response = http.responseText;
      		if(response) { 
				document.getElementById("quickCalendar").innerHTML = http.responseText; 
      		} 
   		} 
	} 
   	http.send(null); 
}

function renderData(div, str){
	document.getElementById(div).innerHTML = str;
}

function printList(date, what){
	document.getElementById('event_list').innerHTML = '<span class="title">Events for: '+date+'</span><br />'+what;
	document.getElementById('event_list').style.border = "thin solid #000000";
	//document.getElementById('event_list').innerHTML = '<span class="title">Events for: '+date+'</span><br /><a href="'+linkTo+'" onclick="document.getElementById('description').innerHTML = '+desc+';">'+what+'</a>';
	//document.getElementById('event_list').innerHTML += '<br /><a href="'+linkTo+'" onclick="return renderData(\'description\', \''+desc+'\');">'+what+'</a>';
}

</script>
<?php 
}
?>
<?php
class CreateQCalendarArray {

	var $daysInMonth;
	var $weeksInMonth;
	var $firstDay;
	var $week;
	var $month;
	var $year;

	function CreateQCalendarArray($month, $year) {
		$this->month = $month;
		$this->year = $year;
		$this->week = array();
		$this->daysInMonth = date("t",mktime(0,0,0,$month,1,$year));
		// get first day of the month
		$this->firstDay = date("w", mktime(0,0,0,$month,1,$year));
		$tempDays = $this->firstDay + $this->daysInMonth;
		$this->weeksInMonth = ceil($tempDays/7);
		$this->fillArray();
	}
	
	function fillArray() {
		// create a 2-d array
		for($j=0;$j<$this->weeksInMonth;$j++) {
			for($i=0;$i<7;$i++) {
				$counter++;
				$this->week[$j][$i] = $counter; 
				// offset the days
				$this->week[$j][$i] -= $this->firstDay;
				if (($this->week[$j][$i] < 1) || ($this->week[$j][$i] > $this->daysInMonth)) {	
					$this->week[$j][$i] = "";
				}
			}
		}
	}
}

class QCalendar {
	
	var $html;
	var $weeksInMonth;
	var $week;
	var $month;
	var $year;
	var $today;
	var $links;
	var $css;

	function QCalendar($cArray, $today, &$links, $css='') {
		$this->month = $cArray->month;
		$this->year = $cArray->year;
		$this->weeksInMonth = $cArray->weeksInMonth;
		$this->week = $cArray->week;
		$this->today = $today;
		$this->links = $links;
		$this->css = $css;
		$this->createHeader();
		$this->createBody();
		$this->createFooter();
	}
	function createHeader() {
  		$header = date('M', mktime(0,0,0,$this->month,1,$this->year)).' '.$this->year;
  		$nextMonth = $this->month+1;
  		$prevMonth = $this->month-1;
  		// thanks adam taylor for modifying this part
		switch($this->month) {
			case 1:
	   			$lYear = $this->year;
   				$pYear = $this->year-1;
   				$nextMonth=2;
   				$prevMonth=12;
   				break;
  			case 12:
   				$lYear = $this->year+1;
   				$pYear = $this->year;
   				$nextMonth=1;
   				$prevMonth=11;
      			break;
  			default:
      			$lYear = $this->year;
	   			$pYear = $this->year;
    	  		break;
  		}
		// --
		$this->html = "<table cellspacing='0' cellpadding='0' class='$this->css'>
		<tr>
		<th class='header'>&nbsp;<a href=\"javascript:;\" onclick=\"displayQCalendar('$this->month','".($this->year-1)."')\" class='headerNav' title='Prev Year'><<</a></th>
		<th class='header'>&nbsp;<a href=\"javascript:;\" onclick=\"displayQCalendar('$prevMonth','$pYear')\" class='headerNav' title='Prev Month'><</a></th>
		<th colspan='3' class='header'>$header</th>
		<th class='header'><a href=\"javascript:;\" onclick=\"displayQCalendar('$nextMonth','$lYear')\" class='headerNav' title='Next Month'>></a>&nbsp;</th>
		<th class='header'>&nbsp;<a href=\"javascript:;\" onclick=\"displayQCalendar('$this->month','".($this->year+1)."')\"  class='headerNav' title='Next Year'>>></a></th>
		</tr>";
	}
	
	function createBody(){
//echo $this->links[0]['day'];
		// start rendering table
		// echo "<a href='#_self' onclick='document.getElementById(&#39description&#39).innerHTML = &#39something fun!!!&#39;'>a test link</a>"; the testing single quote thingy
		$this->html.= "<tr><th>S</th><th>M</th><th>T</th><th>W</th><th>Th</th><th>F</th><th>S</th></tr>";
		for($j=0;$j<$this->weeksInMonth;$j++) {
			$this->html.= "<tr>";
			for ($i=0;$i<7;$i++) {
				$cellValue = $this->week[$j][$i];
				$theMonth = date('F', mktime(0,0,0,$cellValue+1,1,$this->year));
				// if today
				if (($this->today['day'] == $cellValue) && ($this->today['month'] == $this->month) && ($this->today['year'] == $this->year)) {
					$cell = "<div class='today'>$cellValue</div>";
				}
				// else normal day
				else {
					$cell = "$cellValue";
				}
				// if days with link
			foreach ($this->links as $val) {
				$aLinkVar = "";
					if (($val['day'] == $cellValue) && (($val['month'] == $this->month) || ($val['month'] == '*')) && (($val['year'] == $this->year) || ($val['year'] == '*'))) {
						for ($k=0;$k<count($this->links);$k++){						
							if ((!empty($cell)) && ($this->links[$k]['day'] == $cellValue) ){
							$aLink = $this->links[$k];
								//echo ($k+1).". ".$val['day']." and ".$cellValue."<br />";
								$desc=str_replace(" ","\\\\t",$aLink['desc']);  //admit, this is out of control...but a very small ammount of code!
								$aLinkVar .= "<a href=\'".$aLink['link']."\' onclick=renderData(\'description\',\'{$desc}\'); >".$aLink['what']."</a><br />";
								$cell = "<a href=\"{$aLink['link']}\" onclick=\"printList('{$theMonth} {$cellValue}, {$aLink['year']}', '{$aLinkVar}');\"><div class='link'>$cellValue</div></a>";
							}
						}
						break;
					} 
				}	
				$this->html.= "<td>$cell</td>";
			}
			$this->html.= "</tr>";
		}	
	}	
	
	function createFooter() {
		$this->html .= "<tr><td colspan='7' class='footer'><a href=\"javascript:;\" onclick=\"displayQCalendar('{$this->today['month']}','{$this->today['year']}')\" class='footerNav'>Today is {$this->today['day']} ".date('M', mktime(0,0,0,$this->today['month'],1,$this->today['year']))." {$this->today['year']}</a></td></tr></table>";
	}
	
	function render() {
		echo $this->html;
	}
}
?>

<?php
// render calendar now
$cArray = &new CreateQCalendarArray($m, $y);
$cal = &new QCalendar($cArray, $today, $links, $css);
if (!isset($_GET['ran'])) {
	echo "<div id='quickCalendar'>";
}
$cal->render();
if (!isset($_GET['ran'])) {
	echo "</div>";
}
?>