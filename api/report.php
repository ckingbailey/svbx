<?php
use SVBX\Report;
use SVBX\Export;

$basedir = __DIR__ . '/..';
require "$basedir/vendor/autoload.php";
require "$basedir/session.php";

// query params:
//  milestone
//  TODO: date
//  TODO: system
//  type
//  format => if Export class lacks $format method, return bad http response 
if (!method_exists('SVBX\Export', $_GET['format'])) {
    header('Malformed request', true, 400);
    error_log(__FILE__ . '(' . __LINE__ . ')' . ' Invalid format requested from Report API');
    exit;
}

if (!method_exists('SVBX\Report', $_GET['type'])) {
    header('Malformed request', true, 400);
    error_log(__FILE__ . '(' . __LINE__ . ')' . ' Invalid report type requested from Report API');
    exit;
}

try {
    $format = $_GET['format'];
    $reportType = $_GET['type'];

    $report = Report::delta(
        $_GET['field'],
        $_GET['to'],
        $_GET['from'],
        $_GET['milestone']
    )->get();
    $headings = array_keys($report[0]);
    array_unshift($report, $headings);

    echo Export::$format($report);
} catch (\UnexpectedValueException $e) {
    error_log($e);
    header('Bad query param', true, 400);
} catch (\Exception $e) {
    error_log($e);
    header('Internal server error', true, 500);
} catch (\Error $e) {
    error_log($e);
    header('Internal server error', true, 500);
} finally {
    exit;
}