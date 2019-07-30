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

// only if it's a GET will you get a 0 and bypass this statement
if (strcasecmp($_SERVER['REQUEST_METHOD'], 'GET')) {
    http_response_code(405);
    exit;
}

try {
    // check Session vars against DB
    $link = new MySqliDB(DB_CREDENTIALS);

    if (empty($_GET['fields'])) {
        http_response_code(400);
        echo 'No data receieved';
        exit;
    }

    $fields = explode(',', $_GET['fields']);
    $view = 'deficiency';
    $headings = [
        'id' => '_id',
        'location' => 'Location',
        'severity' => 'Severity',
        'status' => 'Status',
        'systemAffected' => 'System Affected',
        'groupToResolve' => 'Group to Resolve',
        'description' => 'Description',
        'specLoc' => 'Specific Location',
        'requiredBy' => 'Required Prior To',
        'dueDate' => 'Due Date',
        'defType' => 'Type',
        'actionOwner' => 'Action Owner',
        'comment' => 'Comments'
    ];
    if (!empty($_GET['view'])) {
        if (strtolower($_GET['view']) === 'bart') {
            $view = 'bart_def';
            $headings = [
                'id' => '_id',
                'status' => 'Status',
                'date_created' => 'Date Created',
                'description' => 'Description',
                'resolution' => 'Resolution',
                'nextStep' => 'Next Step',
                'comment' => 'Comments'
            ];
        } else {
            http_response_code(400);
            echo $_GET['view'] . ' is not a known view';
            exit;
        }
    } 

    if (!empty($_GET['range'])) {
        $range = explode(',', $_GET['range']);
        if (count($range) > 2) {
            http_response_code(400);
            echo 'Range must be two comma-separated numbers';
            exit;
        }
        list($from, $to) = [ intval(min($range)), intval(max($range)) ];
        $link->where('id', $from, '>=');
        $link->where('id', $to, '<=');
    }

    $link->orderBy('id', 'ASC');
    $defs = $link->get($view, null, $fields);

    // if comments included, combine defs and put comments in extra cols
    if (array_search('comment', $fields) !== false) {
        $next = null;
        $comments = [];
        $output = [];
        foreach ($defs as $i => &$def) {
            $nextID = !empty($defs[$i + 1]) ? $defs[$i + 1]['id'] : null;
            // decode special chars
            $def['comment'] = html_entity_decode($def['comment'], ENT_QUOTES, 'utf-8');
            $def['description'] = html_entity_decode($def['description'], ENT_QUOTES, 'utf-8');
            // if next def is different from current def
            // push cur def to output array
            if ($nextID !== $def['id']) {
                // if any comments have been collected
                // add them to prev before pushing it to output array
                if (!empty($comments)) {
                    // push cur to end of comments collection
                    array_push($comments, $def['comment']);
                    unset($def['comment']);
                    $def = array_merge($def, $comments);
                    // reset comments collection
                    $comments = [];
                }
                array_push($output, $def);
            }
            // collect comment only if cur def is same as next def
            elseif (!empty($def['comment'])) {
                array_push($comments, $def['comment']);
            }
        }
        $defs = $output;
    }

    array_unshift($defs, $headings);

    $csv = Writer::createFromFileObject(new SplTempFileObject());
    $csv->setNewline("\r\n");
    $csv->insertAll($defs);
    $csv->output("defs_summary_" . date('YmdHis') . ".csv");
} catch (\Exception $e) {
    error_log($e);
    http_response_code(500);
    echo $e;
} catch (\Error $e) {
    error_log($e);
    http_response_code(500);
    echo $e;
} finally {
    if (is_a($link, 'MySqliDB')) $link->disconnect();
    exit;
}
