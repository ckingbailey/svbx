<?php
require 'vendor/autoload.php';
require 'uploadImg.php';
require 'session.php';

use SVBX\Deficiency;

if ($_SESSION['role'] <= 10) {
    error_log('Unauthorized client tried to access newdef.php from ' . $_SERVER['HTTP_ORIGIN']);
    header('This is not for you', true, 403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log(sprintf('[%s](%s): %s',
        $_SERVER['PHP_SELF'],
        __LINE__,
        "POST data rec'd\n" . print_r($_POST, true)
    ));
    try {
        $def = new Deficiency($_POST['defID'], $_POST);

        error_log(sprintf('[%s](%s): %s',
            $_SERVER['PHP_SELF'],
            __LINE__,
            "Def instantiated\n" . $def
        ));

        $def->set('created_by', $_SESSION['username']);
        $def->insert();

        // if INSERT succesful, prepare, upload, and INSERT photo
        // TODO: make all this one transcaction handled by the Deficiency object
        // TODO: create classes for Comment and Attachment
        if ($_FILES['CDL_pics']['size']
            && $_FILES['CDL_pics']['name']
            && $_FILES['CDL_pics']['tmp_name']
            && $_FILES['CDL_pics']['type'])
        {
            $CDL_pics = $_FILES['CDL_pics'];
        } else $CDL_pics = null;

        if ($CDL_pics) {
            error_log(sprintf('[%s](%s): %s',
                $_SERVER['PHP_SELF'],
                __LINE__,
                "New pic rec'd\n" . print_r(($CDL_pics + [ 'defID' => $def->get('ID') ]), true)
            ));
            $link = new MysqliDb(DB_CREDENTIALS);
            error_log("link instantiated " . is_a($link, 'MysqliDb'));
            $table = 'CDL_pics';
            $fields = [
                'defID' => $def->get('ID'),
                'pathToFile' => null
            ];
            
            // TODO: this can fail silently. Why? Get better error handling here
            error_log("Try to invoke saveImgToServer");
            $fields['pathToFile'] = saveImgToServer($CDL_pics, $fields['defID']);
            error_log("[{$_SERVER['PHP_SELF']}]" . "(" . __LINE__ . ") Unescaped path to file: {$fields['pathToFile']}");
            $fields['pathToFile'] = filter_var($fields['pathToFile'], FILTER_SANITIZE_SPECIAL_CHARS);
            error_log("[{$_SERVER['PHP_SELF']}]" . "(" . __LINE__ . ") Escaped path to file: {$fields['pathToFile']}");
            if ($fields['pathToFile']) {
                error_log(sprintf('[%s](%s): %s',
                    $_SERVER['PHP_SELF'],
                    __LINE__,
                    "New pic data:\n" . print_r($fields, true)
                ));
                if ($newPicID = $link->insert($table, $fields)) {
                    error_log('New pic successfully inserted: ' . $newPicID);
                }
            }
        }
        
        // if comment submitted commit it to a separate table
        // if (strlen($_POST['cdlCommText'])) {
        //     $table = 'cdlComments';
        //     $fields = [
        //         'defID' => $def->ID,
        //         'commentText' => null,
        //         'userID' => $_SESSION['userID']
        //     ];
            
        //     if ($fields['commentText'] = filter_var($cdlCommText, FILTER_SANITIZE_SPECIAL_CHARS))
        //         $link->insert($table, $fields);
        // }

        header("location: /viewDef.php?defID={$def->get('ID')}");
    } catch (\Exception $e) {
        error_log($e);
        $_SESSION['errorMsg'] = 'Something went wrong in trying to add your new deficiency: ' . $e->getMessage();
        $props = $def->get();
        $qs = array_reduce(array_keys($props), function ($acc, $key) use ($props) {
            if ($key === 'newPic' || $key === 'comments' || $key === 'pics') return $acc;
            $val = $props[$key];
            if ($key === 'ID') $key = 'defID';
            $joiner = empty($acc) ? '?' : '&';
            return $acc .= ("$joiner" . "$key=$val");
        }, '');
        header("location: /newDef.php$qs");
    } finally {
        if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && !empty($_GET)
    && !empty($_GET['defID'])
    && is_numeric($_GET['defID']))
{
    try {
        $defID = intval($_GET['defID']);
        $def = new Deficiency($defID);
        $def->set($_GET);
    } catch (\Exception $e) {
        error_log(
            "{$_SERVER['PHP_SELF']} tried to fetch a non-existent Deficiency\n"
            . $e);
    }
}

// instantiate Twig
$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader, [ 'debug' => $_ENV['PHP_ENV'] === 'dev' ]);
if ($_ENV['PHP_ENV'] === 'dev') $twig->addExtension(new Twig_Extension_Debug());

// add extra Twig filters
$filter_decode = new Twig_Filter('safe', function($str) {
    return html_entity_decode($str);
});
$twig->addFilter($filter_decode);    
    
$context = [
    'session' => $_SESSION,
    'title' => 'Create deficiency record',
    'pageHeading' => "Add New Deficiency",
    'formAction' => $_SERVER['PHP_SELF']
];    

if (!empty($_SESSION['errorMsg']))
    unset($_SESSION['errorMsg']);

try {
    $context['options'] = Deficiency::getLookupOptions();

    if (!empty($def) && is_a($def, 'SVBX\Deficiency')) {
        $def->set(Deficiency::MOD_HISTORY); // clear modification history
        $data = $def->get();
        $context['data'] = $data;
        $context['pageHeading'] = "Clone Deficiency No. {$data['ID']}";
    }

    $twig->display('defForm.html.twig', $context);
} catch (Exception $e) {
    error_log($e);
} finally {
    if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
    exit;
}
