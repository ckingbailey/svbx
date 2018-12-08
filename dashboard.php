<?php
use SVBX\Report;

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
  join('CDL', 'status.statusID = CDL.status', 'LEFT')->
  get('status', null, ['statusName', 'COUNT(CDL.status) as count']);

$context['data']['severity'] = $link->
  orderBy('severityName', 'ASC')->
  groupBy('severityName')->
  // where('CDL.status', '1')->
  join('CDL', 'severity.severityID = CDL.severity', 'LEFT')->
  get('severity', null, ['severityName', 'COUNT(IF(status = 1, 1, NULL)) as count']);

$context['data']['system'] = $link->
  orderBy('systemName', 'ASC')->
  groupBy('systemName')->
  // where('CDL.status', '3', '<>')->
  join('CDL', 'system.systemID = CDL.groupToResolve', 'LEFT')->
  join('users_enc', 'system.lead = users_enc.userid', 'LEFT')->
  get('system', null, ['systemName', 'COUNT(IF(status = 1, 1, NULL)) as count', 'CONCAT(SUBSTR(firstname, 1, 1), " ", lastname) as lead']);

$context['data']['location'] = $link->  
  orderBy('locationName', 'ASC')->
  groupBy('locationName')->
  // where('CDL.status', '3', '<>')->
  join('CDL', 'location.locationID = CDL.location', 'LEFT')->
  get('location', null, ['locationName', 'COUNT(IF(status = 1, 1, NULL)) as count']);

$statusName = array_column($context['data']['status'], 'statusName');
$context['data']['totalOpen'] = $context['data']['status'][array_search('Open', $statusName)]['count']; // where statusName === 'open'
$context['data']['totalClosed'] = $context['data']['status'][array_search('Closed', $statusName)]['count']; // where statusName === 'closed'

$severityName = array_column($context['data']['severity'], 'severityName');
$context['data']['totalBlocker'] = $context['data']['severity'][array_search('Blocker', $severityName)]['count'];
$context['data']['totalCrit'] = $context['data']['severity'][array_search('Critical', $severityName)]['count'];
$context['data']['totalMajor'] = $context['data']['severity'][array_search('Major', $severityName)]['count'];
$context['data']['totalMinor'] = $context['data']['severity'][array_search('Minor', $severityName)]['count'];

// instantiate Twig
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, [
    'debug' => getenv('PHP_ENV') === 'dev'
]);
if (getenv('PHP_ENV') === 'dev') $twig->addExtension(new Twig_Extension_Debug());

// instantiate report object
$sit3delta = Report::delta('SIT3');
// $weeklySIT3delta = new WeeklyDelta('SIT3');
$context['data']['weeklyReport'] = $sit3delta->get();

$twig->display('dashboard.html.twig', $context);
