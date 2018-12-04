<?php
require 'vendor/autoload.php';
include('SQLFunctions.php');
require 'WeeklyDelta.php';
require 'session.php';

$context = [
  'title' => 'Home',
  'pageHeading' => 'Database Information'
];

// $title = "SVBX - Home";
//$table = pages;

$Status = "SELECT S.Status, COUNT(C.Status) FROM CDL C LEFT JOIN Status S ON C.Status=S.StatusID GROUP BY Status ORDER BY StatusID";
$System = "SELECT S.System, COUNT(C.GroupToResolve) FROM CDL C LEFT JOIN System S ON C.GroupToResolve=S.SystemID GROUP BY System ORDER BY S.System"; //Count Actions by System
$Sev = "SELECT S.SeverityName, COUNT(C.Severity) FROM CDL C LEFT JOIN Severity S ON C.Severity=S.SeverityID WHERE C.Status=1 GROUP BY Severity ORDER BY S.SeverityName";
$location = "SELECT L.LocationName, COUNT(C.Location) FROM CDL C LEFT JOIN Location L ON L.LocationID=C.Location GROUP BY Location  ORDER BY L.LocationName";
$Comp = "SELECT CompName FROM Comp ORDER BY CompName";
$sqlSys = "SELECT COUNT(*) FROM System"; //Systems Count
$sqlStat = "SELECT COUNT(*) FROM Status"; //Status Counts
$sqlSev = "SELECT COUNT(*) FROM Severity"; //Severity Counts
$sqlLoc = "SELECT COUNT(*) FROM Location"; //Location Counts
$sqlET = "SELECT COUNT(*) FROM CDL WHERE Status=2"; //Status Closed Counts

// vars to pass to JS scripts
$statusOpen = 0;
$statusClosed = 0;
$blockSev = 0;
$critSev = 0;
$majSev = 0;
$minSev = 0;

// array of tables to render as cards
// each item MUST include TableName, PluralString, ItemString, SqlString
// if any extra content is needed, add it to index after required items
$cards = [
  ['status', 'Statuses', 'Items',
    "<div class='data-display'><div id='open-closed-graph' class='chart-container'></div><p id='open-closed-legend' class='flex-column'></p></div>"],
  ['severity', 'Severities', 'Open Items', '<div class="data-display"><div id="severity-graph" class="chart-container"></div><p id="open-closed-legend" class="legend"></p></div>'],
  ['system', 'Systems', 'Actions'],
  ['location', 'Locations', 'Open Items']
];
$queries = [
  'status' => 'SELECT S.StatusName, COUNT(C.Status) FROM CDL C LEFT JOIN status S ON C.Status=S.StatusID GROUP BY StatusName ORDER BY StatusID',
  'severity' => 'SELECT S.SeverityName, COUNT(C.Severity) FROM CDL C LEFT JOIN severity S ON C.Severity=S.SeverityID WHERE C.Status=1 GROUP BY SeverityName ORDER BY S.SeverityName',
  'system' => 'SELECT S.SystemName, COUNT(C.Status) FROM CDL C LEFT JOIN system S ON C.GroupToResolve=S.SystemID WHERE C.Status=1 GROUP BY SystemName ORDER BY S.SystemName',
  'location' => 'SELECT L.LocationName, COUNT(C.Status) FROM CDL C LEFT JOIN location L ON L.LocationID=C.Location WHERE Status = 1 GROUP BY LocationName ORDER BY L.LocationName'
];

function writeDashCard($count, &$res, $card) {
  global $statusOpen, $statusClosed, $blockSev, $critSev, $majSev, $minSev;
  echo "
    <div class='card dash-card'>
      <header class='card-header'>
        <h4>{$card[1]}</h4>
      </header>
      <div class='card-body grey-bg'>
        <ul class='dash-list'>
          <li class='bg-secondary text-white dash-list-heading'>
            <span class='dash-list-left dash-list-name'>{$card[0]}</span>
            <span class='dash-list-right dash-list-count'>{$card[2]}</span>
          </li>";
  if ($count && $res) {
    while ($row = $res->fetch_row()) {
      if ($row[0] == 'Open') $statusOpen = $row[1];
      elseif ($row[0] == 'Closed') $statusClosed = $row[1];
      elseif ($row[0] == 'Blocker') $blockSev = $row[1];
      elseif ($row[0] == 'Critical') $critSev = $row[1];
      elseif ($row[0] == 'Major') $majSev = $row[1];
      elseif ($row[0] == 'Minor') $minSev = $row[1];
      echo "
          <li class='dash-list-item'>
            <span class='dash-list-left'>{$row[0]}</span>
            <span class='dash-list-right'>{$row[1]}</span>
          </li>
      ";
    }
    echo "</ul>";
    if (count($card) > 3) {
      echo "{$card[3]}</div>";
    } else echo "</div>";
    echo "
        <footer class='card-footer'>
          <a href='Display{$card[1]}.php' class='btn btn-lg btn-outline btn-a'>Number of {$card[1]} {$count}</a>
        </footer>
    ";
  } else echo "</ul><p class='empty-qry-msg'>0 items returned from database</p>";
  echo "</div>";
}

include('filestart.php'); //Provides all HTML starting code
$link = f_sqlConnect();
?>
<header class="container page-header">
  <h1 class="page-title">Database Information</h1>
</header>
<main role="main" class="container main-content dashboard">
<?php
foreach($cards as $card) {
  $tableStr = 'SELECT COUNT(*) FROM ' . $card[0];
  $res = $link->query($tableStr);
  $count = $res->fetch_row()[0];
  $res->close();
  $res = $link->query($queries[$card[0]]);
  writeDashCard($count, $res, $card);
  $res->close();
}

if (!empty($_SESSION['userID'])) {
  // instantiate Twig
  $loader = new Twig_Loader_Filesystem('templates');
  $twig = new Twig_Environment($loader, [
      'debug' => getenv('PHP_ENV') === 'dev'
  ]);
  if (getenv('PHP_ENV') === 'dev') $twig->addExtension(new Twig_Extension_Debug());

  // instantiate report object
  $weeklySIT3delta = new WeeklyDelta('SIT3');

  $twig->display('weekly-report.html.twig', [
    'data' => $weeklySIT3delta->getData()
  ]);
}
?>
</main>
<?php
$link->close();
echo "
<script src='https://d3js.org/d3.v5.js'></script>
<script src='js/pie_chart.js'></script>
<script>
window.drawOpenCloseChart(window.d3, '$statusOpen', '$statusClosed')
window.drawSeverityChart(window.d3, '$blockSev', '$critSev', '$majSev', '$minSev')
</script>
<!--DO NOT TYPE BELOW THIS LINE-->";
include('fileend.php');
