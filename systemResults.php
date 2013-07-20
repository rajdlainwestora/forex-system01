<form method="post" action="#">
  <textarea name="data" placeholder="2 [numer] wynik"></textarea>
	<br /><input type="submit" value="Submit" />
</form>

<?php

error_reporting(0);
$messages['xAxisTitle'] = 'Numer miesiąca';
$messages['reportTitle'] = 'Podsumowanie miesięcy';
$messages['periodName'] = 'miesiąc';
$messages['xAxisTitle'] = 'Numer tygodnia';
$messages['reportTitle'] = 'Podsumowanie tygodni';
$messages['periodName'] = 'tydzień';

function calculateResult($type, $price, $sl) {
	$factor = 0;
	if ($type == "buy") $factor = -1;
	elseif ($type == "sell") $factor = 1;
	return intval(($price - $sl) * 100000 * $factor);
}

function parseWeeklySummary($data) {
	global $messages;
	$return = $messages['reportTitle'].':<br />';
	foreach($data as $i => $week) {
		$weekType = ($week[0] == 2) ? 'REAL' : 'DEMO';
		$return = $return.'['.$weekType.'] '.$i.' '.$messages['periodName'].' '.$week[1].'<br />';
	}
	return $return;
}

include('data.php');

if ($_POST) {
	$data = $_POST['data'];
}

function formatData($data, $type) {
	$lines = explode("\n", $data);
	$formattedData = '';
	$cumulatedValue = 0;
	$trasnaction['all'] = 0;
	$trasnaction['loss'] = 0;
	$trasnaction['profit'] = 0;
	$trasnaction['sumLoss'] = 0;
	$trasnaction['sumProfit'] = 0;
	$trasnaction['avgLoss'] = 0;
	$trasnaction['avgProfit'] = 0;
	$minMax['min'] = 0;
	$minMax['max'] = 0;
	$weeklySummary = array();
	foreach($lines as $e) {
		$el = explode(" ", $e);
		if (
			($type == 1)
			|| ($type == 2 && $el[0] == 1)
			|| ($type == 3 && $el[0] == 2)
		) {
			$trasnaction['all']++;
			$weeklySummary[intVal($el[1])] = array(
				intVal($el[0]),
				$weeklySummary[intVal($el[1])][1] + $el[2]
			);
			if (intVal($el[2]) < 0) {
				$trasnaction['loss']++;
				$trasnaction['sumLoss'] += intVal($el[2]);
			}
			else {
				$trasnaction['profit']++;
				$trasnaction['sumProfit'] += intVal($el[2]);
			}
			if (intVal($el[2]) < $minMax['min']) {
				$minMax['min'] = intVal($el[2]);
			}
			if (intVal($el[2]) > $minMax['max']) {
				$minMax['max'] = intVal($el[2]);
			}
			$cumulatedValue += intVal($el[2]);
			$formattedData = $formattedData."['".$el[1]."', ".$cumulatedValue."],";
		}
	}
	$trasnaction['lossPercent'] = intVal($trasnaction['loss'] / $trasnaction['all'] * 100);
	$trasnaction['profitPercent'] = intVal($trasnaction['profit'] / $trasnaction['all'] * 100);
	$trasnaction['avgLoss'] = intVal($trasnaction['sumLoss'] / $trasnaction['loss']);
	$trasnaction['avgProfit'] = intVal($trasnaction['sumProfit'] / $trasnaction['profit']);
	$description = "
		Wszystkich transakcji: ".$trasnaction['all']."
		<br />
		Wynik po wszystkich transakcjach: $cumulatedValue
		<br />
		Transakcje zyskowne: ".$trasnaction['profit']." (".$trasnaction['profitPercent']."%)
		<br />
		Transakcje stratne: ".$trasnaction['loss']." (".$trasnaction['lossPercent']."%)
		<br />
		Suma zarobionych pipsów: ".$trasnaction['sumProfit']."
		<br />
		Suma straconych pipsów: ".$trasnaction['sumLoss']."
		<br />
		Średnia wartość zyskownej transakcji: ".$trasnaction['avgProfit']."
		<br />
		Średnia wartość stratnej transakcji: ".$trasnaction['avgLoss']."
		<br />
		Największa zyskowna transakcja: ".$minMax['max']."
		<br />
		Największa stratna transakcja: ".$minMax['min']."
	";
	return array(
		$formattedData,
		$trasnaction,
		$minMax,
		$cumulatedValue,
		'<br />'.$description.'<br />',
		parseWeeklySummary($weeklySummary)
	);
}

$output1 = formatData($data, 1);
$output2 = formatData($data, 2);
$output3 = formatData($data, 3);

?>
<html>
<head>
	<meta charset='utf-8'>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript">
		function drawChart(getTitle, getData, getId) {
			var data = google.visualization.arrayToDataTable(getData);
			var options = {
				title: getTitle,
				hAxis: {title: '<?=$messages['xAxisTitle']?>'},
				vAxis: {title: 'Pips'},
				legend: {position: 'top'},
				chartArea: {width:"80%",height:"80%"}
			};
			var chart = new google.visualization.LineChart(document.getElementById(getId));
			chart.draw(data, options);
		}
		google.load("visualization", "1", {packages:["corechart"]});
		google.setOnLoadCallback(function() {
			drawChart(
				'System EURUSD (DEMO + REAL)',
				[['Tydzien', 'Skumulowany wynik w pipsach'], ['00', 0], <?php echo $output1[0] ?>],
				'chart_div_1'
			);
			drawChart(
				'System EURUSD (DEMO)',
				[['Tydzien', 'Skumulowany wynik w pipsach'], ['00', 0], <?php echo $output2[0] ?>],
				'chart_div_2'
			);
			drawChart(
				'System EURUSD (REAL)',
				[['Tydzien', 'Skumulowany wynik w pipsach'], ['00', 0], <?php echo $output3[0] ?>],
				'chart_div_3'
			);
		});
	</script>
</head>
<body>
	<div id="chart_div_1" style="width: 600px; height: 600px; border: 2px solid black;"></div>
	<?php echo $output1[4] ?>
	<br />
	<?php echo $output1[5] ?>
	<br />
	<div id="chart_div_2" style="width: 600px; height: 600px; border: 2px solid black;"></div>
	<?php echo $output2[4] ?>
	<br />
	<?php echo $output2[5] ?>
	<br />
	<div id="chart_div_3" style="width: 600px; height: 600px; border: 2px solid black;"></div>
	<?php echo $output3[4] ?>
	<br />
	<?php echo $output3[5] ?>
	<br />
</body>
</html>
