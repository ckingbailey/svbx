<?php
use SVBX\Export;
use League\Csv\Writer;

require 'vendor/autoload.php';
require 'session.php';

/**
 * The way this ought to work:
 * Takes a GET request containing a list of fields as query string
 * For 1=>1 field names such as location, severity, status
 *   get deficiency view from database
 * For `comments`
 *   grab all comments text and their def.ids from database
 *   iterate over this comments collection
 *     transforming into an assoc array of [ def.id => "comment text" ]
 *     and combining comments text that share common def.id into single string with comments separated by "\n"
 *   then iterate over defs collection, matching def.id to comments array keys
 *     and appending comments text to 'comments' key of each def
 * Headings
 *   use field names passed in qs as headings
 */

// only if it's a POST will you get a 0 and bypass this statement
// if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST')) {
//     header('Access-Control-Allow-Methods: POST', true, 405);
//     exit;
// }

try {
    // check Session vars against DB
    $link = new MySqliDB(DB_CREDENTIALS);
    // $fields = [ 'username', 'userID', 'firstname', 'lastname', 'role' ];

    // $link->where('userID', $_SESSION['userID']);
    // $result = $link->getOne('users_enc', $fields);

    // if ($result['username'] !== $_SESSION['username']
    //     || $result['role'] !== $_SESSION['role']
    //     || $result['firstname'] !== $_SESSION['firstname']
    //     || $result['lastname'] !== $_SESSION['lastname'])
    // {
    //     header('Status: 403 Forbidden', true, 403);
    //     exit;
    // }

    // if Auth ok, validate fields on first data element of POST against fields in DB
    // note: element at index 0 is heading names, not table data
    // $link->where('table_name', 'CDL');
    // $link->orWhere('table_name', 'BARTDL');
    // $cols = $link->getValue('information_schema.columns', 'column_name', null); // returns 50+ columns
    // $cols = array_map('strtolower', $cols);
    
    // get raw POST input coz it's in JSON and PHP doesn't handle that well
    // $post = trim(file_get_contents('php://input'));
    // $post = json_decode($post, true);
    // error_log(print_r(array_slice($post, 0, 2), true));
    // $post = filter_var_array($post, FILTER_SANITIZE_SPECIAL_CHARS);

    // $postKeys = array_keys($post[1] + $post[count($post) - 1] + $post[floor((count($post) / 2))]); // grab keys from first, middle, and last element of post data

    // if (($idIndex = array_search('ID', $postKeys)) !== false) unset($postKeys[$idIndex]); // don't try to match name of ID col

    // compare POST keys to columns
    // foreach ($postKeys as $key) {
    //     if (array_search(strtolower($key), $cols) === false) {
    //         header('Status: 400 Bad Request', true, 400);
    //         exit;
    //     }
    // }

    // header('Content-Type: text/csv', true);

    error_log(print_r($_GET, true));
    if (empty($_GET['fields'])) {
        http_response_code(400);
        echo 'No data receieved';
        exit;
    }

    $fields = explode(',', $_GET['fields']);

    if (!empty($_GET['range'])) {
        $range = explode(',', $_GET['range']);
        if (count($range) > 2) {
            http_response_code(400);
            echo 'Range must be two comma-separted numbers';
            exit;
        }
        $from = min($range);
        $to = max($range);
        $link->where('id', $from, '>=');
    }

    $link->orderBy('id', 'ASC');
    $defs = $to ?
        $link->get('deficiency', $to, $fields)
        : $link->get('deficiency', $fields);

    error_log(print_r($defs, true));
    echo "<pre>" . print_r($defs, true) . "</pre>";
    exit;

    $csv = Writer::createFromFileObject(new SplTempFileObject());
    $csv->setNewline("\r\n");
    $csv->insertAll($post);
    $csv->output("defs_summary_" . date('YmdHis') . ".csv");
    // echo Export::csv($post);
} catch (\Exception $e) {
    error_log($e);
    header('500 Internal server error', true, 500);
} catch (\Error $e) {
    error_log($e);
    header('500 Internal server error', true, 500);
} finally {
    if (is_a($link, 'MySqliDB')) $link->disconnect();
    exit;
}
