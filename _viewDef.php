<?php
require 'vendor/autoload.php';
require 'session.php';


// project def params
$projectFields = [
    'defID as ID',
    'yesNoName as safetyCert',
    'sys.systemName as systemAffected',
    'locationName as location',
    'specLoc',
    'statusName as status',
    'closureRequested',
    'severityName as severity',
    'dueDate',
    'grp.systemName as groupToResolve',
    'req.requiredBy',
    'contractName as contract',
    'identifiedBy',
    'defTypeName as defType',
    'description',
    'spec',
    'actionOwner',
    'oldID',
    'comments as moreInfo',
    'eviTypeName as evidenceType',
    'repoName as repo',
    'evidenceLink',
    'closureComments',
    'c.dateCreated',
    "CONCAT(cre.firstname, ' ', cre.lastName) as created_by",
    'c.lastUpdated',
    "CONCAT(upd.firstname, ' ', upd.lastName) as updated_by",
    'dateClosed',
    "CONCAT(close.firstname, ' ', close.lastName) as closureRequestedBy"
];

$projectJoins = [
    'yesNo' => 'c.safetyCert = yesNo.yesNoID',
    'system sys' => 'c.systemAffected = sys.systemID',
    'location' => 'c.location = location.locationID',
    'status' => 'c.status = status.statusID',
    'severity' => 'c.severity = severity.severityID',
    'system grp' => 'c.groupToResolve = grp.systemID',
    'requiredBy req' => 'c.requiredBy = req.reqByID',
    'contract' => 'c.contractID = contract.contractID',
    'defType' => 'c.defType = defType.defTypeID',
    'evidenceType' => 'c.evidenceType = evidenceType.eviTypeID',
    'repo' => 'c.repo = repo.repoID',
    'users_enc cre' => 'c.created_by = cre.username',
    'users_enc upd' => 'c.updated_by = upd.username',
    'users_enc close' => 'c.closureRequestedBy = close.username'
];

$projectTableName = 'CDL';
$projectTableAlias = 'c';
$projectIdField = 'defID';

$projectCommentsTable = 'cdlComments';
$projectComments = 'cdlCommText';

// bart def params
$bartFields = [];
$bartJoins = [];
$bartTableName = 'bartDL';
$bartTableAlias = 'b';
$bartIdField = 'ID';
$bartCommentTable = 'bartdlComments';
$bartComments = 'bdCommText';

if (!empty($_GET['defID'])) $defID = filter_input(INPUT_GET, 'defID');
elseif (!empty($_GET['bartDefID'])) $defID = filter_input(INPUT_GET, 'bartDefID');
else $defID = null;

list($id, $idField, $tableName, $tableAlias, $fields, $joins, $commentTable, $commentTextField) = (!empty($_GET['defID']) // TODO: clean GET var before using it
    ? [ $_GET['defID'], $projectIdField, $projectTableName, $projectTableAlias, $projectFields, $projectJoins, $projectCommentsTable, $projectComments ]
    : (!empty($_GET['bartDefID'])
        ? [ $_GET['bartDefID'], $bartIdField, $bartTableName, $bartTableAlias, $bartFields, $bartJoins, $bartCommentTable, $bartComments] // TODO: put the BART values in here
        : array_fill(0, 8, null)));

$role = $_SESSION['role'];
$title = "Deficiency No. " . $defID;

// echo '<pre>Query terms: '; print_r([$id, $idField, $tableName, $tableAlias, $fields, $joins]); echo '</pre>';

try {
    $link = new MySqliDB(DB_CREDENTIALS);

    foreach ($joins as $joinTable => $onCondition) {
        $link->join($joinTable, $onCondition, 'LEFT');
    }

    $link->where('statusName', 'deleted', '<>');
    $link->where($idField, $id);

    $data = $link->getOne("$tableName $tableAlias", $fields);

    if (strcasecmp($data['status'], "open") === 0) {
        $color = "bg-red text-white";
    } else {
        $color = "bg-green text-white";
    }

    $context = [
        'session' => $_SESSION,
        'pageHeading' => "Deficiency No. ",
        'data' => $data
    ];

    // query for comments associated with this Def
    $link->join('users_enc u', "$commentTable.userID = u.userID");
    $link->orderBy("$commentTable.date_created", 'DESC');
    $link->where($idField, $id);
    $context['data']['comments'] = $link->get($commentTable, null, [ "$commentTextField as commentText", 'date_created', "CONCAT(firstname, ' ', lastname) as userFullName" ]);
    $context['meta'] = $link->getLastQuery();

    // instantiate Twig
    $loader = new Twig_Loader_Filesystem('./templates');
    $twig = new Twig_Environment($loader, [ 'debug' => $_ENV['PHP_ENV'] === 'dev' ]);
    $twig->addExtension(new Twig_Extension_Debug());
    // $template = $twig->load();

    $html_sanitize_decode = new Twig_Filter('html_sanitize_decode', function($str) {
        $decoded = html_entity_decode($str, ENT_QUOTES);
        return filter_var($decoded, FILTER_SANITIZE_SPECIAL_CHARS);
    });
    $filter_stripslashes = new Twig_Filter('unescape', function($str) {
        return stripcslashes($str);
    });
    $twig->addFilter($html_sanitize_decode);
    $twig->addFilter($filter_stripslashes);

    $twig->display('def.html.twig', $context);

    // // query for photos linked to this Def
    // if (!$stmt = $link->prepare("SELECT pathToFile FROM CDL_pics WHERE defID=?"))

    // $photos =

} catch (Twig_Error $e) {
    echo "Unable to render template";
    error_log($e);
} catch (Exception $e) {
    echo "Unable to retrieve record";
    error_log($e);
} finally {
    $link->disconnect();
    exit;
}

    // check for bartdl permission
    // if ($bdPermit) {

    //     // query for attachments and render them as a list of links
    //     $attachments = getAttachments($link, $defID);
    //     $attachmentList = renderAttachmentsAsAnchors($attachments);

    //     // build SELECT query string from sql file
    //     $fieldList = preg_replace('/\s+/', '', file_get_contents('bartdl.sql'))
    //         .',form_modified';
    //     // replace ambiguous or JOINED keys
    //     $fieldList = str_replace('updated_by', 'BARTDL.updated_by AS updated_by', $fieldList);
    //     $fieldList = str_replace('status', 's.statusName AS status', $fieldList);
    //     $fieldList = str_replace('agree_vta', 'ag.agreeDisagreeName AS agree_vta', $fieldList);
    //     $fieldList = str_replace('creator', 'c.partyName AS creator', $fieldList);
    //     $fieldList = str_replace('next_step', 'n.nextStepName AS next_step', $fieldList);
    //     $fieldList = str_replace('safety_cert_vta', 'y.yesNoName AS safety_cert_vta', $fieldList);
    //     $sql = 'SELECT '
    //         .$fieldList
    //         ." FROM BARTDL"
    //         ." JOIN status s ON BARTDL.status=s.statusID"
    //         ." JOIN agreeDisagree ag ON BARTDL.agree_vta=ag.agreeDisagreeID"
    //         ." JOIN bdParties c ON BARTDL.creator=c.partyID"
    //         ." JOIN bdNextStep n ON BARTDL.next_step=n.bdNextStepID"
    //         ." JOIN yesNo y ON BARTDL.safety_cert_vta = y.yesNoID"
    //         ." WHERE BARTDL.id=?";

    //     function validateFormatDate($dateStr, $inputFormat, $outputFormat, $nullChar = '—') {
    //         return (
    //             strtotime($dateStr) <= 0
    //                 ? $nullChar
    //                 : DateTime::createFromFormat($inputFormat, $dateStr)->format($outputFormat)
    //         );
    //     }

    //     function formatOpenCloseDate($dateStr) {
    //         $inputFormat = 'Y-m-d';
    //         $outputFormat = 'd/m/Y';
    //         return validateFormatDate($dateStr, $inputFormat, $outputFormat);
    //     }

    //         // query for comments associated with this Def
    //         $sql = "SELECT firstname, lastname, date_created, bdCommText
    //             FROM bartdlComments bdc
    //             JOIN users_enc u
    //             ON bdc.userID=u.userID
    //             WHERE bartdlID=?
    //             ORDER BY date_created DESC";
