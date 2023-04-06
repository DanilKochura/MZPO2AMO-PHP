
<?php

error_reporting(0);

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Filters\BaseRangeFilter;
use AmoCRM\Filters\LeadsFilter;
require_once '../model/MzpoAmo.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Pipelines.php';
file_put_contents(__DIR__.'/0.txt', print_r($_REQUEST, 1), FILE_APPEND);
$subdomain = 'mzpoeducationsale'; //Поддомен нужного аккаунта
$clientSecret = 'KAHuESf38NuVHQ6TxpzaN5eWnbe8TutYO5eo9olYoXAe7xUoYXlHwuYlh4WnFg3R';
$clientId = 'a4fbd30b-b5ae-4ebd-91b1-427f58f0d709';
$redirectUri = 'https://mzpo-s.ru/amo/';
///** Соберем данные для запроса */
//
$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
////var_dump($apiClient); exit;
$accessToken = getToken($subdomain);
$apiClient->setAccessToken($accessToken)
	->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
	->onAccessTokenRefresh(
		function (\League\OAuth2\Client\Token\AccessTokenInterface $accessToken, $baseDomain) {
			saveToken(
				[
					'accessToken' => $accessToken->getToken(),
					'refreshToken' => $accessToken->getRefreshToken(),
					'expires' => $accessToken->getExpires(),
					'baseDomain' => $baseDomain,
				]
			);
		});

$users = [
	'Гребенникова' => 2375107,
	'Белоусова' => 2375143,
	'Федорова' => 3813670,
	'Лукьянова' => 6102562,
	'Матюк' => 6158035,
	'Исмайлова' => 6929800,
	'Сиренко'=>7771945,
	'Кубрина'=>8628637,
	'Ревина'=>8688502,


	];

$data = [];
$avg = [];
$date =  $_REQUEST['date_to'] != 'false' ?  $_REQUEST['date_to'] : date('Y-m-d');
$date1 =  $_REQUEST['date_from'] != 'false' ? $_REQUEST['date_from'] : date('Y-m-d', strtotime(date("Y").'-'.date("m").'-01 00:00:00'));


//dd($data);

if (file_exists('avg_ret_'.$date1.'-'.$date.'.json')) {
	$one_day = 2 * 60 * 60; //часы * мин * сек = 86400 c
	$file_created = filemtime('avg_ret_'.$date1.'-'.$date.'.json');
	if (time() - $file_created > $one_day) { // || true

		foreach ($users as $name=>$id)
		{
			$i = 1;
			$lf = new LeadsFilter();
			$lf->setLimit(200);
			$lf->setPage($i);
			$lf->setPipelineIds([\MzpoAmo\Pipelines::RETAIL])->setStatuses([
				[
					'status_id' => 142,
					'pipeline_id' => \MzpoAmo\Pipelines::RETAIL
				]
			]);
			$lf->setCreatedAt((new BaseRangeFilter())
				->setFrom(strtotime($date1))
				->setTo(strtotime($date)))->setResponsibleUserId($id);
			$price = 0;

			$co = 0;
			do{
				$lf->setPage($i++);
				try {
					$tt = $apiClient->leads()->get($lf);
					foreach ($tt as $t)
					{
						$price+=$t->getPrice();
					}
					$amo = $tt->count();
				} catch (Exception $e)
				{
					$amo = 0;
				}


				$co+= $amo;
			}while($amo == 200);
			$avg[] = $co != 0 ? round($price/$co, 2) : 0;
			$data[] = [
				'name' => $name,
				'avg' => round($price/$co, 2) ?: 0,
				'co' => $co
			];
		}
//		file_put_contents(__DIR__.'/avg_ret.json', json_encode($avg));

	}
	else
	{
		$avg = json_decode(file_get_contents(__DIR__.'/avg_ret_'.$date1.'-'.$date.'.json'));
	}
} else
{
	foreach ($users as $name=>$id)
	{
		$i = 1;
		$lf = new LeadsFilter();
		$lf->setLimit(200);
		$lf->setPage($i);
		$lf->setPipelineIds([\MzpoAmo\Pipelines::RETAIL])->setStatuses([
			[
				'status_id' => 142,
				'pipeline_id' => \MzpoAmo\Pipelines::RETAIL
			]
		]);



		$lf->setCreatedAt((new BaseRangeFilter())
			->setFrom(strtotime($date1))
			->setTo(strtotime($date)))->setResponsibleUserId($id);
		$price = 0;

		$co = 0;
		do{
			$lf->setPage($i++);
			try {
				$tt = $apiClient->leads()->get($lf);
				foreach ($tt as $t)
				{
					$price+=$t->getPrice();
				}
				$amo = $tt->count();
			} catch (Exception $e)
			{
				$amo = 0;
			}


			$co+= $amo;
		}while($amo == 200);
		$avg[] = $co != 0 ? round($price/$co, 2) : 0;
		$data[] = [
			'name' => $name,
			'avg' => round($price/$co, 2) ?: 0,
			'co' => $co
		];
	}

	file_put_contents(__DIR__.'/avg_ret_'.$date1.'-'.$date.'.json', json_encode($avg));

}

?>
<!DOCTYPE html>
<html>
<head>
	<title>amoCRM Test Dashboard Widget</title>
	<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Open+Sans:400,300&subset=latin,cyrillic-ext">
	<link rel="stylesheet" type="text/css" href='//fonts.googleapis.com/css?family=PT+Sans:400,700&subset=latin,cyrillic-ext'>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>
	<style type="text/css">
		* {
			-webkit-box-sizing: border-box;
			-moz-box-sizing: border-box;
			box-sizing: border-box;
			text-rendering: geometricPrecision;
			-webkit-font-smoothing: antialiased;
		}
		html, body {
			padding: 0;
			margin: 0;
			font-family: 'PT Sans';
			color: #2E3640;
		}
		a, a:active, a:hover, a:visited {
			color: #2E3640;
			text-decoration: none;
		}
		.cell-wrapper {
			padding: 20px 15px 15px 15px;
		}
		.cell-wrapper__caption {
			font-size: 15px;
			font-weight: bold;
		}
		.cell-wrapper__data {
			height: 80px;
		}
		.cell-wrapper__data__num {
			font-size: 40px;
			line-height: 68px;
			font-family: 'Open Sans', sans-serif;
		}
		.cell-wrapper__bottom {
			margin-top: 15px;
			font-size: 40px;
			font-family: 'Open Sans', sans-serif;
		}
		.cell-wrapper__bottom.red,
		.cell-wrapper__bottom.red a {
			color: #fe6e6e;
		}
		.cell-wrapper__bottom.green,
		.cell-wrapper__bottom.green a {
			color: #41cfc4;
		}
		.cell-wrapper__bottom:after {
			content: "";
			display: inline-block;
			width: 20px;
			height: 21px;
			vertical-align: middle;
			margin-top: -4px;
			margin-left: 6px;
		}
		.cell-wrapper__bottom.green:after {
			background: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiBwcmVzZXJ2ZUFzcGVjdFJhdGlvPSJ4TWlkWU1pZCIgd2lkdGg9IjgiIGhlaWdodD0iMTMiIHZpZXdCb3g9IjAgMCA4IDEzIj4KICA8ZGVmcz4KICAgIDxzdHlsZT4KCiAgICAgIC5jbHMtMiB7CiAgICAgICAgZmlsbDogIzM4Y2RjMTsKICAgICAgfQogICAgPC9zdHlsZT4KICA8L2RlZnM+CiAgPHBhdGggZD0iTTguNTAyLDQuMjc5IEM4LjUwMiw0LjI3OSA3LjYwNiw1LjIwNyA3LjYwNiw1LjIwNyBDNy42MDYsNS4yMDcgNS4wMDcsMi41MTYgNS4wMDcsMi41MTYgQzUuMDA3LDIuNTE2IDUuMDA3LDEzLjAyNiA1LjAwNywxMy4wMjYgQzUuMDA3LDEzLjAyNiAzLjQ5MywxMy4wMjYgMy40OTMsMTMuMDI2IEMzLjQ5MywxMy4wMjYgMy40OTMsMi41MTYgMy40OTMsMi41MTYgQzMuNDkzLDIuNTE2IDAuODk0LDUuMjA3IDAuODk0LDUuMjA3IEMwLjg5NCw1LjIwNyAtMC4wMDIsNC4yNzkgLTAuMDAyLDQuMjc5IEMtMC4wMDIsNC4yNzkgNC4xMTksMC4wMTIgNC4xMTksMC4wMTIgQzQuMTE5LDAuMDEyIDQuMjUwLDAuMTQ4IDQuMjUwLDAuMTQ4IEM0LjI1MCwwLjE0OCA0LjM4MSwwLjAxMiA0LjM4MSwwLjAxMiBDNC4zODEsMC4wMTIgOC41MDIsNC4yNzkgOC41MDIsNC4yNzkgWiIgaWQ9InBhdGgtMSIgY2xhc3M9ImNscy0yIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz4KPC9zdmc+Cg==) no-repeat  ;
			background-position-y: center;
			background-size: contain;
		}
		.cell-wrapper__bottom.red:after {
			background: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiBwcmVzZXJ2ZUFzcGVjdFJhdGlvPSJ4TWlkWU1pZCIgd2lkdGg9IjkiIGhlaWdodD0iMTMiIHZpZXdCb3g9IjAgMCA5IDEzIj4KICA8ZGVmcz4KICAgIDxzdHlsZT4KCiAgICAgIC5jbHMtMiB7CiAgICAgICAgZmlsbDogI2ZlNmU2ZTsKICAgICAgfQogICAgPC9zdHlsZT4KICA8L2RlZnM+CiAgPHBhdGggZD0iTTguNTAyLDguNzM1IEM4LjUwMiw4LjczNSA0LjM4MSwxMy4wMDIgNC4zODEsMTMuMDAyIEM0LjM4MSwxMy4wMDIgNC4yNTAsMTIuODY2IDQuMjUwLDEyLjg2NiBDNC4yNTAsMTIuODY2IDQuMTE5LDEzLjAwMiA0LjExOSwxMy4wMDIgQzQuMTE5LDEzLjAwMiAtMC4wMDIsOC43MzUgLTAuMDAyLDguNzM1IEMtMC4wMDIsOC43MzUgMC44OTQsNy44MDcgMC44OTQsNy44MDcgQzAuODk0LDcuODA3IDMuNDkzLDEwLjQ5OCAzLjQ5MywxMC40OTggQzMuNDkzLDEwLjQ5OCAzLjQ5MywtMC4wMTIgMy40OTMsLTAuMDEyIEMzLjQ5MywtMC4wMTIgNS4wMDcsLTAuMDEyIDUuMDA3LC0wLjAxMiBDNS4wMDcsLTAuMDEyIDUuMDA3LDEwLjQ5OCA1LjAwNywxMC40OTggQzUuMDA3LDEwLjQ5OCA3LjYwNiw3LjgwNyA3LjYwNiw3LjgwNyBDNy42MDYsNy44MDcgOC41MDIsOC43MzUgOC41MDIsOC43MzUgWiIgaWQ9InBhdGgtMSIgY2xhc3M9ImNscy0yIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz4KPC9zdmc+Cg==) no-repeat;
			background-position-y: center;
			background-size: contain;
		}

		.cell-wrapper__caption,
		.cell-wrapper__data,
		.cell-wrapper__bottom {
			position: relative;
			white-space: nowrap;
			overflow: hidden;
			width: 100%;
		}
		.cell-wrapper__caption:before,
		.cell-wrapper__data:before,
		.cell-wrapper__bottom:before {
			content: "";
			position: absolute;
			top: 0;
			bottom: 0;
			right: -1px;
			width: 20px;


		}

		.text-22
		{
			font-size: 22px;
		}
		.text-center
		{
			text-align: center;
		}

	</style>
</head>
<body>
<canvas id="densityChart" width="600" height="400" style="width: 600px; height: 600px"></canvas>
<script>

	Chart.register(ChartDataLabels);
	const ctx = document.getElementById('densityChart');

	const labels = ["<?=implode('","',array_keys($users))?>"]
	const data = {
		labels: labels,
		datasets: [{
			axis: 'y',
			label: 'My First Dataset',
			data: [<?=implode(',', $avg)?>],
			fill: false,
			backgroundColor: [
				'rgba(255, 99, 132, 0.2)',
				'rgba(255, 159, 64, 0.2)',
				'rgba(255, 205, 86, 0.2)',
				'rgba(75, 192, 192, 0.2)',
				'rgba(54, 162, 235, 0.2)',
				'rgba(153, 102, 255, 0.2)',
				'rgba(201, 203, 207, 0.2)'
			],
			borderColor: [
				'rgb(255, 99, 132)',
				'rgb(255, 159, 64)',
				'rgb(255, 205, 86)',
				'rgb(75, 192, 192)',
				'rgb(54, 162, 235)',
				'rgb(153, 102, 255)',
				'rgb(201, 203, 207)'
			],
			borderWidth: 1
		}]
	};

	const config = {
		type: 'bar',
		data,
		plugins: [ChartDataLabels],
		options: {
			plugins: {
				legend: {
					display: false
				},
				tooltips: {
					enabled: false
				},
				datalabels: {
					formatter: Math.round,
					font: {
						weight: 'bold',
						size: 14
					}
				}
			},
			indexAxis: 'y',

			hover: {
				animationDuration: 0
			},
			animation: {
				duration: 500,
				easing: "easeOutQuart",
				onComplete: function () {
					var ctx = this.chart.ctx;
					ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontFamily, 'normal', Chart.defaults.global.defaultFontFamily);
					ctx.textAlign = 'center';
					ctx.textBaseline = 'bottom';
					this.data.datasets.forEach(function (dataset) {
						for (var i = 0; i < dataset.data.length; i++) {
							var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model,
								scale_max = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._yScale.maxHeight;
							ctx.fillStyle = '#444';
							var y_pos = model.y - 5;
							// Make sure data value does not get overflown and hidden
							// when the bar's value is too close to max value of scale
							// Note: The y value is reverse, it counts from top down
							if ((scale_max - model.y) / scale_max >= 0.93)
								y_pos = model.y + 20;
							ctx.fillText(dataset.data[i], model.x, y_pos);
						}
					});
				}
			}
		}
	};
	new Chart(ctx, config);

</script>

<!---->
</body>
</html>
