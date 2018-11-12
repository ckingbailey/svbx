<?php
require 'vendor/autoload.php';

// Thanks go to this gist in writing this code:
// https://gist.github.com/johanmeiring/2894568
// function to dump def query to file readable by spreadsheet program
function writeToFile($data) {
    $curTime = time();
    $fileName = "./assets/tmp/def_dump_$curTime.csv";
    
    if (!$csv = fopen($fileName, 'w'))
        throw new Exception('Failed to open file stream');

    $headings = array_keys($data[0]);
    fputcsv($csv, $headings);
        
    foreach ($data as $row) {
        fputcsv($csv, $row);
    }

    return $fileName;
}

// function to write csv to string
if (!function_exists('str_putcsv')) {
    function str_putcsv(array $input, $delimiter = ',', $enclosure = '"') {
        $pointer = fopen('php://temp', 'r+b');
        foreach ($input as $line) {
            fputcsv($pointer, $line, $delimiter, $enclosure); // puts a single line
        }
        rewind($pointer);
        $data = rtrim(stream_get_contents($pointer), "\n");
        fclose($pointer);
        return $data;
    }
}

if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST')) {
    header('Access-Control-Allow-Methods: POST', true, 405);
    exit;
}

if (empty($_SESSION) // if POST lacks Session Cookie, forbidden
    || empty($_SESSION['username'])
    || empty($_SESSION['userID'])
    || empty($_SESSION['firstname'])
    || empty($_SESSION['lastname'])
    || empty($_SESSION['role'])
    || empty($_SESSION['timeout']))
{
    header('Status: 403 Forbidden', true, 403);
    exit;
}

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

// if Auth ok, proceed with encoding CSVs
$post = trim(file_get_contents('php://input'));
$post = json_decode($post, true);
$post = filter_var_array($post, FILTER_SANITIZE_SPECIAL_CHARS);

// concat the schema/host/port tuple
$host = substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/')) . '://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];

header('Content-Type: text/csv', true);
header("Access-Control-Allow-Origin: $host");

if (empty($_SERVER['HTTP_ORIGIN']) || strcasecmp($_SERVER['HTTP_ORIGIN'], $host) !== 0) {
    header('No cors, buddy', true, 403);
    exit;
}

echo str_putcsv($post);

exit;

/* TODO:
    1. Prevent CORS
*/