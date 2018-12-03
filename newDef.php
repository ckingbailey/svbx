<?php
require 'vendor/autoload.php';
require 'session.php';

use SVBX\Deficiency;

if ($_SESSION['role'] <= 10) {
    error_log('Unauthorized client tried to access newdef.php from ' . $_SERVER['HTTP_ORIGIN']);
    header('This is not for you', true, 403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $def = new Deficiency($_POST['defID'], $_POST);
        $def->set('created_by', $_SESSION['username']);
        $def->insert();
        header("location: /viewDef.php?defID={$def->get('ID')}");
        exit;
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
