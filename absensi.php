<html>
	<head>
		<?php 
		$server = "universal";
		//$server = "latexindo";
		if($server == "universal") {
			$address = "192.168.10.200";	
		}
		else {
			$address = "192.168.1.200";
		}
		?>
		<link rel="stylesheet" href="http://<?php echo $address; ?>/libs/bootstrap4/css/bootstrap.css">
		<link rel="stylesheet" href="http://<?php echo $address; ?>/libs/bootstrap-datepicker/css/bootstrap-datepicker.css">
		<script src="http://<?php echo $address; ?>/libs/jquery331/jquery-3.3.1.js"></script>
		<script src="http://<?php echo $address; ?>/libs/bootstrap4/js/bootstrap.js"></script>
		<script src="http://<?php echo $address; ?>/libs/bootstrap-datepicker/js/bootstrap-datepicker.js"></script>
		<script src="http://<?php echo $address; ?>/libs/table2excel/jquery.table2excelold.js"></script>
		<title>ABSENSI</title>
	</head>
	<style>
		body {
			background-color: lightblue;
		}
		.table-scroll {
			position: relative;
			width:100%;
			z-index: 1;
			margin: auto;
			overflow: auto;
			height: 55vh;
		}
		.table-scroll table {
			width: 100%;
			min-width: 2280px;
			margin: auto;
			border-collapse: separate;
			border-spacing: 0;
		}
		.table-wrap {
			position: relative;
		}
		.table-scroll th,
		.table-scroll td {
			padding: 5px 10px;
			border: 1px solid #000;
			#background: #fff;
			vertical-align: top;
			text-align: center;
		}
		.table-scroll thead th {
			background: #333;
			color: #fff;
			position: -webkit-sticky;
			position: sticky;
			top: 0;
		}
		.table-scroll tfoot,
		.table-scroll tfoot th,
		.table-scroll tfoot td {
			position: -webkit-sticky;
			position: sticky;
			bottom: 0;
			background: #666;
			color: #fff;
			z-index:4;
		}
		th:first-child {
			position: -webkit-sticky;
			position: sticky;
			left: 0;
			z-index: 2;
			background: #ccc;
		}
		thead th:first-child, 
		tfoot th:first-child {
			z-index: 5;
		}
		
	</style>
	<script>
		function updateDatabase() {
			$('#generateButton').prop("disabled", true)
			document.getElementById("resultid").innerHTML = "<center><img id='loader-img' alt='' src='loading.gif' width='100' height='100'/></center>"
			$.ajax({
				url: "absensilogic.php",
				method: "POST",
				data: { cmd : "update"},
				success: function(data) {
					document.getElementById("resultid").innerHTML = ""
					alert(data)
					$('#generateButton').prop("disabled", false)
				},
			})
		}
		
		function generateDatabase() {
			var startDate = document.getElementById("startdateid").value
			var endDate = document.getElementById("enddateid").value
			document.getElementById("resultid").innerHTML = "<center><img id='loader-img' alt='' src='loading.gif' width='100' height='100'/></center>"
			$('#updateButton').prop("disabled", true);
			$.ajax({
				url: "absensilogic.php",
				method: "POST",
				data: { cmd : "generate2", startdate: startDate, enddate: endDate},
				success: function(data) {
					document.getElementById("resultid").innerHTML = data
					$('#updateButton').prop("disabled", false)
				},
			})
		}
		
		function testConnect() {
			$.ajax({
				url: "absensilogic.php",
				method: "POST",
				data: { cmd : "user" },
				success: function(data) {
					alert(data);
				}
			})
		}
		
		function fixConnect() {
			$.ajax({
				url: "absensilogic.php",
				method: "POST",
				data: { cmd : "fix" },
				success: function(data) {
					alert(data);
				}
			})
		}
		
		function to_mysql_date(tanggal) {
			var _tanggal = tanggal.split('/');
			return _tanggal.join('-');
		}
		
		function exportToExcel() {
			var startDate = to_mysql_date(document.getElementById('startdateid').value)
			var endDate = to_mysql_date(document.getElementById('enddateid').value)
			$("#main-table").table2excel({
				name: "ABSENSI UNIVERSAL GLOVES",
				filename: "ABSENSI_UG_FROM_" + startDate + '_TO_' + endDate,
				fileext: ".xls"
			});
		}
		
		$( document ).ready(function() {
			$('#startdateid').datepicker({
				format: "dd/mm/yyyy",
				todayBtn: "linked",
				language: "id",
				autoclose: true,
				todayHighlight: true,
			});
			$('#enddateid').datepicker({
				format: "dd/mm/yyyy",
				todayBtn: "linked",
				language: "id",
				autoclose: true,
				todayHighlight: true,
			});
		});
		
	</script>
	<body>

		<div class="container">
			<h1 class="display-3 text-center">ABSENSI</h1>
		</div>
		<div class="container">
			<table class="table text-center">
				<tr>
					<td><strong>START DATE</strong></td>
					<td><strong>END DATE</strong></td>
					<td colspan='2'><strong>ACTION</strong></td>
				</tr>
				<tr>
					<?php
						$hariini = new DateTime("now", new DateTimeZone("Asia/Jakarta"));
						$hariiniTemp = clone $hariini;
						$firstDayMonth = $hariiniTemp->format("01/m/Y");
						$todayMonth = $hariini->format("d/m/Y");
					?>
					<td><input id="startdateid" class="form-control text-center" value='<?php echo $firstDayMonth; ?>'/></td>
					<td><input id="enddateid" class="form-control text-center" value='<?php echo $todayMonth; ?>'/></td>
					<td><button id="generateButton" type="button" class="btn btn-primary" onclick="generateDatabase()">GENERATE</button></td>
					<td><button id="updateButton" type="button" class="btn btn-primary" onclick="updateDatabase()">UPDATE DATABASE</button></td>
				</tr>
			</table>
		</div>


		<div class="container tablestyle" id="resultid">
		</div>

	</body>
</html>