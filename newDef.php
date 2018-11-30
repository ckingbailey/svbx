<?php
require 'vendor/autoload.php';
require 'session.php';

use SVBX\Deficiency;

if ($_SESSION['role'] <= 10) {
    error_log('Unauthorized client tried to access newdef.php from ' . $_SERVER['HTTP_ORIGIN']);
    header('This is not for you', true, 403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && !empty($_GET)
    && !empty($_GET['defID'])
    && is_numeric($_GET['defID']))
{
    try {
        $defID = intval($_GET['defID']);
        $def = new Deficiency($defID);
        $data = $def->getReadable();
    } catch (\Exception $e) {
        error_log(
            "{$_SERVER['PHP_SELF']} tried to fetch a non-existent Deficiency"
            . PHP_EOL
            . $e);
    }
}

// instantiate Twig
$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader, [ 'debug' => $_ENV['PHP_ENV'] === 'dev' ]);
if ($_ENV['PHP_ENV'] === 'dev') $twig->addExtension(new Twig_Extension_Debug());

if (!empty($_SESSION['errorMsg']))
    unset($_SESSION['errorMsg']);

// add extra Twig filters
$html_sanitize_decode = new Twig_Filter('html_sanitize_decode', function($str) {
    $decoded = html_entity_decode($str, ENT_QUOTES);
    return filter_var($decoded, FILTER_SANITIZE_SPECIAL_CHARS);
});    
$filter_stripslashes = new Twig_Filter('unescape', function($str) {
    return stripcslashes($str);
});    
$filter_decode = new Twig_Filter('safe', function($str) {
    return html_entity_decode($str);
});
$twig->addFilter($filter_decode);    
$twig->addFilter($html_sanitize_decode);
$twig->addFilter($filter_stripslashes);
    
$context = [
    'session' => $_SESSION,
    'title' => 'Create deficiency record',
    'pageHeading' => "Add New Deficiency",
    'formAction' => 'RecDef.php'
];

try {
    $context['options'] = Deficiency::getLookupOptions();
    $context['data'] = !empty($data) ? $data : null;

    $twig->display('defForm.html.twig', $context);
} catch (Exception $e) {
    error_log($e);
} finally {
    if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
    exit;
}
