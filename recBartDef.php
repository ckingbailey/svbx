<?php
use codeguy\Upload\Exception;
require 'vendor/autoload.php';

session_start();
include('SQLFunctions.php');
include('error_handling/sqlErrors.php');
include('uploadAttachment.php');
$link = f_sqlConnect();

$date = date('Y-m-d');
$userID = intval($_SESSION['userID']);

function printException(\Exception $exc, $color = 'orangeRed') {
    print "
        <div style='font-family: monospace'>
            <h4>Caught exception:</h4>
            <h2 style='color: $color'>".$exc->getCode().": ".$exc->getMessage()."</h2>
            <h3>".$exc->getFile().": ".$exc->getLine()."</h3>
            <h4>$line</h4>
        </div>";
}

// prepare POST and sql string for commit
$post = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
$bdCommText = $link->escape_string($post['bdCommText']);

// validate POST data, if it's empty bump user back to form
if (!count($post)) {
    $_SESSION['errorMsg'] = 'No data received. Your upload may be too large';
    header('Location: /newBartDef.php');
    exit;
}

// check for attachment and prepare Upload object
if ($_FILES['attachment']['size']
    && $_FILES['attachment']['name']
    && $_FILES['attachment']['tmp_name']
    && $_FILES['attachment']['type']) {
    $folder = 'uploads/bartdlUploads';
    $attachmentKey = 'attachment';
}
$fieldList = preg_replace('/\s+/', '', file_get_contents('bartdl.sql')) . ',date_created';
$fieldsArr = array_fill_keys(explode(',', $fieldList), '?');
// unset keys that will not be INSERT'd
unset($fieldsArr['id']);
$fieldList = implode(',', array_keys($fieldsArr));
$sql = 'INSERT INTO BARTDL ('.implode(', ', array_keys($fieldsArr)).') VALUES ('.implode(', ', array_values($fieldsArr)).')';

try {
    if (!$stmt = $link->prepare($sql)) throw new Exception($link->error);
} catch (Exception $e) {
    printException($e);
}

try {
    // append keys that do not or may not come from html form
    $post['updated_by'] = $_SESSION['userID'];
    $post['resolution_disputed'] = !empty($post['resolution_disputed']) ? $post['resolution_disputed'] : 0;
    $post['structural'] = !empty($post['structural']) ? $post['structural'] : 0;

    // escape anything that's an open text field
    $post['descriptive_title_vta'] = $link->escape_string($post['descriptive_title_vta']);
    $post['root_prob_vta'] = $link->escape_string($post['root_prob_vta']);
    $post['resolution_vta'] = $link->escape_string($post['resolution_vta']);
    $post['id_bart'] = $link->escape_string($post['id_bart']);
    $post['description_bart'] = $link->escape_string($post['description_bart']);
    $post['cat1_bart'] = $link->escape_string($post['cat1_bart']);
    $post['cat2_bart'] = $link->escape_string($post['cat2_bart']);
    $post['cat3_bart'] = $link->escape_string($post['cat3_bart']);
    $post['level_bart'] = $link->escape_string($post['level_bart']);

    $types = 'iiiiiisssiiiiisssssssss';
    if (!$stmt->bind_param($types,
        $post['created_by'],
        $post['created_by'],
        $post['creator'],
        $post['next_step'],
        $post['bic'],
        $post['status'],
        $post['descriptive_title_vta'],
        $post['root_prob_vta'],
        $post['resolution_vta'],
        $post['priority_vta'],
        $post['agree_vta'],
        $post['safety_cert_vta'],
        $post['resolution_disputed'],
        $post['structural'],
        $post['id_bart'],
        $post['description_bart'],
        $post['cat1_bart'],
        $post['cat2_bart'],
        $post['cat3_bart'],
        $post['level_bart'],
        $post['dateOpen_bart'],
        $post['dateClose_bart'],
        $date
    )) throw new mysqli_sql_exception($stmt->error);

    if (!$stmt->execute()) {
        throw new mysqli_sql_exception($stmt->error);
    }
    $defID = intval($stmt->insert_id);
    $stmt->close();

    $location = "def.php?bartDefID=$defID";

    // insert comment if one was submitted
    if ($bdCommText) {
        $sql = "INSERT bartdlComments (bdCommText, userID, bartdlID) VALUES (?, ?, ?)";
        $types = 'sii';
        if (!$stmt = $link->prepare($sql)) {
            http_response_code(500);
            throw new mysqli_sql_exception($link->error);
        }
        if (!$stmt->bind_param($types,
            $bdCommText,
            $userID,
            $defID)) {
                http_response_code(500);
                throw new mysqli_sql_exception($stmt->error);
        }
        if (!$stmt->execute()) {
            http_response_code(500);
            throw new mysqli_sql_exception($stmt->error);
        }
        $stmt->close();
    }

    // upload and insert attachment if found
    if ($attachmentKey) uploadAttachment($link, $attachmentKey, $folder, $defID);
    
    $location = '/defs.php?view=BART';
} catch (\Exception $e) {
    $location = '/newBartDef.php';
    $_SESSION['errorMsg'] = $e->getMessage();
} finally {
    header("Location: $location");
    if (is_a($link, 'mysqli')) $link->close();
    exit;
}

$link->close();

if ($location) {
    header("Location:$location");
} else {
    print "
        <p><a href='defs.php?view=BART'>back to DisplayDefs</a></p>
        <p><a href='newBartDef.php'>back to newBartDef</a></p>";
}
exit();
