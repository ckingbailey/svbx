<?PHP
use SVBX\WindowHack;

// include('SQLFunctions.php');
require 'vendor/autoload.php';
require 'session.php';
include('uploadImg.php');


$date = date('Y-m-d');
$userID = intval($_SESSION['userID']);
$username = $_SESSION['username'];
$nullVal = null;

$link = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);

// prepare POST and sql string for commit
$post = array( // TODO instantiate Deficiency object right here, then use its methods to validate data and update db
  'safetyCert' => intval($_POST['safetyCert']),
  'systemAffected' => intval($_POST['systemAffected']),
  'location' => intval($_POST['location']),
  'specLoc' => filter_var($_POST['specLoc'], FILTER_SANITIZE_SPECIAL_CHARS),
  'status' => intval($_POST['status']),
  'severity' => intval($_POST['severity']),
  'dueDate' => filter_var($_POST['dueDate'], FILTER_SANITIZE_SPECIAL_CHARS),
  'groupToResolve' => intval($_POST['groupToResolve']),
  'requiredBy' => intval($_POST['requiredBy']),
  'contractID' => intval($_POST['contractID']),
  'identifiedBy' => filter_var($_POST['identifiedBy'], FILTER_SANITIZE_SPECIAL_CHARS),
  'defType' => intval($_POST['defType']),
  'description' => filter_var($_POST['description'], FILTER_SANITIZE_SPECIAL_CHARS),
  'spec' => filter_var($_POST['spec'], FILTER_SANITIZE_SPECIAL_CHARS),
  'actionOwner' => filter_var($_POST['actionOwner'], FILTER_SANITIZE_SPECIAL_CHARS),
  'oldID' => filter_var($_POST['oldID'], FILTER_SANITIZE_SPECIAL_CHARS),
  'evidenceType' => intval($_POST['evidenceType']),
  'repo' => intval($_POST['repo']),
  'evidenceLink' => filter_var($_POST['evidenceLink'], FILTER_SANITIZE_SPECIAL_CHARS),
  'evidenceID' => filter_var($_POST['evidenceID'], FILTER_SANITIZE_SPECIAL_CHARS),
  'closureComments' => filter_var($_POST['closureComments'], FILTER_SANITIZE_SPECIAL_CHARS),
  'created_by' => $username,
  'dateCreated' => $date,
  'dateClosed' => $nullVal
);

// validate POST data
// if it's empty then file upload exceeds post_max_size
// bump user back to form
if (!count($post)) {
    WindowHack::goBack('No data received. Did you try to upload a file that was larger than 4 MB?');
    exit;
}

// if Status of new Def is 'closed', require [ evidenceType, repo, evidenceID ]
// if Closed, Validate fields required for closure [ evidenceType, repo, evidenceLink ]
if ($post['status'] === 2) {
    if (empty($post['evidenceType'])
    || empty($post['repo'])
    || empty($post['evidenceID'])) {
        WindowHack::goBack('Required data for closure was not received.');
        exit;
    }
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
        $commentText = filter_var($cdlCommText, FILTER_SANITIZE_SPECIAL_CHARS);
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
