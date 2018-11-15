<?php
require 'vendor/autoload.php';
require 'session.php';
// include('html_functions/bootstrapGrid.php');
// include('html_functions/htmlFuncs.php');
// include('html_components/defComponents.php');
// include('sql_functions/stmtBindResultArray.php');
// include('error_handling/sqlErrors.php');

if (!empty($_GET['defID'])) $defID = filter_input(INPUT_GET, 'defID');
elseif (!empty($_GET['bartDefID'])) $defID = filter_input(INPUT_GET, 'bartDefID');
else $defID = null;

// TODO set $id, $tableName, $fields all in one shot with list()

$role = $_SESSION['role'];
$title = "Deficiency No. " . $defID;

if (!empty($_GET['defID'])) {
    // $sql = file_get_contents("viewDef.sql").$defID;
    
    try {
        $link = new MySqliDB(DB_CREDENTIALS);

        $fields = [
            'ID',
            'yesNoName as safetyCert', // JOIN
            'sys.systemName as systemAffected', // JOIN
            'locationName as location', // JOIN
            'specLoc',
            'statusName as status', // JOIN
            'closureRequested',
            'severityName as severity', // JOIN
            'dueDate',
            'grp.systemName as groupToResolve', // JOIN
            'req.requiredBy',
            'contractName as contractID', // JOIN
            'identifiedBy',
            'defTypeName as defType', // JOIN
            'description',
            'spec',
            'actionOwner',
            'oldID',
            'comments',
            'eviTypeName as evidenceType', // JOIN
            'repoName as repo', // JOIN
            'evidenceLink',
            'closureComments',
            'dateCreated',
            "CONCAT(cre.firstname, ' ', cre.lastName) as createdBy", // JOIN
            'lastUpdated',
            "CONCAT(upd.firstname, ' ', upd.lastName) as updatedBy", // JOIN
            'dateClosed',
            "CONCAT(close.firstname, ' ', close.lastName) as closureRequestedBy" // JOIN
        ];

        $joins = [

        ];

            if ($Status == "Open") {
                $color = "bg-red text-white";
            } else {
                $color = "bg-success text-white";
            }
            echo "
                <header class='container page-header'>
                    <h1 class='page-title $color pad'>Deficiency No. $defID</h1>";
                    if ($closureRequested) {
                        echo "<h4 class='bg-yellow text-light pad-less'>Closure requested</h4>";
                    }
            echo "
                </header>
                <main class='container main-content'>";
            foreach ([$requiredRows, $optionalRows, $closureRows] as $rowGroup) {
                $rowName = array_shift($rowGroup);
                $content = iterateRows($rowGroup);
                printSection($rowName, $content);
            }
        }

        $stmt->close();

        // query for comments associated with this Def
        $sql = "SELECT firstname, lastname, date_created, cdlCommText
            FROM cdlComments c
            JOIN users_enc u
            ON c.userID=u.userID
            WHERE c.defID=?
            ORDER BY c.date_created DESC";

        if (!$stmt = $link->prepare($sql))
            throw new mysqli_sql_exception($link->error);

        if (!$stmt->bind_param('i', $defID))
            throw new mysqli_sql_exception($stmt->error);

        if (!$stmt->execute())
            throw new mysqli_sql_exception($stmt->error);

        $comments = stmtBindResultArray($stmt) ?: [];

        // query for photos linked to this Def
        if (!$stmt = $link->prepare("SELECT pathToFile FROM CDL_pics WHERE defID=?"))
            throw new mysqli_sql_exception($link->error);

        if (!$stmt->bind_param('i', $defID))
            throw new mysqli_sql_exception($stmt->error);

        if (!$stmt->execute())
            throw new mysqli_sql_exception($stmt->error);

        if (!$stmt->store_result())
            throw new mysqli_sql_exception($stmt->error);

        $photos = stmtBindResultArray($stmt);

        $stmt->close();

        if (count($comments)) {
            print returnCollapseSection(
                'Comments',
                'comments',
                returnCommentsHTML($comments)
            );
        }

        print returnCollapseSection(
            'Modification History',
            'modHistory',
            iterateRows($modHistory)
        );

        if (count($photos)) {
            print returnCollapseSection(
                'Photos',
                'defPics',
                returnPhotoSection(
                    $photos,
                    "<img src='%s' alt='photo related to deficiency number {$defID}'>"
                ),
                'item-margin-bottom'
            );
        }

        // if Role has permission level show Update and Clone buttons
        if($role > 10) {
            echo "
                <div class='row item-margin-botom'>
                    <div class='col-12 center-content'>
                        <a href='updateDef.php?defID=$defID' class='btn btn-primary btn-lg'>Update</a>
                        <a href='cloneDef.php?defID=$defID' class='btn btn-primary btn-lg'>Clone</a>
                    </div>
                </div>";
        }
        echo "</main>";
    } catch (Exception $e) {
        print "Unable to retrieve record: $e";
        exit;
    } finally {
        $link->close();
        include('fileend.php');
        exit;
    }
} elseif (!empty($_GET['bartDefID'])) {
    $link = f_sqlConnect();
    // check for bartdl permission
    if ($result = $link->query('SELECT bdPermit from users_enc where userID='.$_SESSION['userID'])) {
        if ($row = $result->fetch_row()) {
            $bdPermit = $row[0];
        }
        $result->close();
    }
    if ($bdPermit) {
        // render View for bartDef
        $result = [];
        // query for attachments and render them as a list of links
        $attachments = getAttachments($link, $defID);
        $attachmentList = renderAttachmentsAsAnchors($attachments);
        $attachmentDisplay =
            $vtaElements['bartdlAttachments']['label']
            .sprintf($vtaElements['bartdlAttachments']['element'], $attachmentList);

        // build SELECT query string from sql file
        $fieldList = preg_replace('/\s+/', '', file_get_contents('bartdl.sql'))
            .',form_modified';
        // replace ambiguous or JOINED keys
        $fieldList = str_replace('updated_by', 'BARTDL.updated_by AS updated_by', $fieldList);
        $fieldList = str_replace('status', 's.statusName AS status', $fieldList);
        $fieldList = str_replace('agree_vta', 'ag.agreeDisagreeName AS agree_vta', $fieldList);
        $fieldList = str_replace('creator', 'c.partyName AS creator', $fieldList);
        $fieldList = str_replace('next_step', 'n.nextStepName AS next_step', $fieldList);
        $fieldList = str_replace('safety_cert_vta', 'y.yesNoName AS safety_cert_vta', $fieldList);
        $sql = 'SELECT '
            .$fieldList
            ." FROM BARTDL"
            ." JOIN status s ON BARTDL.status=s.statusID"
            ." JOIN agreeDisagree ag ON BARTDL.agree_vta=ag.agreeDisagreeID"
            ." JOIN bdParties c ON BARTDL.creator=c.partyID"
            ." JOIN bdNextStep n ON BARTDL.next_step=n.bdNextStepID"
            ." JOIN yesNo y ON BARTDL.safety_cert_vta = y.yesNoID"
            ." WHERE BARTDL.id=?";

        if ($stmt = $link->prepare($sql)) {
            if (!$stmt->bind_param('i', $defID)) printSqlErrorAndExit($stmt, $sql);

            if (!$stmt->execute()) printSqlErrorAndExit($stmt, $sql);

            $result = stmtBindResultArray($stmt)[0];

            function validateFormatDate($dateStr, $inputFormat, $outputFormat, $nullChar = '—') {
                return (
                    strtotime($dateStr) <= 0
                        ? $nullChar
                        : DateTime::createFromFormat($inputFormat, $dateStr)->format($outputFormat)
                );
            }

            function formatOpenCloseDate($dateStr) {
                $inputFormat = 'Y-m-d';
                $outputFormat = 'd/m/Y';
                return validateFormatDate($dateStr, $inputFormat, $outputFormat);
            }

            $dateOpen = formatOpenCloseDate($result['dateOpen_bart']);
            $dateClosed = formatOpenCloseDate($result['dateClose_bart']);

            $generalFields = [
                [
                    [
                        [ sprintf($labelStr, 'ID'), sprintf($fakeInputStr, $result['id']) ],
                        [ sprintf($labelStr, 'Creator'), sprintf($fakeInputStr, $result['creator']) ],
                        [ sprintf($labelStr, 'Next step'), sprintf($fakeInputStr, $result['next_step']) ],
                        [ sprintf($labelStr, 'BIC'), sprintf($fakeInputStr, $result['bic']) ],
                        [ sprintf($labelStr, 'Status'), sprintf($fakeInputStr, $result['status']) ]
                    ],
                    [
                        [ sprintf($labelStr, 'Descriptive').sprintf($fakeInputStr, stripcslashes($result['descriptive_title_vta'])) ]
                    ]
                ]
            ];

            $vtaFields = [
                'Root_Prob_VTA' => [ sprintf($labelStr, 'Root problem').sprintf($labelStr, sprintf($fakeInputStr, stripcslashes($result['root_prob_vta']))) ],
                'Resolution_VTA' => [ sprintf($labelStr, 'Resolution').sprintf($labelStr, sprintf($fakeInputStr, stripcslashes($result['resolution_vta']))) ],
                [
                    [
                        [ sprintf($labelStr, 'Priority'), sprintf($fakeInputStr, $result['priority_vta']) ],
                        [ sprintf($labelStr, 'Agree'), sprintf($fakeInputStr, $result['agree_vta']) ],
                        [ sprintf($labelStr, 'Safety Certifiable'), sprintf($fakeInputStr, $result['safety_cert_vta']) ],
                        [
                            checkboxLabel('resolution_disputed', 'Resolution disputed').returnCheckboxInput(['value' => $result['resolution_disputed']] + $checkbox),
                            checkboxLabel('structural', 'Structural').returnCheckboxInput(['value' => $result['structural']] + $checkbox)
                        ]
                    ],
                    [
                        [ $attachmentDisplay ]
                    ]
                ]
            ];

            $bartFields = [
                'BART ID' => [
                    returnRow([ sprintf($labelStr, 'BART ID').sprintf($fakeInputStr, stripcslashes($result['id_bart'])) ]),
                ],
                'Description' => [
                    returnRow([ sprintf($labelStr, 'Description').sprintf($fakeInputStr, stripcslashes($result['description_bart'])) ])
                ],
                [
                    returnRow([ sprintf($labelStr, 'Cat1'), sprintf($fakeInputStr, $result['cat1_bart']) ]).
                    returnRow([ sprintf($labelStr, 'Cat2'), sprintf($fakeInputStr, $result['cat2_bart']) ]).
                    returnRow([ sprintf($labelStr, 'Cat3'), sprintf($fakeInputStr, $result['cat3_bart']) ]),
                    returnRow([ sprintf($labelStr, 'Level'), sprintf($fakeInputStr, $result['level_bart']) ]).
                    returnRow([ sprintf($labelStr, 'Date open'), sprintf($fakeInputStr, $dateOpen) ]).
                    returnRow([ sprintf($labelStr, 'Date closed'), sprintf($fakeInputStr, $dateClosed) ])
                ]
            ];

            $stmt->close();

            // query for comments associated with this Def
            $sql = "SELECT firstname, lastname, date_created, bdCommText
                FROM bartdlComments bdc
                JOIN users_enc u
                ON bdc.userID=u.userID
                WHERE bartdlID=?
                ORDER BY date_created DESC";

            if (!$stmt = $link->prepare($sql)) printSqlErrorAndExit($link, $sql);

            if (!$stmt->bind_param('i', $defID)) printSqlErrorAndExit($stmt, $sql);

            if (!$stmt->execute()) printSqlErrorAndExit($stmt, $sql);

            $comments = stmtBindResultArray($stmt) ?: [];

            $stmt->close();

            if($result['status'] === "Closed") {
                $color = "bg-success text-white";
            } else {
                $color = "bg-red text-white";
            }

            echo "
                <header class='container page-header'>
                    <h1 class='page-title $color pad'>Deficiency No. $defID</h1>
                </header>
                <main class='container main-content'>";
            foreach ($generalFields as $gridRow) {
                print returnRow($gridRow);
            }
            print "<h5 class='grey-bg pad'>VTA Information</h5>";
            foreach ($vtaFields as $gridRow) {
                print returnRow($gridRow);
            }
            print "<h5 class='grey-bg pad'>BART Information</h5>";
            foreach ($bartFields as $gridRow) {
                print returnRow($gridRow);
            }

            if (count($comments)) {
                print "<h5 class='grey-bg pad'>Comments</h5>";
                foreach ($comments as $comment) {
                    $timestamp = strtotime($comment['date_created']) - (60 * 60 * 7);

                    printf(
                        $commentFormat,
                        $comment['firstname'].' '.$comment['lastname'],
                        date('j/n/Y • g:i a', $timestamp),
                        stripcslashes($comment['bdCommText'])
                    );
                }
            }

            print "
                <div class='center-content'>
                    <a href='updateBartDef.php?bartDefID=$defID' class='btn btn-primary btn-lg'>Update</a>
                </div>
            </main>";
            // print "<header class='page-header'><h4 class='text-success'>&darr; BART def view will go here &darr;</h4></header>";
        } else printSqlErrorAndExit($link, $sql);
    }
    $link->close();
    include('fileend.php');
    exit;
} else {
    echo "<h1 class='text-secondary'>No deficiency number found</h1>";
}
