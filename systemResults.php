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
$messages['chartTitle'][1] = 'System EURUSD (DEMO + REAL)';
$messages['chartTitle'][2] = 'System EURUSD (DEMO)';
$messages['chartTitle'][3] = 'System EURUSD (REAL)';

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
		$normalValue = $week[2] / $week[1];
		if ($week[1] == 1) {
			$return = $return.'['.$weekType.'] '.$i.' '.$messages['periodName'].' '.$week[2].'<br />';
		}
		else {
			$return = $return.'['.$weekType.'] '.$i.' '.$messages['periodName'].' '.$normalValue.' * '.$week[1].' = '.$week[2].'<br />';
		}
	}
	return $return.'<br />';
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

function prepareDescription($descData) {
	return "
		Wszystkich transakcji: ".$descData['transaction']['all']."
		<br />
		Wynik po wszystkich transakcjach: ".$descData['cumulated']['Value']."
		<br />
		Transakcje zyskowne: ".$descData['transaction']['profit']." (".$descData['transaction']['profitPercent']."%)
		<br />
		Transakcje stratne: ".$descData['transaction']['loss']." (".$descData['transaction']['lossPercent']."%)
		<br />
		Suma zarobionych pipsów: ".$descData['transaction']['sumProfit']."
		<br />
		Suma straconych pipsów: ".$descData['transaction']['sumLoss']."
		<br />
		Średnia wartość zyskownej transakcji: ".$descData['transaction']['avgProfit']."
		<br />
		Średnia wartość stratnej transakcji: ".$descData['transaction']['avgLoss']."
		<br />
		Największa zyskowna transakcja: ".$descData['minMax']['max']."
		<br />
		Największa stratna transakcja: ".$descData['minMax']['min']."
		<br />
		Historyczne maksimum: ".$descData['cumulated']['Max']."
		<br />
		Historyczne minimum: ".$descData['cumulated']['Min']."
		<br />
		Drawdown (obsunięcie): ".$descData['transaction']['drawDown']."
		<br />
		Najdłuższa seria stratnych transakcji: ".$descData['series']['loss']['biggest']."
		<br />
		Najdłuższa seria zyskownych transakcji: ".$descData['series']['profit']['biggest']."
		<br />
		Średnia seria stratnych transakcji: ".$descData['series']['loss']['average']['val']."
		<br />
		Średnia seria zyskownych transakcji: ".$descData['series']['profit']['average']['val']."<br /><br />
	";
}

function prepareJs($type, $serializedArray, $messages) {
	return "<script type=\"text/javascript\">
		google.setOnLoadCallback(function() {
			drawChart(
				'".$messages['chartTitle'][$type]."',
				[['Tydzien', 'Skumulowany wynik w pipsach'], ['00', 0], ".$serializedArray."],
				'chart_div_".$type."'
			);
		});
	</script>";
}

function prepareData() {
	$default = array();
	$default['transaction']['all'] = 0;
	$default['transaction']['loss'] = 0;
	$default['transaction']['profit'] = 0;
	$default['transaction']['sumLoss'] = 0;
	$default['transaction']['sumProfit'] = 0;
	$default['transaction']['avgLoss'] = 0;
	$default['transaction']['avgProfit'] = 0;
	$default['transaction']['drawDown'] = 0;
	$default['minMax']['min'] = 0;
	$default['minMax']['max'] = 0;
	$default['cumulated']['Value'] = 0;
	$default['cumulated']['Min'] = 0;
	$default['cumulated']['Max'] = 0;
	$default['series']['loss']['prev'] = false; //poprzednia strata w serii
	$default['series']['loss']['val'] = 0; //wartosc straty w serii
	$default['series']['loss']['biggest'] = 0; //najwieksza strata w serii
	$default['series']['profit']['prev'] = false; //poprzedni zysk w serii
	$default['series']['profit']['val'] = 0; //wartosc zysku w serii
	$default['series']['profit']['biggest'] = 0; //najwiekszy zysk w serii
	$default['series']['loss']['average']['index'] = 0; //index tablicy sredniej dlugosci serii strat
	$default['series']['loss']['average']['array'] = array(); //tablica sredniej dlugosci serii strat
	$default['series']['loss']['average']['val'] = 0; //wartosc sredniej dlugosci serii strat
	$default['series']['profit']['average']['index'] = 0; //index tablicy sredniej dlugosci serii zyskow
	$default['series']['profit']['average']['array'] = array(); //tablica sredniej dlugosci serii zyskow
	$default['series']['profit']['average']['val'] = 0; //wartosc sredniej dlugosci serii zyskow
	$default['jsData'] = '';
	$default['weeklySummary'] = array();
	return $default;
}

function cutToWeek($inputData, $weekNumber) {
	$returnArray = array();
	$lines = explode("\n", $inputData);
	foreach($lines as $e) {
		$el = explode(" ", $e);
		if ($el[1] <= $weekNumber) {
			$returnArray[] = $e;
		}
	}
	$returnString = implode("\n", $returnArray);
	return $returnString;
}

function processRawData($data) {
	$lines = explode("\n", $data['raw']);
	$linesReversed = array_reverse($lines);
	$newInputData = "";
	$newDataDetails = "";
	foreach($linesReversed as $e) {
		$el = explode("	", $e);
		$newDataDetails .= "\n".$el[1].' '.'['.$el[3].'] '.$el[4].' '.$el[7].' SL: '.$el[8].' -> '.calculateResult($el[4], $el[7], $el[8]);
	}
	foreach($linesReversed as $e) {
		$el = explode("	", $e);
		$newInputData .= "\n".$el[0].' '.$el[1].' 1 '.calculateResult($el[4], $el[7], $el[8]);
	}
	$data['list'] .= $newInputData;
	$data['details'] .= $newDataDetails;
	return $data;
}

function formatData($inputData, $messages, $type) {
	if ($_GET['cutTo']) {
		$inputData = cutToWeek($inputData, $_GET['cutTo']);
	}
	$formatedData = prepareData();
	$lines = explode("\n", $inputData);
	foreach($lines as $e) {
		$el = explode(" ", $e);
		if (
			($type == 1)
			|| ($type == 2 && $el[0] == 1)
			|| ($type == 3 && $el[0] == 2)
		) {
			$result = intVal($el[2]) * intVal($el[3]);
			$formatedData['transaction']['all']++;
			$formatedData['weeklySummary'][intVal($el[1])] = array(
				intVal($el[0]),
				intVal($el[2]),
				$formatedData['weeklySummary'][intVal($el[1])][2] + $result
			);
			$formatedData = calculateData(intVal($result), $formatedData);
			if ($result < $formatedData['minMax']['min']) {
				$formatedData['minMax']['min'] = $result;
			}
			if ($result > $formatedData['minMax']['max']) {
				$formatedData['minMax']['max'] = $result;
			}
			$formatedData['cumulated']['Value'] += $result;
			if ($formatedData['cumulated']['Value'] < $formatedData['cumulated']['Min']) {
				$formatedData['cumulated']['Min'] = $formatedData['cumulated']['Value'];
			}
			if ($formatedData['cumulated']['Value'] > $formatedData['cumulated']['Max']) {
				$formatedData['cumulated']['Max'] = $formatedData['cumulated']['Value'];
			}
			$formatedData['jsData'] = $formatedData['jsData']."['".$el[1]."', ".$formatedData['cumulated']['Value']."],";
		}
	}
	$formatedData['transaction']['lossPercent'] = intVal($formatedData['transaction']['loss'] / $formatedData['transaction']['all'] * 100);
	$formatedData['transaction']['profitPercent'] = intVal($formatedData['transaction']['profit'] / $formatedData['transaction']['all'] * 100);
	$formatedData['transaction']['avgLoss'] = intVal($formatedData['transaction']['sumLoss'] / $formatedData['transaction']['loss']);
	$formatedData['transaction']['avgProfit'] = intVal($formatedData['transaction']['sumProfit'] / $formatedData['transaction']['profit']);
	$formatedData['transaction']['drawDown'] = $formatedData['cumulated']['Max'] + abs($formatedData['cumulated']['Min']);
	$formatedData['series']['loss']['average']['val'] = round(array_sum($formatedData['series']['loss']['average']['array']) / count($formatedData['series']['loss']['average']['array']));
	$formatedData['series']['profit']['average']['val'] = round(array_sum($formatedData['series']['profit']['average']['array']) / count($formatedData['series']['profit']['average']['array']));
	/*
	TODO:
	najdłuższa seria stratnych tygodnii
	średnia seria stratnych tygodnii
	najdłuższa seria zyskownych tygodni
	średnia seria stratnych tygodnii
	*/
	$htmlCode = '<div id="chart_div_'.$type.'" style="width: 600px; height: 600px; border: 2px solid black;"></div><br />';
	return prepareJs($type, $formatedData['jsData'], $messages).$htmlCode.prepareDescription($formatedData).parseWeeklySummary($formatedData['weeklySummary']);
}

function weeklyReport($inputData) {
	$lines = explode("\n", $inputData);
	$prevWeekNumber = 0;
	foreach($lines as $e) {
		$el = explode(" ", $e);
		if ($el[0] !== $prevWeekNumber) {
			echo $el[0].'<br />';
		}
		echo $el[1].' '.$el[2].' '.$el[3].' '.$el[4].' '.$el[5].' '.$el[6].' '.$el[7].' '.$el[8];
		echo '<br />';
		$prevWeekNumber = $el[0];
	}
}

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
	</script>
</head>
<body>

	<?php
		$data = processRawData($data);
	?>

	<?php for ($i = 1; $i <= 3; $i++) {
		echo formatData($data['list'], $messages, $i);
	} ?>

	<h1>Weekly report</h1>

	<?php
		weeklyReport($data['details']);
	?>

</body>
</html>
