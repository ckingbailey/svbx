<?php
use SVBX\Deficiency;

require 'vendor/autoload.php';
require 'session.php';

/* TODO:
**  > instantiate new Def from defID
**  > pass Def data to Twig template
*/
if (empty($_GET) || empty($_GET['defID'])) {
    header('We need a deficiency ID number to show you anything', true, 400);
    exit;
}

$defID = intval($_GET['defID']);

$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader, [ 'debug' => true ]);
$twig->addExtension(new Twig_Extension_Debug());

$filter_decode = new Twig_Filter('safe', function($str) {
    return html_entity_decode($str, ENT_QUOTES);
});
$twig->addFilter($filter_decode);    

$context = [
    'title' => "Clone deficiency no. $defID"
];

try {
    $def = new Deficiency($defID);

    $context['data'] = $def->getReadable();
    // echo "<pre>" . print_r($def->getReadable(), true) . "</pre>";
    $twig->display('def.html.twig', $context);
} catch (Exception $e) {
    error_log($e);
} finally {
}
