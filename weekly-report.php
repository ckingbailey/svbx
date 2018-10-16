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

$result = $link->rawQueryOne($queryStr);

$delta = $result['openThisWeek'] - $result['openLastWeek'];

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, [
    'debug' => (PHP_ENV === 'dev' ? true : false)
]);
if (PHP_ENV === 'dev') $twig->addExtension(new Twig_Extension_Debug());
