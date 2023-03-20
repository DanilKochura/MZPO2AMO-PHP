
<?php
use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Filters\BaseRangeFilter;
use AmoCRM\Filters\LeadsFilter;
require_once '../model/MzpoAmo.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Pipelines.php';
$subdomain = 'mzpoeducationsale'; //Поддомен нужного аккаунта
$clientSecret = 'KAHuESf38NuVHQ6TxpzaN5eWnbe8TutYO5eo9olYoXAe7xUoYXlHwuYlh4WnFg3R';
$clientId = 'a4fbd30b-b5ae-4ebd-91b1-427f58f0d709';
$redirectUri = 'https://mzpo-s.ru/amo/';
///** Соберем данные для запроса */
//
$subdomain = 'mzpoeducation'; //Поддомен нужного аккаунта
$clientSecret = 'PpfKPVKEoND3MBHh7fjLSQIcYBNaZetCmVkUXU9VMLI02ynoGJl1SJ4e4YStYust';
$clientId = 'e48269b8-aca1-4ebd-8809-420d71f57522';
$redirectUri = 'https://mzpo-s.ru/amo/mainhook.php';
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

$i = 1;
$lf = new LeadsFilter();
$lf->setLimit(200);
$lf->setPage($i);
$lf->setPipelineIds([\MzpoAmo\Pipelines::NEW])->setStatuses([
	[
		'status_id' => 142,
		'pipeline_id' => \MzpoAmo\Pipelines::NEW
	]
]);
$date = date('Y-m-d H-i-s');
$date1 = date('Y-m-d', strtotime(date("Y").'-'.date("m").'-01 00:00:00'));
$lf->setCreatedAt((new BaseRangeFilter())
	->setFrom(strtotime($date1))
	->setTo(strtotime($date)));
$price = 0;

$co = 0;
do{
	$lf->setPage($i++);
	$tt = $apiClient->leads()->get($lf);
	foreach ($tt as $t)
	{
		$price+=$t->getPrice();
	}
	$amo = $tt->count();

	$co+= $amo;
}while($amo == 200);
$avg =  $price/$co;


?>
<!DOCTYPE html>
<html>
<head>
	<title>amoCRM Test Dashboard Widget</title>
	<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Open+Sans:400,300&subset=latin,cyrillic-ext">
	<link rel="stylesheet" type="text/css" href='//fonts.googleapis.com/css?family=PT+Sans:400,700&subset=latin,cyrillic-ext'>

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
<div class="cell-wrapper text-center">
	<!-- Название виджета -->
	<div class="cell-wrapper__caption text-22">Средний чек</div>

	<!-- Главная цифра в виджете -->
	<div class="cell-wrapper__data">
		<div class="cell-wrapper__bottom <?=$avg > 13000 ? 'red' : 'green'?>" title="500"><?=round($avg, 1)?> руб</div>
	</div>
	<!-- Футер виджета, например, сравнение с предыдущим периодом -->
	<!-- Используйте классы .green и .red для того, чтобы показать успешный период или нет -->
	<div class="" title="80%">
		<a href=""><?=$co?> сделок</a>
	</div>
</div>
</body>
</html>
