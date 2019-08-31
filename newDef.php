<?php
require 'vendor/autoload.php';
require 'session.php';

use SVBX\Deficiency;

if ($_SESSION['role'] <= 10
    || (empty($_SESSION['bdPermit'])
    && ($_POST['class'] === 'bart' || $_GET['class' === 'bart']))) {
    error_log('Unauthorized client tried to access newdef.php from ' . $_SERVER['HTTP_ORIGIN']);
    header('This is not for you', true, 403);
    exit;
}

// if POST data rec'd, try to INSERT new Def in db
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $class = sprintf('SVBX\%sDeficiency', $_POST['class']);
    $createdByField = $_POST['class'] === 'bart' ? 'userID' : 'username';
    try {
        $ref = new ReflectionClass($class);
        $def = $ref->newInstanceArgs([ $id, $_POST ]);

        $def->set('created_by', $_SESSION[$createdByField]);
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
            require 'uploadImg.php';
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
        
        if ($_FILES['attachment']['size']
            && $_FILES['attachment']['name']
            && $_FILES['attachment']['tmp_name']
            && $_FILES['attachment']['type'])
        {
            $attachment = $_FILES['attachment'];
        } else $attachment = null;

        if ($attachment) {
            require('uploadAttachment.php');
            $link = (!empty($link) && is_a($link, 'MysqliDb'))
                ? $link
                : new MysqliDb(DB_CREDENTIALS);
            uploadAttachment($link, 'attachment', 'uploads/bartdlUploads', $def->get('id'));
        }

        // if comment submitted commit it to a separate table
        if (strlen($_POST['comment'])) {
            $link = (!empty($link) && is_a($link, 'MysqliDb'))
                ? $link
                : new MysqliDb(DB_CREDENTIALS);
            list($table, $commentField, $defID) = [
                $def->commentsTable['table'],
                $def->commentsTable['field'],
                $def->commentsTable['defID']
            ];
            $fields = [
                $defID => $def->get('id'),
                $commentField => trim(filter_var($_POST['comment'], FILTER_SANITIZE_SPECIAL_CHARS)),
                'userID' => $_SESSION['userID']
            ];
            
            if ($fields[$commentField])
                if (!$link->insert($table, $fields))
                    $_SESSION['errorMsg'] = "There was a problem adding new comment: {$link->getLastError()}";
        }
        $location = '/def.php';
        $qs = '?' . ($class === 'SVBX\Deficiency'
            ? 'defID' : 'bartDefID')
            . "={$def->get('id')}";
        header("location: /def.php{$qs}");
    } catch (\ReflectionException $e) {
        error_log($e);
        header("No Class found for the deficiency type $class", true, 400);
    } catch (\Exception $e) {
        error_log($e);
        $_SESSION['errorMsg'] = 'Something went wrong in trying to add your new deficiency: ' . $e->getMessage();
        $location = '/newDef.php';
        $qs = '?' . http_build_query($def->get());
        header("Location: $location{$qs}");
    } catch (\Error $e) {
        error_log($e);
    } finally {
        if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $getClass = !empty($_GET['class']) ? $_GET['class'] : '';
    $class = sprintf('SVBX\%sDeficiency', $getClass);
    $template = $getClass === 'bart' ? 'bartForm.html.twig' : 'defForm.html.twig';
    $pageHeading = '';

    if (!empty($_GET['id'])) {
        try {
            $defID = intval($_GET['id']);
            $ref = new ReflectionClass($class);
            $def = $ref->newInstanceArgs([$defID]);
            $def->set($_GET);
            $pageHeading = "Clone Deficiency No. {$_GET['id']}";
        } catch (\ReflectionException $e) {
            error_log($e);
            header("No Class found for the deficiency type $class", true, 400);
        } catch (\Exception $e) {
            error_log("{$_SERVER['PHP_SELF']} tried to fetch a non-existent Deficiency\n$e");
        }
    } elseif (!empty($_GET['descriptive_title_vta'])) {
        try {
            $ref = new ReflectionClass($class);
            $def = $ref->newInstanceArgs([null, $_GET]);
        } catch (\ReflectionException $e) {
            error_log($e);
            header("No Class found for the deficiency type $class", true, 400);
            exit;
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
        'title' => 'Add new deficiency',
        'pageHeading' => $pageHeading ?: "Add New Deficiency",
        'formAction' => $_SERVER['PHP_SELF']
    ];    

    if (!empty($_SESSION['errorMsg']))
        unset($_SESSION['errorMsg']);

    try {
        $context['options'] = $class::getLookupOptions();
        error_log(print_r($context['options'], true));

        if (!empty($def) && is_a($def, $class)) {
            $def->set($class::MOD_HISTORY); // clear modification history
            $data = $def->get();
            $context['data'] = $data;
        }

        $twig->display($template, $context);
    } catch (Exception $e) {
        error_log($e);
    } finally {
        if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
        exit;
    }
} else header("What do you think you're doing?", true, 400);
exit;
