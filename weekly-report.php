<?php
use Carbon\Carbon;
use Carbon\CarbonImmutable;

require 'vendor/autoload.php';
require 'config.php';

$today = new CarbonImmutable();
$thisDayLastWeek = new CarbonImmutable($today->subWeek());

$caseStr = "COUNT(CASE WHEN dateCreated BETWEEN CAST('%s' AS DATE) AND CAST('%s' AS DATE) THEN defID ELSE NULL END) AS %s";
$queryStr = 'SELECT ' . sprintf($caseStr, $thisDayLastWeek->addDay() , $today, 'openThisWeek') . ', '
    . sprintf($caseStr, $thisDayLastWeek->subWeek()->addDay(), $thisDayLastWeek, 'openLastWeek') . ' FROM CDL';
$link = new MySqliDB(DB_HOST, DB_USER, DB_PWD, DB_NAME);

// echo $thisDayLastWeek->format('Y:m:d H:i') . PHP_EOL;
$result = $link->rawQueryOne(sprintf($queryStr, 'Last', $thisDayLastWeek->subWeek()->addDay(), $thisDayLastWeek));
echo $link->getLastQuery() . PHP_EOL;
print_r($result) . PHP_EOL;