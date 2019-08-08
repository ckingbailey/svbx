<?php
use SVBX\Report;

require 'vendor/autoload.php';
require 'WeeklyDelta.php';
require 'session.php';

if (!empty($_GET)) {
  $field = filter_var($_GET['field'], FILTER_SANITIZE_STRING);
  $from = filter_var($_GET['from'], FILTER_SANITIZE_STRING);
  $to = filter_var($_GET['to'], FILTER_SANITIZE_STRING);
  $milestone = filter_var($_GET['milestone'], FILTER_SANITIZE_STRING);

  $from = $from
    ? DateTime::createFromFormat('Y-m-d', $from)->format('Y-m-d')
    : null;
  $to = $to
    ? DateTime::createFromFormat('Y-m-d', $to)->format('Y-m-d')
    : null;
  $milestone = intval($milestone) ?: null;
}

$context = [
  'session' => $_SESSION,
  'title' => 'Home',
  'pageHeading' => 'Database Information'
];

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
  get('system', null, ['systemName', 'COUNT(IF(status = 1, 1, NULL)) as count']);

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

$context['data']['milestones'] = $link->get('requiredBy', null, [ 'reqByID as id', 'requiredBy as name' ]);

// instantiate report object
if (!empty($field))
  $report = Report::delta($field, $from, $to, $milestone);
else $report = Report::delta();
$context['data']['deltaReport'] = $report->get();

$link->disconnect();
$twig->display('dashboard.html.twig', $context);
