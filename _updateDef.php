<?php
require 'vendor/autoload.php';
require 'session.php';

// include('html_components/defComponents.php');
// include('html_functions/bootstrapGrid.php');
// include('sql_functions/stmtBindResultArray.php');

// $title = "SVBX - Update Deficiency";
// $role = $_SESSION['role'];
$get = !empty($_GET) ? filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS) : null;

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
$projectAttachmentsTable = 'CDL_pics';
$projectPathField = 'pathToFile';
$projectTemplate = 'defForm.html.twig';

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

$bartTableName = 'bartDL';
$bartTableAlias = 'b';
$bartIdField = 'ID';
$bartCommentTable = 'bartdlComments';
$bartComments = 'bdCommText';
$bartAttachmentsTable = 'bartdlAttachments';
$bartPathField = 'bdaFilepath';
$bartTemplate = 'bartForm.html.twig';

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
        : array_fill(0, 11, null)));

// prepare sql statement
// $fieldList = preg_replace('/\s+/', '', file_get_contents('updateDef.sql'));
// $fieldsArr = array_fill_keys(explode(',', $fieldList), '?');

// include('filestart.php');

// if (isset($_SESSION['errorMsg'])) {
//     echo "
//         <h1 style='font-size: 4rem; font-family: monospace; color: red;'>{$_SESSION['errorMsg']}</h1>";
//     unset($_SESSION['errorMsg']);
// }

try {
    $link = new MySqliDB(DB_CREDENTIALS);
    // $sql = "SELECT $fieldList FROM CDL WHERE defID=?";

    // $defStatus = $elements['status']['value'];
    // // special options for Contractor level when Def is Open
    // if ($role === 15 && $defStatus === 1) {
    //     $elements['status']['query'] = [ 1 => 'Open', 4 => 'Request closure' ];
    // }

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
    $link->where(($idField === 'defID' ?: 'bartdlID'), $id);
    $context['data']['comments'] = $link->get($commentTable, null, [ "$commentTextField as commentText", 'date_created', "CONCAT(firstname, ' ', lastname) as userFullName" ]);

    // query for photos linked to this Def
    // keep BART and Project photos | attachments separate for now
    // to leave room for giving photos or attachments to either of those data types in the future
    if ($tableName === 'CDL') {
        $link->where($idField, $id);
        $photos = $link->get($attachmentsTable, null, "$pathField as filepath");
        $context['data']['photos'] = array_chunk($photos, 3);
    }
    if ($tableName = 'BARTLDL') {
        $link->where('bartdlID', $id);
        $context['data']['attachments'] = $link->get($attachmentsTable, null, "$pathField as filepath");
    }
    
    $context['meta'] = $_ENV['SVBX_TIMEOUT'];

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

    $twig->display($templatePath, $context);
    // // query for comments associated with this Def
    // $sql = "SELECT firstname, lastname, date_created, cdlCommText
    //     FROM cdlComments c
    //     JOIN users_enc u
    //     ON c.userID=u.userID
    //     WHERE c.defID=?
    //     ORDER BY c.date_created DESC";

    // if (!$stmt = $link->prepare($sql)) throw new mysqli_sql_exception($link->error);

    // $comments = stmtBindResultArray($stmt) ?: [];

    // // query for photos linked to this Def
    // if (!$stmt = $link->prepare("SELECT pathToFile FROM CDL_pics WHERE defID=?"))

    // $photos = stmtBindResultArray($stmt);

    // $toggleBtn = '<a data-toggle=\'collapse\' href=\'#%1$s\' role=\'button\' aria-expanded=\'false\' aria-controls=\'%1$s\' class=\'collapsed\'>%2$s<i class=\'typcn typcn-arrow-sorted-down\'></i></a>';

    // $requiredRows = [
    //     [
    //         $elements['safetyCert'],
    //         $elements['systemAffected']
    //     ],
    //     [
    //         $elements['location'],
    //         $elements['specLoc']
    //     ],
    //     [
    //         $elements['status'],
    //         $elements['severity']
    //     ],
    //     [
    //         $elements['dueDate'],
    //         $elements['groupToResolve']
    //     ],
    //     [
    //         $elements['requiredBy'],
    //         $elements['contractID']
    //     ],
    //     [
    //         $elements['identifiedBy'],
    //         $elements['defType']
    //     ],
    //     [
    //         $elements['description']
    //     ]
    // ];

    // $optionalRows = [
    //     [
    //         $elements['spec'],
    //         $elements['actionOwner']
    //     ],
    //     [
    //         $elements['oldID'],
    //         $elements['CDL_pics']
    //     ]
    // ];

    // $closureRows = [
    //     [
    //         $elements['evidenceType'],
    //         $elements['repo'],
    //         $elements['evidenceLink']
    //     ],
    //     [
    //         $elements['closureComments']
    //     ]
    // ];
    
    // $color = ($defStatus === 1 ? "bg-red " : "bg-success ") . "text-white";

    // echo "
    //     <header class='container page-header'>
    //         <h1 class='page-title $color pad'>Update Deficiency ".$defID."</h1>";
    //         if (!empty($closureRequested)) {
    //             echo "<h4 class='bg-yellow text-light pad-less'>Closure requested</h4>";
    //         }
    // echo "
    //     </header>
    //     <main class='container main-content'>
    //     <form action='updateDefCommit.php' method='POST' enctype='multipart/form-data' onsubmit='' class='item-margin-bottom'>
    //         <input type='hidden' name='defID' value='$defID'>
    //         <div class='row'>
    //             <div class='col-12'>
    //                 <h4 class='pad grey-bg'>Deficiency No. $defID</h4>
    //             </div>
    //         </div>";

    //         foreach ($requiredRows as $gridRow) {
    //             $options = [ 'required' => true ];
    //             if (count($gridRow) > 1) $options['inline'] = true;
    //             else $options['colWd'] = 6;
    //             print returnRow($gridRow, $options);
    //         }

    //     echo "
    //         <h5 class='grey-bg pad'>
    //             <a data-toggle='collapse' href='#optionalInfo' role='button' aria-expanded='false' aria-controls='optionalInfo' class='collapsed'>Optional Information<i class='typcn typcn-arrow-sorted-down'></i></a>
    //         </h5>
    //         <div id='optionalInfo' class='collapse item-margin-bottom'>";
    //         foreach ($optionalRows as $gridRow) {
    //             $options = [ 'required' => true ];
    //             if (count($gridRow) > 1) $options['inline'] = true;
    //             else $options['colWd'] = 6;
    //             print returnRow($gridRow, $options);
    //         }
    //     echo "
    //             <p class='text-center pad-less bg-yellow'>Photos uploaded from your phone may not preserve rotation information. We are working on a fix for this.</p>
    //         </div>
    //         <h5 class='grey-bg pad'>
    //             <a data-toggle='collapse' href='#closureInfo' role='button' aria-expanded='false' aria-controls='closureInfo' class='collapsed'>Closure Information<i class='typcn typcn-arrow-sorted-down'></i></a>
    //         </h5>
    //         <div id='closureInfo' class='collapse item-margin-bottom'>";
    //         foreach ($closureRows as $gridRow) {
    //             $options = [ 'required' => true ];
    //             if (count($gridRow) > 1) $options['inline'] = true;
    //             else $options['colWd'] = 6;
    //             print returnRow($gridRow, $options);
    //         }
    //     echo "
    //         </div>
    //         <h5 class='grey-bg pad'>";
    //     printf($toggleBtn, 'comments', 'Comments');
    //     echo "
    //         </h5>
    //         <div id='comments' class='collapse item-margin-bottom'>";
    //     echo returnRow([ $optionalElements['cdlCommText'] ], [ 'colWd' => 8 ]);
    //         foreach ($comments as $comment) {
    //             $userFullName = $comment['firstname'].' '.$comment['lastname'];
    //             $text = stripcslashes($comment['cdlCommText']);
    //             printf($commentFormat, $userFullName, $comment['date_created'], $text);
    //         }
    //     echo "</div>";

    //     if (count($photos)) {
    //         print returnCollapseSection(
    //             'Photos',
    //             'defPics',
    //             returnPhotoSection(
    //                 $photos,
    //                 "<img src='%s' alt='photo related to deficiency number {$defID}'>"
    //             ),
    //             'item-margin-bottom'
    //         );
    //     }

    //         echo "
    //             <div class='row item-margin-bottom'>
    //                 <div class='col-12 center-content'>";
    //                 // if Def is not Closed, show submit btn
    //                 // if Def is Closed, show "Re-open" btn
    //                 if ($defStatus !== 2) {
    //                     echo "
    //                         <button type='submit' class='btn btn-primary btn-lg'>Submit</button>
    //                         <button type='reset' class='btn btn-primary btn-lg'>Reset</button>";
    //                 } else {
    //                     echo "
    //                         <button type='button' class='btn btn-lg btn-primary' onclick='return reopenDef(event)'>Re-open Deficiency</button>";
    //                 }
    //         echo "
    //                 </div>
    //             </div>
    //         </form>";
    // if ($role >= 40) {
    //     echo "
    //         <form action='DeleteDef.php' method='POST' onsubmit=''>
    //             <div class='row'>
    //                 <div class='col-12 center-content'>
    //                     <button class='btn btn-danger btn-lg' type='submit' name='q' value='$defID'
    //                         onclick='return confirm(`ARE YOU SURE? Deficiencies should not be deleted, your deletion will be logged.`)'>delete</button>
    //                 </div>
    //             </div>
    //         </form>";
    // }
    // echo "</main>";
    // echo "
    //     <script>
    //         function reopenDef(ev) {
    //             const form = document.forms[0];
    //             document.forms[0].status.value = 1;
    //             form.submit();
    //         }
    //     </script>";
} catch (Twig_Exception $e) {
    echo "Unable to render template";
    error_log($e);
} catch (Exception $e) {
    echo "Unable to retrieve record";
    error_log($e);
} finally {
    if (is_a($link, 'MySqliDB')) $link->disconnect();
    exit;
}
