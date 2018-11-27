<?php
require 'vendor/autoload.php';
require 'session.php';

if (!empty($_GET['bartDefID']) && !$_SESSION['bdPermit']) {
    header("This is not for you", true, 403);
    exit;
}

if (empty($_GET['bartDefID']) && empty($_GET['defID'])) {
    header("We need a deficiency ID to show you anything", true, 400);
    exit;
}

// TODO: move these fields and queries into the Deficiency class
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
    'evidenceID',
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
$projectAttachmentsTable = 'CDL_pics';
$projectPathField = 'pathToFile';
$projectTemplate = 'def.html.twig';

// bart def params
$bartFields = [
    'ID',
    'creator.partyName as creator',
    'nextStepName as next_step',
    'bic.partyName as bic',
    'statusName as status',
    'descriptive_title_vta',
    'root_prob_vta',
    'resolution_vta',
    'priority_vta',
    'agreeDisagreeName as agree_vta',
    'yesNoName as safety_cert_vta',
    'resolution_disputed',
    'structural',
    'id_bart',
    'description_bart',
    'cat1_bart',
    'cat2_bart',
    'cat3_bart',
    'level_bart',
    'dateOpen_bart',
    'dateClose_bart',
    'date_created',
    "CONCAT(cre.firstname, ' ', cre.lastname)",
    'Form_Modified',
    "CONCAT(upd.firstname, ' ', upd.lastname)"
];

$bartJoins = [
    'yesNo' => 'b.safety_cert_vta = yesNo.yesNoID',
    'users_enc upd' => 'b.updated_by = upd.userID',
    'bdNextStep' => 'b.next_step = bdNextStep.bdNextStepID',
    'bdParties creator' => 'b.creator = creator.partyID',
    'bdParties bic' => 'b.bic = bic.partyID',
    'status' => 'b.status = status.statusID',
    'agreeDisagree' => 'b.agree_vta = agreeDisagree.agreeDisagreeID',
    'users_enc cre' => 'b.created_by = cre.userID'
];

$bartTableName = 'BARTDL';
$bartTableAlias = 'b';
$bartIdField = 'ID';
$bartCommentTable = 'bartdlComments';
$bartComments = 'bdCommText';
$bartAttachmentsTable = 'bartdlAttachments';
$bartPathField = 'bdaFilepath';
$bartTemplate = 'bartDef.html.twig';

if (!empty($_GET)) $get = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);

list(
    $id,
    $idField,
    $tableName,
    $tableAlias,
    $fields,
    $joins,
    $commentTable,
    $commentTextField,
    $attachmentsTable,
    $pathField,
    $templatePath
) = (!empty($get['defID'])
    ? [
        $get['defID'],
        $projectIdField,
        $projectTableName,
        $projectTableAlias,
        $projectFields,
        $projectJoins,
        $projectCommentsTable,
        $projectComments,
        $projectAttachmentsTable,
        $projectPathField,
        $projectTemplate
        ]
        : (!empty($get['bartDefID'])
        ? [
            $get['bartDefID'],
            $bartIdField,
            $bartTableName,
            $bartTableAlias,
            $bartFields,
            $bartJoins,
            $bartCommentTable,
            $bartComments,
            $bartAttachmentsTable,
            $bartPathField,
            $bartTemplate
          ]
        : array_fill(0, 9, null)));
// TODO: handle case of no def ID

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
        'pageHeading' => "Deficiency No. $id",
        'data' => $data
    ];

    // query for comments associated with this Def
    $link->join('users_enc u', "$commentTable.userID = u.userID");
    $link->orderBy("$commentTable.date_created", 'DESC');
    $link->where(($idField === 'defID' ? 'defID' : 'bartdlID'), $id); // this is necessary because the name of the BART id field is different on the bartDef table and the comment table
    $context['data']['comments'] = $link->get($commentTable, null, [ "$commentTextField as commentText", 'date_created', "CONCAT(firstname, ' ', lastname) as userFullName" ]);

    // query for photos linked to this Def
    // keep BART and Project photos | attachments separate for now
    // to leave room for giving photos or attachments to either of those data types in the future
    if ($tableName === 'CDL') {
        $link->where($idField, $id);
        $photos = $link->get($attachmentsTable, null, "$pathField as filepath");
        $context['data']['photos'] = array_chunk($photos, 3);
    }
    if ($tableName === 'BARTDL') {
        $link->where('bartdlID', $id);
        $context['data']['attachments'] = $link->get($attachmentsTable, null, "$pathField as filepath");
    }
    
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
    $filter_decode = new Twig_Filter('safe', function($str) {
        return html_entity_decode($str, ENT_QUOTES);
    });
    $twig->addFilter($filter_decode);    
    $twig->addFilter($html_sanitize_decode);
    $twig->addFilter($filter_stripslashes);

    $twig->display($templatePath, $context);
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
