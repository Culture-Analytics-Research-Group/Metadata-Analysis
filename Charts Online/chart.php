<!DOCTYPE php>

<head>
	<link href="line_chart_styles.css" rel="stylesheet" type="text/css">
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" charset="utf-8"></script>
	<link href="https://fonts.googleapis.com/css?family=Muli|Open+Sans" rel="stylesheet">
	<script src="http://d3js.org/d3.v3.js" charset="utf-8"></script>
	<script src="chart_script.js" type="text/javascript"></script>

	<?php
		/*
		Copyright 2018 John W. Harlan working under Ana Jofre

		Licensed under the Apache License, Version 2.0 (the "License");
		you may not use this file except in compliance with the License.
		You may obtain a copy of the License at

			http://www.apache.org/licenses/LICENSE-2.0

		Unless required by applicable law or agreed to in writing, software
		distributed under the License is distributed on an "AS IS" BASIS,
		WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
		See the License for the specific language governing permissions and
		limitations under the License.

		This script generates a dynamic form used to query a database in order to
			create line charts
		*/

		//Performs the same task as the now-deprecated fetch_all() function from mysqli
		//Generates a two-dimensional array of sql query results
		function fetchAll($conn, $query){
			$toReturn = array();
			$results = $conn->query($query);
			while($temp = $results->fetch_row()){
				$toReturn[] = $temp;
			}
			return $toReturn;
		}

		//Generates a human-readable string from a field-value pair as the database expects them.
		function query2HRString($field, $value){
			if($value === '1'){
				return $field;
			} else if($value === '0'){
				return "not $field";
			} else if($value === "unkown"){
					return "$field unkown";
			}
			return substr($value, 1, -1);
		}

		//Connection data, secure data.
		$serverName = "";
		$username = "";
		$passwd = "";
		$dbName = "";

		//Connect to the database
		$conn = new mysqli($serverName, $username, $passwd, $dbName);
		if($conn->connect_error){
			die("Connection failed: ".$conn->connect_error);
			echo "<h1>Your request could not be completed.</h1></head>";
		}

		//Determine if the user wanted to use subsets or not, fed in from POST data.
		if($_POST["useSubset"] == "true"){
			$useSubset = true;
		} else {
			$useSubset = false;
		}

		//gets the dates of the issues
		//The current system for organizing by date is kind of messy and difficult to read because they had to be single numbers.
		//	The way it does this is simple-it moves the year to a more significant value using mulitplication, then adds the month.
		//	The same is done again if days are needed. The graphing scirpt requires that he horizontal axis be NUMBERS.
		$dates = array();
		switch($_POST["timeRes"]){
			case "yearly":
				//Yearly
				$queryResults = fetchAll($conn, "SELECT DISTINCT year FROM data ORDER BY year ASC;");
				$years = array();
				$resLen = count($queryResults);
				for($i = 0; $i < $resLen; $i ++){
					array_push($years, $queryResults[$i][0]);

					array_push($dates, $queryResults[$i][0]);
				}
			break;
			case "monthly":
				//Monthly
				$queryResults = fetchAll($conn, "SELECT DISTINCT year,month FROM data ORDER BY year ASC,month ASC;");
				$years = array();
				$months = array();
				$resLen = count($queryResults);
				for($i = 0; $i < $resLen; $i ++){
					array_push($years, $queryResults[$i][0]);
					array_push($months, $queryResults[$i][1]);

					array_push($dates, $queryResults[$i][0]*100 + $queryResults[$i][1]);
					//array_push($dates, $queryResults[$i][0]."-".$queryResults[$i][1]);
				}

			break;
			case "issue":
				//Per issue
				$queryResults = fetchAll($conn, "SELECT DISTINCT year,month,day FROM data ORDER BY year ASC,month ASC,day ASC;");
				$years = array();
				$months = array();
				$days = array();
				$resLen = count($queryResults);
				for($i = 0; $i < $resLen; $i ++){
					array_push($years, $queryResults[$i][0]);
					array_push($months, $queryResults[$i][1]);
					array_push($days, $queryResults[$i][2]);

					array_push($dates, $queryResults[$i][0]*10000 + $queryResults[$i][1]*100 + $queryResults[$i][2]);
					//array_push($dates, (string)$queryResults[$i][0]."-".(string)$queryResults[$i][1]."-".(string)$queryResults[$i][2]);
				}
			break;
			default:
				//This is in case the page is directly accessed without the proper POST data. A step towards secure SQL and sensable usability.
				die("NO VALID DATE");
			break;
		}

		//An array to contain key-value pairs from the form in index.php
		$queries = array();
		//Some protection against SQL injection-This was suggested by my friend Ed Green while I was talking with him about SQL injection.
		$valid_keys = array("multiface", "color", "category", "photo", "angle", "gender", "race", "adult", "smile", "quality");
		$valid_values = array("0", "1", "'female'", "'male'", "'ad'", "'feature'", "'author'", "'cover'", "'unknown'", "'americanindian'", "'asian'", "'black'", "'pacificislander'", "'white'", "'good'", "'poor'", "'fair'", "'discard'");

		//Find all the queries
		foreach($_POST as $x_key => $x_val){
			if(stristr($x_key, "line") and stristr($x_key, "field")){
				$searchVal = substr($x_key, 0, - 5)."value";
				$validQuery = true;
				if(in_array($_POST[$x_key], $valid_keys) == false){
					$validQuery = false;
				}
				if(in_array($_POST[$searchVal], $valid_values) == false){
					$validQuery = false;
				}
				if($validQuery){
					array_push($queries, array($_POST[$x_key], $_POST[$searchVal]));
				} else {
					echo "</br>INVALID QUERY:".$_POST[$x_key]."=".$_POST[$searchVal];
				}
			}
		}

		$data = array();
		$dateCount = count($dates);
		for($i = 0; $i < $dateCount; $i ++){
			//assembles the portion of the SQL query that handles the date.
			switch($_POST["timeRes"]){
				case "yearly":
					$dateString = "year=".$years[$i];
				break;
				case "monthly":
					$dateString = "year=".$years[$i]." AND month=".$months[$i];
				break;
				case "issue":
					$dateString = "year=".$years[$i]." AND month=".$months[$i]." AND day=".$days[$i];
				break;
			}
			//get total number of faces in date
			$queryResults = $conn->query("SELECT COUNT(*) FROM data WHERE $dateString;")->fetch_row();
			$total = $queryResults[0];
			$dataPoint=NULL;
			$dataPoint["date"]=$dates[$i];
			//get number of faces in subset, if subset is used
			if($useSubset){
				$subsetString = $_POST["subsetFieldName"]."=".$_POST["subsetFieldValue"];
				$queryResults = $conn->query("SELECT COUNT(*) FROM data WHERE $dateString AND $subsetString;")->fetch_row();
				$subsetFaces = $queryResults[0];
				$dataPoint["subset"]=$subsetFaces / $total *100;
			}
			//enter query loop
			$queryCount = count($queries);
			for($j = 0; $j < $queryCount; $j++){
				//get number of faces in subset(if used) AND in query
				$queryString = $queries[$j][0]."=".$queries[$j][1];
				if($useSubset){
    					$queryResults = $conn->query("SELECT COUNT(*) FROM data WHERE $dateString AND $subsetString AND $queryString;")->fetch_row();
					$res = $queryResults[0];
					$dataPoint[query2HRString($queries[$j][0], $queries[$j][1])] = $res / $subsetFaces *100;
				} else {
   					$queryResults = $conn->query("SELECT COUNT(*) FROM data WHERE $dateString AND $queryString;")->fetch_row();
					$res = $queryResults[0];
					$dataPoint[query2HRString($queries[$j][0], $queries[$j][1])] = $res / $total *100;
				}
			}
			//push $dataPoint as JSON onto $data for use in js
			array_push($data, json_encode($dataPoint));
		}

		$data_JSON = json_encode($data);
		switch($_POST["timeRes"]){
			case "monthly":
				echo "<h2>Note: For technical reasons, dates are formatted as YYYYMM with no spaces, dashes, slashes or other delimeters.</h2?";
			break;
			case "issue":
				echo "<h2>Note: For technical reasons, dates are formatted as YYYYMMDD with no spaces, dashes, slashes or other delimeters.</h2?";
			break;
		}
	?>
</head>

<body>
	<div class="chart-wrapper" id="chart-line1"><div style ="text-align:right; margin-right=-100px"> click on a label to hide or show</div> </div>
</body>

<script>
	//Generates linecharts using 'chart_script.js'
	//Echos the data array from the previous PHP into a JS array
	var data = new Array(<?php
		$data = json_decode($data_JSON);
		$len = count($data);
		for($i = 0; $i < $len; $i++){
			echo $data[$i];
			if($i < $len -1){
				echo ",  ";
			}
		}
	?>);
	var lines = new Array("date");
	//Make sure everything is a number
	for(var dp in data){
		dp = data[dp];
 		for(var key in dp){
			dp[key] = +dp[key];
			if(!lines.includes(key)){
				lines.push(key);
			}
		}
	}
	console.log(data);
	lines = lines.slice(start=1);
	var datum = new Array();
	for(var line in lines){
		datum[lines[line]] = {column:lines[line]};
	}

	//Actually generates the chart. Then it attatches the chart to the div defined in the body.
	var chart = makeLineChart(data, "date", datum, {xAxis: 'Date', yAxis: 'Percent'});
	chart.bind("#chart-line1");
	chart.render();
</script>
