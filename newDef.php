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

// if POST data rec'd, try to INSERT new Def in db
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $def = new Deficiency($_POST['id'], $_POST);

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
            $link = new MysqliDb(DB_CREDENTIALS);
            $table = 'CDL_pics';
            $fields = [
                'defID' => $def->get('id'),
                'pathToFile' => null
            ];
            
            $fields['pathToFile'] = saveImgToServer($CDL_pics, $fields['defID']); // TODO: if defID is missing, throw error
            $fields['pathToFile'] = filter_var($fields['pathToFile'], FILTER_SANITIZE_SPECIAL_CHARS);
            if ($fields['pathToFile']) {
                if (!$link->insert($table, $fields))
                    $_SESSION['errorMsg'] = "There was a problem adding new picture: {$link->getLastError()}";
            }
        }
        
        // if comment submitted commit it to a separate table
        if (strlen($_POST['cdlCommText'])) {
            $link = (!empty($link) && is_a($link, 'MysqliDb'))
                ? $link
                : new MysqliDb(DB_CREDENTIALS);
            $table = 'cdlComments';
            $fields = [
                'defID' => $def->get('id'),
                'cdlCommText' => trim(filter_var($_POST['cdlCommText'], FILTER_SANITIZE_SPECIAL_CHARS)),
                'userID' => $_SESSION['userID']
            ];
            
            if ($fields['cdlCommText'])
                if (!$link->insert($table, $fields))
                    $_SESSION['errorMsg'] = "There was a problem adding new comment: {$link->getLastError()}";
        }

        header("location: /viewDef.php?defID={$def->get('id')}");
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
    } catch (\Error $e) {
        error_log($e);
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
        error_log("{$_SERVER['PHP_SELF']} tried to fetch a non-existent Deficiency\n$e");
    }
}

// instantiate Twig
$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader, [ 'debug' => $_ENV['PHP_ENV'] === 'dev' ]);
if ($_ENV['PHP_ENV'] === 'dev') $twig->addExtension(new Twig_Extension_Debug());

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
