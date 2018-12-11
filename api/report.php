<?php
use SVBX\Report;
use SVBX\Export;

$basedir = __DIR__ . '/..';
require "$basedir/vendor/autoload.php";
require "$basedir/session.php";

/* query params:
**  milestone
**  date
**  system
**  type
**  format => if Export class lacks $format method, return bad http response
*/
// TODO: clean data
// TODO: validate query params
// TODO: check `format` param
if (!method_exists('SVBX\Export', $_GET['format'])) {
    header('Malformed request', true, 400);
    exit;
}

$format = $_GET['format'];
$reportType = 'delta';

echo Export::$format(
    Report::delta($_GET['milestone'])->get()
);

exit;