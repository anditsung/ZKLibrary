<?php
$server = "universal";
//$server = "latexindo";

if($server == "universal") {
	include("zklibrary.php");
	$server = "localhost";
	$username = "username";
	$password = "password";
	$database = "database";
	$deviceip = "192.168.10.208";
}
else {
	include("zklibrary.php");
	$server = "localhost";
	$username = "username";
	$password = "password";
	$database = "database";
	$deviceip = "192.168.1.205";
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
	$cmd = $_POST['cmd'];
	if($cmd == "update") {
		$zk = new ZKLibrary($deviceip, 4370);
		$ret = $zk->connect();
		if($ret) {
			$zk->disableDevice();
			
			$conn = new mysqli($server, $username, $password, $database);
			
			if($conn->connect_error) {
				$zk->enableDevice();
				$zk->disconnect();
				die("Connection failed: " . $conn->connect_error);
			}

			// save all user data to database
			// empty user table before update
			$query = "TRUNCATE TABLE at_users";
			$conn->query($query);
			
			$users = $zk->getUserV2();
			while(list($uid, $user) = each($users)) {
				$id = $user[0];
				$name = $user[1];
				$role = $user[2];
				$password = $user[3];
				$card = $user[4];
				$enable = $user[5];
				//$query = "INSERT INTO at_users ( uid, id, name, role, password ) VALUES ( '{$uid}', '{$id}', '{$name}', '{$role}', '{$password}' )";
				$query = "INSERT INTO at_users ( uid, id, name, role, password, card, enable ) VALUES ( '{$uid}', '{$id}', '{$name}', '{$role}', '{$password}', '{$card}', '{$enable}' )";
				$conn->query($query);
			}
			
			$attendances = $zk->getAttendance();
			
			// clear att on device
			$zk->clearAttendance();
			
			// we dont need the device connection anymore. removing the lock
			$zk->enableDevice();
			$zk->disconnect();
			
			$query = "SELECT idx FROM at_attendances ORDER BY idx DESC LIMIT 1";
			$result = $conn->query($query);
			$lastNumber = intval($result->fetch_assoc()['idx']);
			$continueNumber = false;
			if($lastNumber > 0) {
				$recordNumber = $lastNumber + 1;
				$continueNumber = true;
			}
			$count = 0;
			while( list($idx, $attendance) = each($attendances)) {
				$uid = $attendance[0];
				$id = $attendance[1];
				$method = $attendance[2];
				$tanggal = $attendance[3];
				if($continueNumber) {
					$query = "INSERT INTO at_attendances ( idx, uid, id, method, waktu ) VALUES ( '{$recordNumber}', '{$uid}', '{$id}', '{$method}', '{$tanggal}' )";
					$recordNumber++;
				}
				else {
					$query = "INSERT INTO at_attendances ( idx, uid, id, method, waktu ) VALUES ( '{$idx}', '{$uid}', '{$id}', '{$method}', '{$tanggal}' )";
				}
				if($conn->query($query)) {
					$count++;
				}
			}
			$conn->close();
			echo "SUCCESS ADDING {$count} DATA";
		}
		else {
			echo "UNABLE TO CONNECT";
		}
	}
	else if($cmd == "generate") {
		$tmpDate = $_POST['startdate'];
		$tmpDate = str_replace('/', '-', $tmpDate);
		$startDate = date('Y-m-d', strtotime($tmpDate));
		
		$tmpDate = $_POST['enddate'];
		$tmpDate = str_replace('/', '-', $tmpDate);
		$endDate = date('Y-m-d', strtotime($tmpDate . ' + 1 days'));
		$conn = new mysqli($server, $username, $password, $database);
		
		if($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		
		$karyawan = array( "null" );
		$query = "SELECT * FROM at_users";
		$result = $conn->query($query);
		while($row = $result->fetch_assoc()) {
			$karyawan[$row['uid']] = $row['name'];
		}
		
		$absensi = array();
		$query = "SELECT at_attendances.uid, at_attendances.waktu, at_users.name FROM at_attendances LEFT JOIN at_users ON at_attendances.uid = at_users.uid WHERE at_attendances.waktu >= '{$startDate}' AND at_attendances.waktu <= '{$endDate}' ORDER BY at_attendances.uid, at_attendances.waktu";
		$result = $conn->query($query);
		if($result) {
			while($row = $result->fetch_assoc()) {
				$uid = $row['uid'];
				$jamAbsensi = date_create($row['waktu']);
				$bt = date_format($jamAbsensi, 'md');
				$btf = date_format($jamAbsensi, 'd-m-Y');
				$jam = date_format($jamAbsensi, 'H:i:s');
				$jamh = date_format($jamAbsensi, 'H');
				//$absensi[$uid][$btf][] = $jam;
				
				if(strlen($absensi[$uid][$btf][0]) == 0) {
					$absensi[$uid][$btf][0] = $jam;
				}
				else {
					// scan twice will ignore same hour
					$tempJ = date_create($absensi[$uid][$btf][0]);
					$tempJH = date_format($tempJ, 'H');
					if($tempJH != $jamh) {
						$absensi[$uid][$btf][1] = $jam;
					}
				}
			}
		}
		else {
			echo "ERROR ON QUERY:<br>";
			echo $query;
		}
		$conn->close();
		
		//parse absensi
		$btIndex = array();
		$loopdate = $startDate;
		while($loopdate < $endDate) {
			$btTemp = date_create($loopdate);
			$btI = date_format($btTemp, 'md');
			$btIF = date_format($btTemp, 'd-m-Y');
			$btIndex[] = $btIF;
			$loopdate = date('Y-m-d', strtotime($loopdate . ' + 1 days'));
		}
		
		$tableStart = "<table id='absensitable' summary='Code page support in different versions of MS Windows.' class='table table-bordered table-striped table-hover table-sm'>";
		//$tableStart = "<table class='hmitabledata' border='1'>";
		//$tableHeader = "<thead class='thead-dark'><tr><th class='headcol'><strong>NAMA</strong></th>";
		$tableHeader = "<thead class='thead-dark'><tr><th><strong>NAMA</strong></th>";
		//$tableHeader = "<tr><th><strong>NAMA<strong></th>";
		foreach($btIndex as $head) {
			$tableHeader .= "<th colspan='2'><strong>" . $head . "<strong></th>";
		}
		$tableHeader .= "</thead></tr>";
		//$tableHeader .= "</tr>";
		
		$tableBody = "";
		foreach(array_keys($absensi) as $uid) {
			//$tableBody .= "<tr><td class='headcol'><strong>" .  $karyawan[$uid] . "</strong></td>";
			$tableBody .= "<tr><td><strong>" .  $karyawan[$uid] . "</strong></td>";
			foreach($btIndex as $lp) {
				$satu = $absensi[$uid][$lp][0];
				$dua = $absensi[$uid][$lp][1];
				if(strlen($satu) == 0) {
					//$tableBody .= "<td style='background-color:red;position:relative;'>" . $satu . "</td>";
					$tableBody .= "<td style='background-color:red'>" . $satu . "</td>";
				}
				else {
					//$tableBody .= "<td style='position:relative;'>" . $satu . "</td>";
					$tableBody .= "<td>" . $satu . "</td>";
				}
				if(strlen($dua) == 0) {
					//$tableBody .= "<td style='position:relative;' bgcolor='red'>" . $dua . "</td>";
					$tableBody .= "<td bgcolor='red'>" . $dua . "</td>";
				}
				else {
					//$tableBody .= "<td style='position:relative;'>" . $dua . "</td>";
					$tableBody .= "<td>" . $dua . "</td>";
				}
			}
			$tableBody .= "</tr>";
		}
		
		$tableEnd = "</table>";
		
		$button = "<button class='btn btn-primary' onclick='exportToExcel()'>EXPORT TO EXCEL</button><br><br>";
		
		$table = $button . $tableStart . $tableHeader . $tableBody . $tableEnd;
		
		echo $table;
	}
	else if($cmd == "generate2") {
		$tmpDate = $_POST['startdate'];
		$tmpDate = str_replace('/', '-', $tmpDate);
		$startDate = date('Y-m-d', strtotime($tmpDate));
		
		$tmpDate = $_POST['enddate'];
		$tmpDate = str_replace('/', '-', $tmpDate);
		$endDate = date('Y-m-d', strtotime($tmpDate . ' + 1 days'));
		$conn = new mysqli($server, $username, $password, $database);
		
		if($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		
		$karyawan = array( "null" );
		$query = "SELECT * FROM at_users";
		$result = $conn->query($query);
		while($row = $result->fetch_assoc()) {
			$karyawan[$row['uid']] = $row['name'];
		}
		
		$absensi = array();
		$query = "SELECT at_attendances.uid, at_attendances.waktu, at_users.name FROM at_attendances LEFT JOIN at_users ON at_attendances.uid = at_users.uid WHERE at_attendances.waktu >= '{$startDate}' AND at_attendances.waktu <= '{$endDate}' ORDER BY at_attendances.uid, at_attendances.waktu";
		$result = $conn->query($query);
		if($result) {
			while($row = $result->fetch_assoc()) {
				$uid = $row['uid'];
				$jamAbsensi = date_create($row['waktu']);
				$bt = date_format($jamAbsensi, 'md');
				$btf = date_format($jamAbsensi, 'd-m-Y');
				$jam = date_format($jamAbsensi, 'H:i:s');
				$jamh = date_format($jamAbsensi, 'H');
				
				if(strlen($absensi[$uid][$btf][0]) == 0) {
					$absensi[$uid][$btf][0] = $jam;
				}
				else {
					// scan twice will ignore same hour
					$tempJ = date_create($absensi[$uid][$btf][0]);
					$tempJH = date_format($tempJ, 'H');
					if($tempJH != $jamh) {
						$absensi[$uid][$btf][1] = $jam;
					}
				}
			}
		}
		else {
			echo "ERROR ON QUERY:<br>";
			echo $query;
		}
		$conn->close();
		
		//parse absensi
		$btIndex = array();
		$loopdate = $startDate;
		while($loopdate < $endDate) {
			$btTemp = date_create($loopdate);
			$btI = date_format($btTemp, 'md');
			$btIF = date_format($btTemp, 'd-m-Y');
			$btIndex[] = $btIF;
			$loopdate = date('Y-m-d', strtotime($loopdate . ' + 1 days'));
		}
		
		$tableStart = "<div id='table-scroll' class='table-scroll'><table id='main-table' class='table table-striped table-hover main-table'>";
		
		$tableHeader = "<thead><tr>";
		$tableHeader .= "<th><strong>NAMA</strong></th>";
		foreach($btIndex as $head) {
			$tableHeader .= "<th colspan='2'><strong>" . $head . "</strong></th>";
		}
		$tableHeader .= "</tr></thead>";
		
		$tableBody = "<tbody>";
		foreach(array_keys($absensi) as $uid) {
			$tableBody .= "<tr><th><strong>" .  $karyawan[$uid] . "</strong></th>";
			foreach($btIndex as $lp) {
				$satu = $absensi[$uid][$lp][0];
				$dua = $absensi[$uid][$lp][1];
				if(strlen($satu) == 0) {
					$tableBody .= "<td style='background-color:red'>" . $satu . "</td>";
				}
				else {
					$tableBody .= "<td>" . $satu . "</td>";
				}
				if(strlen($dua) == 0) {
					$tableBody .= "<td bgcolor='red'>" . $dua . "</td>";
				}
				else {
					$tableBody .= "<td>" . $dua . "</td>";
				}
			}
			$tableBody .= "</tr>";
		}
		$tableBody .= "</tbody>";
		
		$tableEnd = "</table></div>";
		
		$button = "<button class='btn btn-primary' onclick='exportToExcel()'>EXPORT TO EXCEL</button><br><br>";
		
		$table = $button . $tableStart . $tableHeader . $tableBody . $tableEnd;
		
		echo $table;
	}
	else if($cmd == "test") {
		$zk = new ZKLibrary($deviceip, 4370);
		$ret = $zk->connect();
		if($ret) {
			$zk->disableDevice();
			$zk->testVoice();
			echo "TEST VOICE";
			$zk->enableDevice();
			$zk->disconnect();
		}
			
	}
	else if($cmd == "user") {
		$zk = new ZKLibrary($deviceip, 4370);
		$ret = $zk->connect();
		$zk->disableDevice();
	
		$aa = $zk->getUserV2();
		var_dump($aa);
		$zk->enableDevice();
		$zk->disconnect();
	}
	else if($cmd == "fix") {
		$zk = new ZKLibrary($deviceip, 4370);
		$ret = $zk->connect();
		if($ret) {
			$zk->disableDevice();
			echo "DEVICE FIX";
			$zk->enableDevice();
			$zk->disconnect();
		}
	}
	else {
		echo "UNKNOWN COMMAND";
	}
}
else {
	echo "HELLO GET";
}