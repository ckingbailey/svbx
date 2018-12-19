<?php
require 'vendor/autoload.php';
require 'session.php';

if ($_SESSION['role'] <= 10) {
    header("This is not for you", true, 403);
    exit;
}

$get = !empty($_GET) ? filter_input_array(INPUT_GET, FILTER_SANITIZE_NUMBER_INT) : null;

list(
    $class,
    $id,
    $commentTable,
    $commentTextField,
    $attachmentsTable,
    $pathField,
    $templatePath
) = (!empty($get['defID'])
    ? [
        'SVBX\Deficiency',
        $get['defID'],
        'cdlComments',
        'cdlCommText',
        'CDL_pics',
        'pathToFile',
        'defForm.html.twig'
        ]
        : (!empty($get['bartDefID'])
        ? [
            'SVBX\BARTDeficiency',
            $get['bartDefID'],
            'bartdlComments',
            'bdCommText',
            'bartdlAttachments',
            'bdaFilepath',
            'bartForm.html.twig'
          ]
        : array_fill(0, 7, null)));

$context = [
    'session' => $_SESSION,
    'title' => "Update deficiency no. $id",
    'pageHeading' => "Update Deficiency No. $id",
    'formAction' => 'updateDefCommit.php'
];

if (!empty($_SESSION['errorMsg']))
    unset($_SESSION['errorMsg']);

try {
    $context['options'] = SVBX\Deficiency::getLookUpOptions();

    // TODO: show special contractor options
    $def = new $class($id);
    $context['data'] = $def->get();
    
    $link = new MySqliDB(DB_CREDENTIALS);
    // query for comments associated with this Def
    $link->join('users_enc u', "$commentTable.userID = u.userID");
    $link->orderBy("$commentTable.date_created", 'DESC');
    $link->where(($class === 'SVBX\Deficiency' ? 'defID' : 'bartdlID'), $id); // this is necessary because the name of the BART id field is different on the bartDef table and the comment table
    $context['data']['comments'] = $link->get($commentTable, null, [ "$commentTextField as commentText", 'date_created', "CONCAT(firstname, ' ', lastname) as userFullName" ]);

    // query for photos linked to this Def
    // keep BART | Project, photos | attachments separate for now
    // to leave room for giving photos or attachments to either of those data types in the future
    if (!empty($get['defID'])) {
        $link->where('defID', $id);
        $photos = $link->get($attachmentsTable, null, "$pathField as filepath");
        $context['data']['photos'] = array_chunk($photos, 3);
    }
    if (!empty($get['bartDefID'])) {
        $link->where('bartdlID', $id);
        $context['data']['attachments'] = $link->get($attachmentsTable, null, "$pathField as filepath");
    }

    // instantiate Twig
    $loader = new Twig_Loader_Filesystem('./templates');
    $twig = new Twig_Environment($loader, [ 'debug' => $_ENV['PHP_ENV'] === 'dev' ]);
    $twig->addExtension(new Twig_Extension_Debug());

    $twig->display($templatePath, $context);
} catch (Twig_Error $e) {
    echo "Unable to render template";
    error_log($e);
} catch (Exception $e) {
    echo "Unable to retrieve record";
    error_log($e);
} finally {
    if (!empty($link) && is_a($link, 'MySqliDB')) $link->disconnect();
    exit;
}
