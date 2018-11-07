<?php
include('session.php');
include('html_components/defComponents.php');
include('html_functions/bootstrapGrid.php');
include('sql_functions/stmtBindResultArray.php');

$title = "SVBX - Update Deficiency";
$role = $_SESSION['role'];
$defID = filter_input(INPUT_GET, 'defID');

// prepare sql statement
$fieldList = preg_replace('/\s+/', '', file_get_contents('updateDef.sql'));
$fieldsArr = array_fill_keys(explode(',', $fieldList), '?');

include('filestart.php');

if (isset($_SESSION['errorMsg'])) {
    echo "
        <h1 style='font-size: 4rem; font-family: monospace; color: red;'>{$_SESSION['errorMsg']}</h1>";
    unset($_SESSION['errorMsg']);
}

try {
    $link = f_sqlConnect();
    $sql = "SELECT $fieldList FROM CDL WHERE defID=?";

    $elements = $requiredElements + $optionalElements + $closureElements;

    if (!$stmt = $link->prepare($sql)) throw new mysqli_sql_exception($link->error);

    if (!$stmt->bind_param('i', $defID)) throw new mysqli_sql_exception($stmt->error);

    if (!$stmt->execute()) throw new mysqli_sql_exception($stmt->error);

    if (!stmtBindResultArrayRef($stmt, $elements))
        throw new mysqli_sql_exception($stmt->error);

    $stmt->close();

    $defStatus = $elements['status']['value'];
    // special options for Contractor level when Def is Open
    if ($role === 15 && $defStatus === 1) {
        $elements['status']['query'] = [ 1 => 'Open', 4 => 'Request closure' ];
    }

    // query for comments associated with this Def
    $sql = "SELECT firstname, lastname, date_created, cdlCommText
        FROM cdlComments c
        JOIN users_enc u
        ON c.userID=u.userID
        WHERE c.defID=?
        ORDER BY c.date_created DESC";

    if (!$stmt = $link->prepare($sql)) throw new mysqli_sql_exception($link->error);

    if (!$stmt->bind_param('i', $defID)) throw new mysqli_sql_exception($stmt->error);

    if (!$stmt->execute()) throw new mysqli_sql_exception($stmt->error);

    $comments = stmtBindResultArray($stmt) ?: [];

    $stmt->close();

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
    
    if (!$stmt = $link->prepare("SELECT closureRequested, closureRequestedBy from CDL where defID = ?"))
        throw new mysqli_sql_exception($link->error);
        
    if (!$stmt->bind_param('i', $defID))
        throw new mysqli_sql_exception($stmt->error);

    if (!$stmt->execute())
        throw new mysqli_sql_exception($stmt->error);

    $closureRequested = stmtBindResultArray($stmt)[0]['closureRequested'];
        
    $stmt->close();

    $toggleBtn = '<a data-toggle=\'collapse\' href=\'#%1$s\' role=\'button\' aria-expanded=\'false\' aria-controls=\'%1$s\' class=\'collapsed\'>%2$s<i class=\'typcn typcn-arrow-sorted-down\'></i></a>';

    $requiredRows = [
        [
            $elements['safetyCert'],
            $elements['systemAffected']
        ],
        [
            $elements['location'],
            $elements['specLoc']
        ],
        [
            $elements['status'],
            $elements['severity']
        ],
        [
            $elements['dueDate'],
            $elements['groupToResolve']
        ],
        [
            $elements['requiredBy'],
            $elements['contractID']
        ],
        [
            $elements['identifiedBy'],
            $elements['defType']
        ],
        [
            $elements['description']
        ]
    ];

    $optionalRows = [
        [
            $elements['spec'],
            $elements['actionOwner']
        ],
        [
            $elements['oldID'],
            $elements['CDL_pics']
        ]
    ];

    $closureRows = [
        [
            $elements['evidenceType'],
            $elements['repo'],
            $elements['evidenceLink']
        ],
        [
            $elements['closureComments']
        ]
    ];
    
    $color = ($defStatus === 1 ? "bg-red " : "bg-success ") . "text-white";

    echo "
        <header class='container page-header'>
            <h1 class='page-title $color pad'>Update Deficiency ".$defID."</h1>";
            if (!empty($closureRequested)) {
                echo "<h4 class='bg-yellow text-light pad-less'>Closure requested</h4>";
            }
    echo "
        </header>
        <main class='container main-content'>
        <form action='updateDefCommit.php' method='POST' enctype='multipart/form-data' onsubmit='' class='item-margin-bottom'>
            <input type='hidden' name='defID' value='$defID'>
            <div class='row'>
                <div class='col-12'>
                    <h4 class='pad grey-bg'>Deficiency No. $defID</h4>
                </div>
            </div>";

            foreach ($requiredRows as $gridRow) {
                $options = [ 'required' => true ];
                if (count($gridRow) > 1) $options['inline'] = true;
                else $options['colWd'] = 6;
                print returnRow($gridRow, $options);
            }

        echo "
            <h5 class='grey-bg pad'>
                <a data-toggle='collapse' href='#optionalInfo' role='button' aria-expanded='false' aria-controls='optionalInfo' class='collapsed'>Optional Information<i class='typcn typcn-arrow-sorted-down'></i></a>
            </h5>
            <div id='optionalInfo' class='collapse item-margin-bottom'>";
            foreach ($optionalRows as $gridRow) {
                $options = [ 'required' => true ];
                if (count($gridRow) > 1) $options['inline'] = true;
                else $options['colWd'] = 6;
                print returnRow($gridRow, $options);
            }
        echo "
                <p class='text-center pad-less bg-yellow'>Photos uploaded from your phone may not preserve rotation information. We are working on a fix for this.</p>
            </div>
            <h5 class='grey-bg pad'>
                <a data-toggle='collapse' href='#closureInfo' role='button' aria-expanded='false' aria-controls='closureInfo' class='collapsed'>Closure Information<i class='typcn typcn-arrow-sorted-down'></i></a>
            </h5>
            <div id='closureInfo' class='collapse item-margin-bottom'>";
            foreach ($closureRows as $gridRow) {
                $options = [ 'required' => true ];
                if (count($gridRow) > 1) $options['inline'] = true;
                else $options['colWd'] = 6;
                print returnRow($gridRow, $options);
            }
        echo "
            </div>
            <h5 class='grey-bg pad'>";
        printf($toggleBtn, 'comments', 'Comments');
        echo "
            </h5>
            <div id='comments' class='collapse item-margin-bottom'>";
        echo returnRow([ $optionalElements['cdlCommText'] ], [ 'colWd' => 8 ]);
            foreach ($comments as $comment) {
                $userFullName = $comment['firstname'].' '.$comment['lastname'];
                $text = stripcslashes($comment['cdlCommText']);
                printf($commentFormat, $userFullName, $comment['date_created'], $text);
            }
        echo "</div>";

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

            echo "
                <div class='row item-margin-bottom'>
                    <div class='col-12 center-content'>";
                    // if Def is not Closed, show submit btn
                    // if Def is Closed, show "Re-open" btn
                    if ($defStatus !== 2) {
                        echo "
                            <button type='submit' class='btn btn-primary btn-lg'>Submit</button>
                            <button type='reset' class='btn btn-primary btn-lg'>Reset</button>";
                    } else {
                        echo "
                            <button type='button' class='btn btn-lg btn-primary' onclick='return reopenDef(event)'>Re-open Deficiency</button>";
                    }
            echo "
                    </div>
                </div>
            </form>";
    if ($role >= 40) {
        echo "
            <form action='DeleteDef.php' method='POST' onsubmit=''>
                <div class='row'>
                    <div class='col-12 center-content'>
                        <button class='btn btn-danger btn-lg' type='submit' name='q' value='$defID'
                            onclick='return confirm(`ARE YOU SURE? Deficiencies should not be deleted, your deletion will be logged.`)'>delete</button>
                    </div>
                </div>
            </form>";
    }
    echo "</main>";
    echo "
        <script>
            function reopenDef(ev) {
                const form = document.forms[0];
                document.forms[0].status.value = 1;
                form.submit();
            }
        </script>";
} catch (Exception $e) {
    print "Unable to retrieve record: {$e->getMessage()}";
    exit;
} finally {
    $link->close();
    include('fileend.php');
}
