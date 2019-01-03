<?php
require 'vendor/autoload.php';
require 'session.php';

if (!empty($_GET['bartDefID']) && !$_SESSION['bdPermit']) {
    header("This is not for you", true, 403);
    exit;
}

if (empty($_GET['bartDefID']) && empty($_GET['defID'])) {
    header("We need a deficiency ID to show you anything", true, 400);
    exit;
}

if (!empty($_GET)) $get = filter_input_array(INPUT_GET, FILTER_SANITIZE_NUMBER_INT);

// TODO: move these fields and queries into the Deficiency class
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
        'def.html.twig'
        ]
        : (!empty($get['bartDefID'])
            ? [
                'SVBX\BARTDeficiency',
                $get['bartDefID'],
                'bartdlComments',
                'bdCommText',
                'bartdlAttachments',
                'bdaFilepath',
                'bartDef.html.twig'
            ]
            : array_fill(0, 7, null)));
// TODO: handle case of no def ID

$context = [
    'session' => $_SESSION,
    'title' => "Deficiency no. $id",
    'pageHeading' => "Deficiency No. $id",
];

try {
    $def = new $class($id);
    $context['data'] = $def->getReadable();
    
    if (strcasecmp($context['data']['status'], "open") === 0) {
        $color = "bg-red text-white";
    } else {
        $color = "bg-green text-white";
    }
    
    // query for comments associated with this Def
    $link = new MySqliDB(DB_CREDENTIALS);
    $link->join('users_enc u', "$commentTable.userID = u.userID");
    $link->orderBy("$commentTable.date_created", 'DESC');
    $link->where(($class === 'SVBX\Deficiency' ? 'defID' : 'bartdlID'), $id); // this is necessary because the name of the BART id field is different on the bartDef table and the comment table
    $context['data']['comments'] = $link->get($commentTable, null, [ "$commentTextField as commentText", 'date_created', "CONCAT(firstname, ' ', lastname) as userFullName" ]);

    // query for photos linked to this Def
    // keep BART and Project photos | attachments separate for now
    // to leave room for giving photos or attachments to either of those data types in the future
    if (!empty($get['defID'])) {
        $link->where('defID', $id);
        $photos = $link->get($attachmentsTable, null, "$pathField as filepath");
        $context['data']['photos'] = array_chunk($photos, 3);
    } elseif (!empty($get['bartDefID'])) {
        $link->where('bartdlID', $id);
        $context['data']['attachments'] = $link->get($attachmentsTable, null, [ "$pathField as filepath", 'filename' ]);
    }
    
    // instantiate Twig
    $loader = new Twig_Loader_Filesystem('./templates');
    $twig = new Twig_Environment($loader, [ 'debug' => $_ENV['PHP_ENV'] === 'dev' ]);
    $twig->addExtension(new Twig_Extension_Debug());
    // $template = $twig->load();

    $twig->display($templatePath, $context);
} catch (Twig_Error $e) {
    echo "Unable to render template";
    error_log($e);
} catch (Exception $e) {
    echo "Unable to retrieve record";
    error_log($e);
} finally {
    if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
    exit;
}
