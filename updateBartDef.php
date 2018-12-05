<?php
include('session.php');
include('html_components/defComponents.php');
include('html_functions/bootstrapGrid.php');
include('sql_functions/stmtBindResultArray.php');
include('error_handling/sqlErrors.php');

$title = "SVBX - Update BART Deficiency";

$defID = $_GET['bartDefID'];
$fieldList = file_get_contents('bartdl.sql');
$fieldsArr = array_fill_keys(explode(',', $fieldList), '?');
$fieldList = implode(',', array_keys($fieldsArr));

include('filestart.php');

$sql = "SELECT $fieldList FROM BARTDL WHERE id=$defID";

$link = f_sqlConnect();

if ($result = $link->query('SELECT bdPermit from users_enc where userID='.$_SESSION['userID'])) {
    if ($row = $result->fetch_row()) {
        $bdPermit = $row[0];
    }
    $result->close();
}

// copy elements from external file '/html_components/defComponents.php'
$elements = $generalElements + $vtaElements + $bartElements;

// query for attachments and append them to elements as a list of links
$attachments = getAttachments($link, $defID);
$attachmentList = renderAttachmentsAsAnchors($attachments);
$elements['bartdlAttachments']['element'] =
    $elements['bartdlAttachments']['label']
    .sprintf($elements['bartdlAttachments']['element'], $attachmentList);

if ($stmt = $link->prepare($sql)) {
    if ($stmt->execute()) {
        // bind result to 'value' prop of each corresponding element
        stmtBindResultArrayRef($stmt, $elements);
        $labelStr = "<label for='%s'%s>%s</label>";
        $required = " class='required'";
        $commentFormat = "
            <div class='thin-grey-border pad mb-3'>
                <h6 class='d-flex flex-row justify-content-between text-secondary'><span>%s</span><span>%s</span></h6>
                <p>%s</p>
            </div>";


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

        $generalRows = [
            'row1' => [
                'col1' => [
                    'options' => [ 'inline' => true ],
                    [
                        $elements['creator']
                    ],
                    [
                        $elements['next_step']
                    ],
                    [
                        $elements['bic']
                    ],
                    [
                        $elements['status']
                    ]
                ],
                $elements['descriptive_title_vta']
            ]
        ];

        $vtaRows = [
            'row1' => [ $elements['root_prob_vta'] ],
            'row2' => [ $elements['resolution_vta'] ],
            'row3' => [
                'col1' => [
                    'options' => [ 'inline' => true ],
                    [ $elements['priority_vta'] ],
                    [ $elements['agree_vta'] ],
                    [ $elements['safety_cert_vta'] ],
                    [
                        'options' => [ 'inline' => true ],
                        $elements['resolution_disputed'],
                        $elements['structural']
                    ]
                ],
                'col2' => [
                    [ $elements['bartdlAttachments'] ],
                    [
                        'options' => [ 'inline' => true ],
                        $elements['attachment']
                    ]                    ]
            ]
        ];

        $bartRows = [
            'row1' => [ $elements['id_bart'] ],
            'row2' => [ $elements['description_bart'] ],
            'row3' => [
                'options' => [ 'inline' => true ],
                'col1' => [
                    [ $elements['cat1_bart'] ],
                    [ $elements['cat2_bart'] ],
                    [ $elements['cat3_bart'] ]
                ],
                'col2' => [
                    [ $elements['level_bart'] ],
                    [ $elements['dateOpen_bart'] ],
                    [ $elements['dateClose_bart'] ]
                ]
            ]
        ];
        echo "
            <header class='container page-header'>
                <h1 class='page-title'>Update Deficiency $defID</h1>";
            if (!empty($_SESSION['errorMsg'])) {
                 echo "
                    <div class='thin-grey-border bg-yellow pad'>
                        <p class='mt-0 mb-0'>{$_SESSION['errorMsg']}</p>
                    </div>";
                unset($_SESSION['errorMsg']);
            }
        echo "
            </header>
            <main role='main' class='container main-content'>
                <form action='updateBartDefCommit.php' method='POST' enctype='multipart/form-data'>
                    <input type='hidden' name='id' value='{$defID}' >
                    <h5 class='grey-bg pad'>General Information</h5>";
                    foreach ($generalRows as $gridRow) {
                        print returnRow($gridRow);
                    }
        echo "
                    <h5 class='grey-bg pad'>VTA Information</h5>";
                    foreach ($vtaRows as $gridRow) {
                        print returnRow($gridRow);
                    }
        echo "
                    <h5 class='grey-bg pad'>BART Information</h5>";
                    foreach ($bartRows as $gridRow) {
                        print returnRow($gridRow);
                    }

        echo "
                    <h5 class='grey-bg pad'>Comments</h5>";
                    print returnRow([ $elements['bdCommText'] ], [ 'colWd' => 8 ]);
                    foreach ($comments as $comment) {
                        $timestamp = strtotime($comment['date_created']) - (60 * 60 * 7);

                        printf(
                            $commentFormat,
                            $comment['firstname'].' '.$comment['lastname'],
                            date('j/n/Y • g:i a', $timestamp),
                            stripcslashes($comment['bdCommText'])
                        );
                    }

        echo "
                    <div class='center-content'>
                        <button type='submit' class='btn btn-primary btn-lg'>Submit</button>
                    </div>
                </form>
            </main>";
    } else {
        printSqlErrorAndExit($stmt, $sql);
    }
    // echo "</div>";
} else {
    printSqlErrorAndExit($link, $sql);
}

$link->close();
include('fileend.php');
