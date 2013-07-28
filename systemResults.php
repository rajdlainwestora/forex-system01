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

function seriesLoss($series) {
	if ($series['loss']['prev']) { //prev is loss and now is loss
		$series['loss']['val']++;
	}
	else {
		$series['loss']['average']['array'][$series['loss']['average']['index']] = $series['loss']['val'];
		$series['loss']['average']['index']++;
		$series['loss']['val'] = 0;
	}
	if ($series['loss']['val'] > $series['loss']['biggest']) {
		$series['loss']['biggest'] = $series['loss']['val'];
	}
	return $series;
}

function seriesProfit($series) {
	if ($series['profit']['prev']) { //prev is profit and now is profit
		$series['profit']['val']++;
	}
	else {
		$series['profit']['average']['array'][$series['profit']['average']['index']] = $series['profit']['val'];
		$series['profit']['average']['index']++;
		$series['profit']['val'] = 0;
	}
	if ($series['profit']['val'] > $series['profit']['biggest']) {
		$series['profit']['biggest'] = $series['profit']['val'];
	}
	return $series;
}

function calculateData($value, $data) {
	if ($value < 0) {
		$data['series'] = seriesLoss($data['series']);
		$data['transaction']['loss']++;
		$data['transaction']['sumLoss'] += $value;
		$data['series']['loss']['prev'] = true;
		$data['series']['profit']['prev'] = false;
	}
	else {
		$data['series'] = seriesProfit($data['series']);
		$data['transaction']['profit']++;
		$data['transaction']['sumProfit'] += $value;
		$data['series']['loss']['prev'] = false;
		$data['series']['profit']['prev'] = true;
	}
	return $data;
}

function formatData($inputData, $type) {
	$lines = explode("\n", $inputData);
	$formattedData = '';
	$cumulatedValue = 0;
	$cumulatedMin = 0;
	$cumulatedMax = 0;
	$data['transaction']['all'] = 0;
	$data['transaction']['loss'] = 0;
	$data['transaction']['profit'] = 0;
	$data['transaction']['sumLoss'] = 0;
	$data['transaction']['sumProfit'] = 0;
	$data['transaction']['avgLoss'] = 0;
	$data['transaction']['avgProfit'] = 0;
	$data['series']['loss']['prev'] = false; //poprzednia strata w serii
	$data['series']['loss']['val'] = 0; //wartosc straty w serii
	$data['series']['loss']['biggest'] = 0; //najwieksza strata w serii
	$data['series']['profit']['prev'] = false; //poprzedni zysk w serii
	$data['series']['profit']['val'] = 0; //wartosc zysku w serii
	$data['series']['profit']['biggest'] = 0; //najwiekszy zysk w serii
	$data['series']['loss']['average']['index'] = 0; //index tablicy sredniej dlugosci serii strat
	$data['series']['loss']['average']['array'] = array(); //tablica sredniej dlugosci serii strat
	$data['series']['loss']['average']['val'] = 0; //wartosc sredniej dlugosci serii strat
	$data['series']['profit']['average']['index'] = 0; //index tablicy sredniej dlugosci serii zyskow
	$data['series']['profit']['average']['array'] = array(); //tablica sredniej dlugosci serii zyskow
	$data['series']['profit']['average']['val'] = 0; //wartosc sredniej dlugosci serii zyskow
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
			$data['transaction']['all']++;
			$weeklySummary[intVal($el[1])] = array(
				intVal($el[0]),
				$weeklySummary[intVal($el[1])][1] + $el[3]
			);
			$data = calculateData(intVal($el[3]), $data);
			if (intVal($el[3]) < $minMax['min']) {
				$minMax['min'] = intVal($el[3]);
			}
			if (intVal($el[3]) > $minMax['max']) {
				$minMax['max'] = intVal($el[3]);
			}
			$cumulatedValue += intVal($el[3]);
			if ($cumulatedValue < $cumulatedMin) {
				$cumulatedMin = $cumulatedValue;
			}
			if ($cumulatedValue > $cumulatedMax) {
				$cumulatedMax = $cumulatedValue;
			}
			$formattedData = $formattedData."['".$el[1]."', ".$cumulatedValue."],";
		}
	}
	$data['transaction']['lossPercent'] = intVal($data['transaction']['loss'] / $data['transaction']['all'] * 100);
	$data['transaction']['profitPercent'] = intVal($data['transaction']['profit'] / $data['transaction']['all'] * 100);
	$data['transaction']['avgLoss'] = intVal($data['transaction']['sumLoss'] / $data['transaction']['loss']);
	$data['transaction']['avgProfit'] = intVal($data['transaction']['sumProfit'] / $data['transaction']['profit']);
	$drawDown = $cumulatedMax + abs($cumulatedMin);
	$data['series']['loss']['average']['val'] = round(array_sum($data['series']['loss']['average']['array']) / count($data['series']['loss']['average']['array']));
	$data['series']['profit']['average']['val'] = round(array_sum($data['series']['profit']['average']['array']) / count($data['series']['profit']['average']['array']));
	/*
	TODO:
	najdłuższa seria stratnych tygodnii
	średnia seria stratnych tygodnii
	najdłuższa seria zyskownych tygodni
	średnia seria stratnych tygodnii
	*/
	$description = "
		Wszystkich transakcji: ".$data['transaction']['all']."
		<br />
		Wynik po wszystkich transakcjach: $cumulatedValue
		<br />
		Transakcje zyskowne: ".$data['transaction']['profit']." (".$data['transaction']['profitPercent']."%)
		<br />
		Transakcje stratne: ".$data['transaction']['loss']." (".$data['transaction']['lossPercent']."%)
		<br />
		Suma zarobionych pipsów: ".$data['transaction']['sumProfit']."
		<br />
		Suma straconych pipsów: ".$data['transaction']['sumLoss']."
		<br />
		Średnia wartość zyskownej transakcji: ".$data['transaction']['avgProfit']."
		<br />
		Średnia wartość stratnej transakcji: ".$data['transaction']['avgLoss']."
		<br />
		Największa zyskowna transakcja: ".$minMax['max']."
		<br />
		Największa stratna transakcja: ".$minMax['min']."
		<br />
		Historyczne maksimum: ".$cumulatedMax."
		<br />
		Historyczne minimum: ".$cumulatedMin."
		<br />
		Drawdown (obsunięcie): ".$drawDown."
		<br />
		Najdłuższa seria stratnych transakcji: ".$data['series']['loss']['biggest']."
		<br />
		Najdłuższa seria zyskownych transakcji: ".$data['series']['profit']['biggest']."
		<br />
		Średnia seria stratnych transakcji: ".$data['series']['loss']['average']['val']."
		<br />
		Średnia seria zyskownych transakcji: ".$data['series']['profit']['average']['val']."
	";
	$htmlCode = '<div id="chart_div_'.$type.'" style="width: 600px; height: 600px; border: 2px solid black;"></div><br />';
	return array(
		$formattedData,
		$data['transaction'],
		$minMax,
		$cumulatedValue,
		$htmlCode.$description.'<br /><br />',
		parseWeeklySummary($weeklySummary)
	);
}

$output1 = formatData($data, 1); //DEMO + REAL
//$output2 = formatData($data, 2); //DEMO
//$output3 = formatData($data, 3); //REAL

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
			drawChart( //DEMO + REAL
				'System EURUSD (DEMO + REAL)',
				[['Tydzien', 'Skumulowany wynik w pipsach'], ['00', 0], <?php echo $output1[0] ?>],
				'chart_div_1'
			);/*
			drawChart( //DEMO
				'System EURUSD (DEMO)',
				[['Tydzien', 'Skumulowany wynik w pipsach'], ['00', 0], <?php echo $output2[0] ?>],
				'chart_div_2'
			);
			drawChart( //REAL
				'System EURUSD (REAL)',
				[['Tydzien', 'Skumulowany wynik w pipsach'], ['00', 0], <?php echo $output3[0] ?>],
				'chart_div_3'
			);*/
		});
	</script>
</head>
<body>
	<?= $output1[4].$output1[5] ?>
	<?= $output2[4].$output2[5] ?>
	<?= $output3[4].$output3[5] ?>
</body>
</html>
