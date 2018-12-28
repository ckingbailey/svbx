<?php
require 'vendor/autoload.php';
require 'session.php';

if ($_SESSION['role'] <= 10) {
    header("This is not for you", true, 403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: this should reject early if no ID
    // TODO: controller should take a generic `id` prop and an additional `class` prop to determine whether it's a BART Def
    error_log(__FILE__ . '(' . __LINE__ . ') POST data received ' . print_r($_POST, true));
    $id = intval($id);
    $class = sprintf('SVBX\%sDeficiency', $_POST['class']);
    $qs = '?' . ($_POST['class'] ? "class={$_POST['class']}&" : '' );
    $updatedByField = $_POST['class'] === 'bart' ? 'userid' : 'username';
    error_log(__FILE__ . '(' . __LINE__ . ') filtered ID: ' . $id . PHP_EOL . 'class name: ' . $class);
    try {
        $ref = new ReflectionClass($class);
        $def = $ref->newInstanceArgs([ $id, $_POST ]);
        $def->set('updated_by', $_SESSION[$updatedByField]);
        if (empty($id)) throw new Exception('No ID found for update request');

        $def->update();
        // if UPDATE succesful, prepare, upload, and INSERT photo
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
            
            // TODO: this can fail silently. Why? Get better error handling here
            $fields['pathToFile'] = saveImgToServer($CDL_pics, $fields['defID']);
            $fields['pathToFile'] = filter_var($fields['pathToFile'], FILTER_SANITIZE_SPECIAL_CHARS);
            if ($fields['pathToFile']) {
                if (!$link->insert($table, $fields))
                    $_SESSION['errorMsg'] = "There was a problem adding new picture: {$link->getLastError()}";
            }
        }
        
        // if comment submitted commit it to a separate table
        if (strlen($_POST['comment'])) {
            $link = (!empty($link) && is_a($link, 'MysqliDb'))
                ? $link
                : new MysqliDb(DB_CREDENTIALS);
            list($table, $commentField, $defID) = [
                $this->commentsTable['table'],
                $this->commentsTable['field'],
                $this->commentsTable['defID']
            ];
            $fields = [
                $defID => $def->get('id'),
                $commentField => trim(filter_var($_POST['comment'], FILTER_SANITIZE_SPECIAL_CHARS)),
                'userID' => $_SESSION['userID']
            ];
            
            if ($fields['comment'])
                if (!$link->insert($table, $fields))
                    $_SESSION['errorMsg'] = "There was a problem adding new comment: {$link->getLastError()}";
        }
        $location = '/def.php';
        $qs .= "id={$def->get('id')}";
    } catch (\ReflectionException $e) {
        error_log($e);
        header("No Class found for the deficiency type $class", true, 400);
    } catch (Exception $e) {
        error_log($e);
        $_SESSION['errorMsg'] = 'Something went wrong in trying to add your new deficiency: ' . $e->getMessage();
        $qs = '?' . http_build_query($def->get());
        $location = $_SERVER['PHP_SELF'];
    } catch (\Error $e) {
        error_log($e);
        $_SESSION['errorMsg'] = 'Something went wrong in trying to add your new deficiency: ' . $e->getMessage();
        $qs = '?' . http_build_query($def->get());
        $location = $_SERVER['PHP_SELF'];
    } finally {
        if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
        header("Location: $location{$qs}");
        exit;
    }
}

// TODO: this should fail early if no ID or if invalid class
try {
    if (empty($_GET['id'])) throw new Exception('No id received for update request form');
    if (empty($_GET)) throw new Exception('No data received for update request form');
    $class = 'SVBX\%sDeficiency';
    list($id, $defClass) = array_values(filter_input_array(INPUT_GET, [
        'id' => FILTER_SANITIZE_NUMBER_INT,
        'class' => FILTER_SANITIZE_SPECIAL_CHARS
    ]));
    unset($_GET['id']);
    unset($_GET['class']);
    $class = sprintf($class, strtoupper($defClass));
} catch (Exception $e) {
    error_log($e);
    header($e->getMessage(), true, 400);
    exit;
} catch (Error $e) {
    error_log($e);
    exit;
}

list(
    $title,
    $idField,
    $commentTable,
    $commentTextField,
    $attachmentsTable,
    $pathField,
    $templatePath
) = ($class === 'SVBX\Deficiency')
    ? [
        'Update deficiency no. ',
        'defID',
        'cdlComments',
        'cdlCommText',
        'CDL_pics',
        'pathToFile',
        'defForm.html.twig'
    ]
    : (($class === 'SVBX\BARTDeficiency')
        ? [
            'Update BART deficiency no. ',
            'bartdlID',
            'bartdlComments',
            'bdCommText',
            'bartdlAttachments',
            'bdaFilepath',
            'bartForm.html.twig'
          ]
        : array_fill(0, 8, null));

$context = [
    'session' => $_SESSION,
    'title' => $title . $id,
    'pageHeading' => ucwords($title) . $id,
    'formAction' => $_SERVER['PHP_SELF']
];

if (!empty($_SESSION['errorMsg']))
    unset($_SESSION['errorMsg']);

try {
    $context['options'] = $class::getLookUpOptions();

    // TODO: show special contractor options
    $ref = new ReflectionClass($class);
    $def = $ref->newInstanceArgs([ $id ]);
    if (!empty($_GET)) {
        $def->set($_GET);
    }
    $context['data'] = $def->getReadable($class::MOD_HISTORY);
    
    $link = new MySqliDB(DB_CREDENTIALS);
    // query for comments associated with this Def
    $link->join('users_enc u', "$commentTable.userID = u.userID");
    $link->orderBy("$commentTable.date_created", 'DESC');
    $link->where($idField, $id); // this is necessary because the name of the BART id field is different on the bartDef table and the comment table
    $context['data']['comments'] = $link->get($commentTable, null, [ "$commentTextField as commentText", 'date_created', "CONCAT(firstname, ' ', lastname) as userFullName" ]);

    // query for photos linked to this Def
    // keep BART | Project, photos | attachments separate for now
    // to leave room for giving photos or attachments to either of those data types in the future
    if (!empty($id)) {
        $link->where($idField, $id);
        $photos = $link->get($attachmentsTable, null, "$pathField as filepath");
        $context['data']['photos'] = array_chunk($photos, 3);
        $link->where($idField, $id);
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
} catch (Error $e) {
    echo "Unable to retrieve record";
    error_log($e);
} finally {
    if (!empty($link) && is_a($link, 'MySqliDB')) $link->disconnect();
    exit;
}
