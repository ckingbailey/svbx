<?php
use Carbon\Carbon;
use Carbon\CarbonImmutable;

require 'vendor/autoload.php';
require 'config.php';

$today = new CarbonImmutable();
$thisDayLastWeek = new CarbonImmutable($today->subWeek());

$caseStr = "COUNT(CASE WHEN dateClosed <= CAST('%s' AS DATE) THEN defID ELSE NULL END) AS %s";
$selectOpenThisWeek = sprintf($caseStr, $today, 'openThisWeek');
$selectOpenLastWeek = sprintf($caseStr, $thisDayLastWeek, 'openLastWeek');
$queryStr = "SELECT s.systemName AS system, $selectOpenThisWeek, $selectOpenLastWeek"
    . ' FROM CDL c JOIN system s ON c.systemAffected = s.systemID WHERE dateClosed IS NOT NULL GROUP BY c.systemAffected';
$link = new MySqliDB(DB_HOST, DB_USER, DB_PWD, DB_NAME);

$result = $link->rawQuery($queryStr);

// $delta = $result['openThisWeek'] - $result['openLastWeek'];

// instantiate Twig
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, [
    'debug' => (PHP_ENV === 'dev' ? true : false)
]);
if (PHP_ENV === 'dev') $twig->addExtension(new Twig_Extension_Debug());

echo $today->getTimezone()->getName() . ': ' . $today->getTimezone()->getOffset($today) . PHP_EOL;
echo $link->getLastQuery() . PHP_EOL;
print_r($result) . PHP_EOL;
// echo $delta . PHP_EOL;