<?php
require 'vendor/autoload.php';
require 'session.php';

use SVBX\Deficiency;
// include('SQLFunctions.php');
// include('html_components/defComponents.php');
// include('html_functions/bootstrapGrid.php');
// $link = f_sqlConnect();
// $Role = $_SESSION['role'];
// $title = "SVBX - New Deficiency";
if ($_SESSION['role'] <= 10) {
    error_log('Unauthorized client tried to access newdef.php from ' . $_SERVER['HTTP_ORIGIN']);
    header('This is not for you', true, 403);
    exit;
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
$twig->addFilter($html_sanitize_decode);
$twig->addFilter($filter_stripslashes);
    
$context = [
    'session' => $_SESSION,
    'pageHeading' => "Add New Deficiency",
    'formAction' => 'RecDef.php'
];

try {
    $context['options'] = Deficiency::getLookupOptions();

    $twig->display('defForm.html.twig', $context);
} catch (Exception $e) {
    error_log($e);
} finally {
    if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
    exit;
}
// include('filestart.php');

// $elements = $requiredElements + $optionalElements + $closureElements;

// $requiredRows = [
//     'Required Information',
//     [
//         'options' => [ 'inline' => true ],
//         $elements['safetyCert'],
//         $elements['systemAffected']
//     ],
//     [
//         'options' => [ 'inline' => true ],
//         $elements['location'],
//         $elements['specLoc']
//     ],
//     [
//         'options' => [ 'inline' => true ],
//         $elements['status'],
//         $elements['severity']
//     ],
//     [
//         'options' => [ 'inline' => true ],
//         $elements['dueDate'],
//         $elements['groupToResolve']
//     ],
//     [
//         'options' => [ 'inline' => true ],
//         $elements['requiredBy'],
//         $elements['contractID']
//     ],
//     [
//         'options' => [ 'inline' => true ],
//         $elements['identifiedBy'],
//         $elements['defType']
//     ],
//     [
//         $elements['description']
//     ]
// ];

// $optionalRows = [
//     'Optional Information',
//     [
//         'options' => [ 'inline' => true ],
//         $elements['spec'],
//         $elements['actionOwner']
//     ],
//     [
//         'options' => [ 'inline' => true ],
//         $elements['oldID'],
//         $elements['CDL_pics']
//     ],
//     [
//         $elements['cdlCommText']
//     ]
// ];

// $closureRows = [
//     'Closure Information',
//     [
//         'options' => [ 'inline' => true ],
//         $elements['evidenceType'],
//         $elements['repo'],
//         $elements['evidenceLink']
//     ],
//     [
//         $elements['closureComments']
//     ]
// ];

// echo "
//     <header class='container page-header'>
//         <h1 class='page-title'>Add New Deficiency</h1>
//     </header>
//     <main role='main' class='container main-content'>
//         <form action='RecDef.php' method='POST' enctype='multipart/form-data'>
//             <input type='hidden' name='username' value='{$_SESSION['username']}' />";

//         foreach ([$requiredRows, $optionalRows, $closureRows] as $rowGroup) {
//             $rowName = array_shift($rowGroup);
//             $content = iterateRows($rowGroup);
//             printSection($rowName, $content);
//         }

// echo "
//         <div class='center-content'>
//             <button type='submit' value='submit' class='btn btn-primary btn-lg'>Submit</button>
//             <button type='reset' value='reset' class='btn btn-primary btn-lg'>Reset</button>
//         </div>
//     </form>
// </main>";

// $link->close();
// include('fileend.php');
// ?>
