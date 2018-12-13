<?php
use Mailgun\Mailgun;
use SVBX\WindowHack;

include 'vendor/autoload.php';
require 'session.php';
include 'uploadImg.php';

// prepare POST and sql string for commit
$post = $_POST; // TODO instantiate Deficiency object right here, then use its methods to validate data and update db
$defID = $post['defID'];
$userID = $_SESSION['userID'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// validate POST data
// if it's empty then file upload exceeds post_max_size
// bump user back to form
if (!count($post) || empty($defID)) {
    WindowHack::goBack('No data received. Did you try to upload a file that was larger than 4 MB?');
    exit;
}

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
$cdlCommText = trim($post['cdlCommText']);

// unset keys that will not be updated before imploding back to string
unset(
    $post['defID'],
    $post['cdlCommText']
);

// if Closed, set dateClosed
// if Closure Requested, record by whom
if ($post['status'] === '2') { // TODO: closure needs to be checked against db before new dateClosed is assigned
    $post['dateClosed'] = date('Y-m-d');
} elseif ($post['status'] === '1') {
    $closureReq = $post['closureRequested'] = 0;
    $closeReqBy = $post['closureRequestedBy'] = null;
} elseif ($post['status'] === '4') {
    $post['status'] = 1;
    $closureReq = $post['closureRequested'] = 1;
    $post['closureRequestedBy'] = $_SESSION['userid'];
    $closeReqBy = $_SESSION['firstname'].' '.$_SESSION['lastname'];
}

// append keys that do not or may not come from html form
// or whose values may be ambiguous in $_POST (e.g., checkboxes)
$post['updated_by'] = $username;

try {
    foreach ($post as $key => $val) {
        $post[$key] = trim($val);
    }
    $post = filter_var_array($post, FILTER_SANITIZE_SPECIAL_CHARS); // TODO instantiate Deficiency object right here, then use its methods to validate data and update db

    $link = new MysqliDb(DB_CREDENTIALS);
    // update CDL table
    $link->where('defID', $defID);
    $link->update('CDL', $post);

    // if INSERT succesful, prepare, upload, and INSERT photo
    if ($CDL_pics) {
        // execute save image and hold onto its new file path
        try {
            $pathToFile = saveImgToServer($_FILES['CDL_pics'], $defID);

            $fileData = [
                'pathToFile' => $pathToFile,
                'defID' => $defID
            ];

            $link->insert('CDL_pics', $fileData);
        } catch (uploadException $e) {
            header("Location: updateDef.php?defID=$defID");
            $_SESSION['errorMsg'] = "There was an error uploading your file: $e";
        } catch (Exception $e) {
            header("Location: updateDef.php?defID=$defID");
            $_SESSION['errorMsg'] = "There was a problem recording your file: $e";
        }
    }

    // if comment submitted commit it to a separate table
    if (strlen($cdlCommText)) {
        // $sql = "INSERT cdlComments (defID, cdlCommText, userID) VALUES (?, ?, ?)";
        try {
            $commentData = [
                'defID' => $defID,
                'cdlCommText' => $cdlCommText,
                'userID' => $userID
            ];

            $link->insert('cdlComments', $commentData);
        } catch (Exception $e) {
            header("Location: updateDef.php?defID=$defID");
            error_log($e);
            $_SESSION['errorMsg'] = "There was a problem recording your comment: $e";
        }
    }
    
    // if closure requested, try to email system lead    
    if (!empty($closureReq)) {
        // instantiate new mailgun client
        $mgClient = new Mailgun($mailgunKey);
        $domain = $mailgunDomain;

        if (!empty($post['groupToResolve'])) {
            $systemID = $post['groupToResolve'];
        } else {
            $link->where('defID', $defID);
            $systemID = $link->getValue('CDL', 'groupToResolve');
        }
        $link->join('users_enc u', 's.lead = u.userid', 'LEFT');
        $link->where('systemID', $systemID);
        $result = $link->getOne('system s', ['email', 'systemName']);
        $systemName = $result['systemName'];
        if ($result['email']) {
            // use mailgun to email sys lead
            $msg = "$closeReqBy has requested deficiency number $defID be closed."
                ."\nView this deficiency at "
                ."https://{$_SERVER['HTTP_HOST']}/defs.php?search=1&groupToResolve=$systemID&closureRequested=1";
            
            $mgClient->sendMessage($domain, [
                'from' => 'no_reply@mail.svbx.org',
                'to' => $result['email'],
                'subject' => "New closure request for your system: $systemName",
                'text' => $msg
            ]);
        }
    }

    header("Location: viewDef.php?defID=$defID");
} catch (Exception $e) {
    header("Location: updateDef.php?defID=$defID");
    $_SESSION['errorMsg'] = "There was an error in committing your submission: $e";
} catch (Error $e) {
    $_SESSION['errorMsg'] = "There was an error in committing your submission: $e";
    WindowHack::goBack();
} finally {
    if (is_a($link, 'MysqliDb')) $link->disconnect();
    exit;
}
