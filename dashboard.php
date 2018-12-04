<?php
require 'vendor/autoload.php';
// include('SQLFunctions.php');
require 'WeeklyDelta.php';
require 'session.php';

$context = [
  'session' => $_SESSION,
  'title' => 'Home',
  'pageHeading' => 'Database Information'
];

// $title = "SVBX - Home";
//$table = pages;

$link = new MysqliDb(DB_CREDENTIALS);

$context['data']['status'] = $link->
  orderBy('statusID', 'ASC')->
  groupBy('statusName')->
  where('CDL.status', '3', '<>')->
  join('status s', 'CDL.status = s.statusID', 'LEFT')->
  get('CDL', null, ['statusName', 'COUNT(CDL.status) as count']);

$context['data']['severity'] = $link->
  orderBy('severityName', 'ASC')->
  groupBy('severityName')->
  join('severity s', 'CDL.severity = s.severityID', 'LEFT')->
  get('CDL', null, ['severityName', 'COUNT(CDL.severity) as count']);

$context['data']['system'] = $link->
  orderBy('systemName', 'ASC')->
  groupBy('systemName')->
  join('system s', 'CDL.groupToResolve = s.systemID', 'LEFT')->
  join('users_enc', 's.lead = users_enc.userid', 'LEFT')->
  get('CDL', null, ['systemName', 'COUNT(CDL.groupToResolve) as count', 'CONCAT(SUBSTR(firstname, 1, 1), " ", lastname) as lead']);

$context['data']['location'] = $link->
  orderBy('locationName', 'ASC')->
  groupBy('locationName')->
  join('location l', 'CDL.location = l.locationID', 'LEFT')->
  get('CDL', null, ['locationName', 'COUNT(CDL.location) as count']);

// echo '<pre>' . print_r($context['data'], true) . '</pre>';

// $sqlSys = "SELECT COUNT(*) FROM System"; //Systems Count
// $sqlStat = "SELECT COUNT(*) FROM Status"; //Status Counts
// $sqlSev = "SELECT COUNT(*) FROM Severity"; //Severity Counts
// $sqlLoc = "SELECT COUNT(*) FROM Location"; //Location Counts
// $sqlET = "SELECT COUNT(*) FROM CDL WHERE Status=2"; //Status Closed Counts

// vars to pass to JS scripts
// $statusOpen = 0;
// $statusClosed = 0;
// $blockSev = 0;
// $critSev = 0;
// $majSev = 0;
// $minSev = 0;

// foreach($cards as $card) {
//   $tableStr = 'SELECT COUNT(*) FROM ' . $card[0];
//   $res = $link->query($tableStr);
//   $count = $res->fetch_row()[0];
//   $res->close();
//   $res = $link->query($queries[$card[0]]);
//   writeDashCard($count, $res, $card);
//   $res->close();
// }

// instantiate Twig
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, [
    'debug' => getenv('PHP_ENV') === 'dev'
]);
if (getenv('PHP_ENV') === 'dev') $twig->addExtension(new Twig_Extension_Debug());

// instantiate report object
$weeklySIT3delta = new WeeklyDelta('SIT3');
$context['data']['weeklyReport'] = $weeklySIT3delta->getData();

$twig->display('dashboard.html.twig', $context);
