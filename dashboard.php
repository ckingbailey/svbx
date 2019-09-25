<?php
use SVBX\Report;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

require 'vendor/autoload.php';
require 'WeeklyDelta.php';
require 'session.php';

// init context with some defaults
$context = [
  'session' => $_SESSION,
  'title' => 'Home',
  'pageHeading' => 'Database Information',
  'data' => [
    'selected' => [
      'field' => 'severity',
      'from' => null,
      'to' => null,
      'milestone' => null
    ]
  ]
];

if (!empty($_GET)) {
  $context['data']['selected']['field'] = filter_var($_GET['field'], FILTER_SANITIZE_STRING);
  $context['data']['selected']['from'] = filter_var($_GET['from'], FILTER_SANITIZE_STRING);
  $context['data']['selected']['to'] = filter_var($_GET['to'], FILTER_SANITIZE_STRING);
  $context['data']['selected']['milestone'] = filter_var($_GET['milestone'], FILTER_SANITIZE_STRING);
}

// defaults 
$context['data']['selected']['to'] = new CarbonImmutable($context['data']['selected']['to']);
$context['data']['selected']['from'] = ($context['data']['selected']['from']
  ? new CarbonImmutable($context['data']['selected']['from'])
  : $context['data']['selected']['to']->subWeek())->toDateString();
$context['data']['selected']['to'] = $context['data']['selected']['to']->toDateString();

$context['data']['selected']['milestone'] = intval($context['data']['selected']['milestone'])
  ?: null;

$link = new MysqliDb(DB_CREDENTIALS);

$context['data']['status'] = $link->
  orderBy('statusID', 'ASC')->
  groupBy('statusName')->
  where('CDL.status', '3', '<>')->
  join('CDL', 'status.statusID = CDL.status', 'LEFT')->
  get('status', null, ['statusName label', 'COUNT(CDL.status) count']);

$context['data']['severity'] = $link->
  orderBy('severityName', 'ASC')->
  groupBy('severityName')->
  join('CDL', 'severity.severityID = CDL.severity', 'LEFT')->
  get('severity', null, ['severityName label', 'COUNT(IF(status = 1, 1, NULL)) count']);

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

$statusOrder = [
  'Open',
  'VTA_PPR_PNDG',
  'VTA_CLOSED',
  'Closed'
];

$severityOrder = [
  'Blocker',
  'PRO_BLOCKER',
  'RSD_BLOCKER',
  'Critical',
  'PRO_CRITICAL',
  'RSD_CRITICAL',
  'Major',
  'PRO_MAJOR',
  'RSD_MAJOR',
  'Minor'
];

// TEST DATA
// $context['data']['status'] = [
//   [
//     'label' => 'Open',
//     'count' => 874
//   ],
//   [
//     'label' => 'VTA_PPR_PNDG',
//     'count' => 263
//   ],
//   [
//     'label' => 'VTA_CLOSED',
//     'count' => 94
//   ],
//   [
//     'label' => 'Closed',
//     'count' => 2134
//   ]
// ];

// $context['data']['severity'] = [
//   [
//     'label' => 'Blocker',
//     'count' => 35
//   ],
//   [
//     'label' => 'Critical',
//     'count' => 135
//   ],
//   [
//     'label' => 'Major',
//     'count' => 362
//   ],
//   [
//     'label' => 'Minor',
//     'count' => 342
//   ],
//   [
//     'label' => 'PRO_BLOCKER',
//     'count' => 25
//   ],
//   [
//     'label' => 'PRO_CRITICAL',
//     'count' => 75
//   ],
//   [
//     'label' => 'PRO_MAJOR',
//     'count' => 45
//   ],
//   [
//     'label' => 'RSD_BLOCKER',
//     'count' => 123
//   ],
//   [
//     'label' => 'RSD_CRITICAL',
//     'count' => 67
//   ],
//   [
//     'label' => 'RSD_MAJOR',
//     'count' => 108
//   ]
// ];
// END TEST DATA

usort($context['data']['status'], function($a, $b) use ($statusOrder) {
  return array_search($a['label'], $statusOrder) - array_search($b['label'], $statusOrder);
});

usort($context['data']['severity'], function($a, $b) use ($severityOrder) {
  return array_search($a['label'], $severityOrder) - array_search($b['label'], $severityOrder);
});

// instantiate Twig
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, [
    'debug' => getenv('PHP_ENV') === 'dev'
]);
if (getenv('PHP_ENV') === 'dev') $twig->addExtension(new Twig_Extension_Debug());

$context['data']['milestones'] = array_reduce(
  $link->get('requiredBy', null, [ 'reqByID as id', 'requiredBy as name' ]),
  function ($map, $row) {
    $map[$row['id']] = $row['name'];
    return $map;
  },
  []
);
$link->disconnect();

// instantiate report object
try {
  $report = Report::delta(
    $context['data']['selected']['field'],
    $context['data']['selected']['from'],
    $context['data']['selected']['to'],
    $context['data']['selected']['milestone']
  );
  $context['data']['deltaReport'] = $report->get();
} catch (Exception | Error $e) {
  error_log($e);
  $context['data']['deltaReport']['error'] = $e->getMessage();
}

$twig->display('dashboard.html.twig', $context);
