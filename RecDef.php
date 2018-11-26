<?PHP
session_start();
include('SQLFunctions.php');
include('uploadImg.php');

$date = date('Y-m-d');
$userID = intval($_SESSION['userID']);
$username = $_SESSION['username'];
$nullVal = null;

$link = f_sqlConnect();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

        
// prepare POST and sql string for commit
$post = array(
  'safetyCert' => intval($_POST['safetyCert']),
  'systemAffected' => intval($_POST['systemAffected']),
  'location' => intval($_POST['location']),
  'specLoc' => filter_var($link->escape_string($_POST['specLoc']), FILTER_SANITIZE_STRING),
  'status' => intval($_POST['status']),
  'severity' => intval($_POST['severity']),
  'dueDate' => filter_var($link->escape_string($_POST['dueDate']), FILTER_SANITIZE_STRING),
  'groupToResolve' => intval($_POST['groupToResolve']),
  'requiredBy' => intval($_POST['requiredBy']),
  'contractID' => intval($_POST['contractID']),
  'identifiedBy' => filter_var($link->escape_string($_POST['identifiedBy']), FILTER_SANITIZE_STRING),
  'defType' => intval($_POST['defType']),
  'description' => filter_var($link->escape_string($_POST['description']), FILTER_SANITIZE_STRING),
  'spec' => filter_var($link->escape_string($_POST['spec']), FILTER_SANITIZE_STRING),
  'actionOwner' => filter_var($link->escape_string($_POST['actionOwner']), FILTER_SANITIZE_STRING),
  'oldID' => filter_var($link->escape_string($_POST['oldID']), FILTER_SANITIZE_STRING),
  'evidenceType' => intval($_POST['evidenceType']),
  'repo' => intval($_POST['repo']),
  'evidenceLink' => filter_var($link->escape_string($_POST['evidenceLink']), FILTER_SANITIZE_STRING),
  'evidenceID' => filter_var($link->escape_string($_POST['evidenceID']), FILTER_SANITIZE_SPECIAL_CHARS),
  'closureComments' => filter_var($link->escape_string($_POST['closureComments']), FILTER_SANITIZE_STRING),
  'created_by' => $username,
  'dateCreated' => $date,
  'dateClosed' => $nullVal
);

// validate POST data
// if it's empty then file upload exceeds post_max_size
// bump user back to form
if (!count($post)) {
    include('js/emptyPostRedirect.php');
    exit;
}

// if photo in POST it will be committed to a separate table
if ($_FILES['CDL_pics']['size']
    && $_FILES['CDL_pics']['name']
    && $_FILES['CDL_pics']['tmp_name']
    && $_FILES['CDL_pics']['type']) {
    $CDL_pics = $_FILES['CDL_pics'];
} else $CDL_pics = null;

// hold onto comments separately
$cdlCommText = trim($_POST['cdlCommText']);
    
// prepare parameterized string from external .sql file
$fieldList = preg_replace('/\s+/', '', file_get_contents('updateDef.sql'));
$fieldsArr = array_fill_keys(explode(',', $fieldList), '?');

// unset keys that will not be updated before imploding back to string
unset(
    $fieldsArr['defID'],
    $fieldsArr['updated_by'],
    $fieldsArr['lastUpdated']
);

$assignmentList = implode(' = ?, ', array_keys($fieldsArr)).' = ?';
$sql = 'INSERT INTO CDL ('
  . implode(', ', array_keys($post))
  . ') VALUES ('
  . implode(',', array_fill(0, count($post), '?'))
  . ')';

// append keys that do not or may not come from html form
// or whose values may be ambiguous in $_POST (e.g., checkboxes)
$post += ['created_by' => $username];

// if photo in POST it will be committed to a separate table
if ($_FILES['CDL_pics']['size']
    && $_FILES['CDL_pics']['name']
    && $_FILES['CDL_pics']['tmp_name']
    && $_FILES['CDL_pics']['type']) {
    $CDL_pics = $_FILES['CDL_pics'];
} else $CDL_pics = null;

try {
    if (!$stmt = $link->prepare($sql)) throw new Exception($link->error);
    
    if (!$stmt->bind_param('iiisiisiiisissssiissssss',
        $post['safetyCert'],
        $post['systemAffected'],
        $post['location'],
        $post['specLoc'],
        $post['status'],
        $post['severity'],
        $post['dueDate'],
        $post['groupToResolve'],
        $post['requiredBy'],
        $post['contractID'],
        $post['identifiedBy'],
        $post['defType'],
        $post['description'],
        $post['spec'],
        $post['actionOwner'],
        $post['oldID'],
        $post['evidenceType'],
        $post['repo'],
        $post['evidenceLink'],
        $post['evidenceID'],
        $post['closureComments'],
        $post['created_by'],
        $post['dateCreated'],
        $nullVal
    )) throw new mysqli_sql_exception($stmt->error);
    
    if (!$stmt->execute()) throw new mysqli_sql_exception($stmt->error);
    
    $defID = intval($stmt->insert_id);
    
    $stmt->close();
    
    // if INSERT succesful, prepare, upload, and INSERT photo
    if ($CDL_pics) {
        $sql = "INSERT CDL_pics (defID, pathToFile) values (?, ?)";
        
        $pathToFile = $link->escape_string(saveImgToServer($_FILES['CDL_pics'], $defID));
        if ($pathToFile) {
            if (!$stmt = $link->prepare($sql)) throw new Exception($link->error);
            
            if (!$stmt->bind_param('is', $defID, $pathToFile)) throw new mysqli_sql_exception($stmt->error);
            
            if (!$stmt->execute()) throw new mysqli_sql_exception($stmt->error);
            
            $stmt->close();
        }
    }
    
    // if comment submitted commit it to a separate table
    if (strlen($cdlCommText)) {
        $sql = "INSERT cdlComments (defID, cdlCommText, userID) VALUES (?, ?, ?)";
        $commentText = filter_var(
            filter_var(
                $cdlCommText,
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            ), FILTER_SANITIZE_SPECIAL_CHARS);
        if (!$stmt = $link->prepare($sql)) throw new Exception($link->error);
        if (!$stmt->bind_param('isi',
            $defID,
            $commentText,
            $userID)) throw new mysqli_sql_exception($stmt->error);
        if (!$stmt->execute()) throw new mysqli_sql_exception($stmt->error);
        $stmt->close();
    }

    header("Location: viewDef.php?defID=$defID");
} catch (Exception $e) {
    print "There was an error in committing your submission";
} finally {
    $link->close();
    exit;
}
