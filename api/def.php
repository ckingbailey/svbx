<?php
use SVBX\Export;

require 'vendor/autoload.php';
require 'session.php';

if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST')) {
    header('Access-Control-Allow-Methods: POST', true, 405);
    exit;
}

try {
    // check Session vars against DB
    $link = new MySqliDB(DB_CREDENTIALS);
    $fields = [ 'username', 'userID', 'firstname', 'lastname', 'role' ];

    $link->where('userID', $_SESSION['userID']);
    $result = $link->getOne('users_enc', $fields);

    if ($result['username'] !== $_SESSION['username']
        || $result['role'] !== $_SESSION['role']
        || $result['firstname'] !== $_SESSION['firstname']
        || $result['lastname'] !== $_SESSION['lastname'])
    {
        header('Status: 403 Forbidden', true, 403);
        exit;
    }

    // if Auth ok, validate fields on first data element of POST against fields in DB
    // note: element at index 0 is heading names, not table data
    $post = trim(file_get_contents('php://input'));
    $post = json_decode($post, true);
    $post = filter_var_array($post, FILTER_SANITIZE_SPECIAL_CHARS);

    $link->where('table_name', 'CDL');
    $link->orWhere('table_name', 'BARTDL');
    $cols = $link->getValue('information_schema.columns', 'column_name', null); // returns 50+ columns
    $cols = array_map('strtolower', $cols);

    $postKeys = array_keys($post[1] + $post[count($post) - 1] + $post[floor((count($post) / 2))]); // grab keys from first, middle, and last element of post data

    if (($idIndex = array_search('ID', $postKeys)) !== false) unset($postKeys[$idIndex]); // don't try to match ID col name

    foreach ($postKeys as $key) {
        if (array_search(strtolower($key), $cols) === false) {
            header('Status: 400 Bad Request', true, 400);
            exit;
        }
    }

    header('Content-Type: text/csv', true);

    echo Export::csv($post);
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
