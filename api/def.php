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
    $link = new MySqliDB(DB_CREDENTIALS);

    if (empty($_GET['fields'])) {
        http_response_code(400);
        echo 'No data receieved';
        exit;
    }

    $get = $_GET;
    $fields = explode(',', $get['fields']);
    unset($get['fields']);

    $view = 'deficiency';
    $headings = [
        'id' => '_id',
        'bartDefID' => 'BART ID',
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
        // closure infos
        'evidenceID' => 'Document ID',
        'evidenceType' => 'Evidence Type',
        'repo' => 'Evidence Repository',
        'safetyCert' => 'Safety Cert.?',
        'certElID' => 'Certifiable Element ID',
        'CEID_PDCC' => 'CEID PDCC #',
        'evidenceLink' => 'Document Link',
        'FinalGroup' => 'Final Group?',
        'closureComments' => 'Closure Comments',
        'comment' => 'Comments'
    ];

    /**
     * Validate $view
     */
    if (!empty($get['view'])) {
        if (strtolower($get['view']) === 'bart') {
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
            error_log('Someone attempted to get view=' . $get['view']);
            echo $get['view'] . ' is not a valid view';
            exit;
        }
        unset($get['view']);
    }

    /**
     * Validate and parse $range
     */
    if (!empty($get['range'])) {
        $range = explode(',', $_GET['range']);
        if (count($range) > 2) {
            http_response_code(400);
            echo 'Range must be two comma-separated numbers';
            exit;
        }
        list($from, $to) = [ intval(min($range)), intval(max($range)) ];
        $link->where('id', $from, '>=');
        $link->where('id', $to, '<=');
        unset($get['range']);
    }

    /**
     * Order fields according to headings order
     * 1. Map fields as keys back to fields, field names become both keys and vals
     * 2. lowercase field names for matching with headings field names
     * 3. lowercase headings, same purpose as previous step
     * 4. map fields names to headings field names, fields names become ordered by headings field names order
     * 5. intersect headings -> fields mapping with o.g. fields, we drop those headings fields are not in requested fields
     * 6. extract values from intersected headings / fields, field names are the part that's useful to us
     */
     $fields = array_values(array_intersect(
        array_replace(
            array_change_key_case($headings),
            array_change_key_case(array_combine($fields, $fields)
        )), $fields
    ));

    // filter defs with remaining GET params
    if (!empty($get)) {
        foreach ($get as $key => $val) {
            // for these free text fields (and ID) use LIKE comparison
            if (strcasecmp($key, 'identifiedby') === 0
            || strcasecmp($key, 'specloc') === 0
            || strcasecmp($key, 'id') === 0
            || strcasecmp($key, 'bartDefID') === 0
            || strcasecmp($key, 'description') === 0
            || strcasecmp($key, 'CEID_PDCC') === 0
            || strcasecmp($key, 'certElID') === 0) {
                $link->where($key, "%{$val}%", 'LIKE');
            }

            // these fields allow selecting multiples for plain Def view
            elseif (strcasecmp($key, 'systemAffected') === 0
            || strcasecmp($key, 'groupToResolve') === 0
            || (strcasecmp($key, 'status') === 0 && $view === 'deficiency')
            && is_array($val))
            {
                // fetch systemIDs because they are not in the view
                $lowerKey = strtolower($key);
                $tableLookup = [
                    'systemaffected' => 'system',
                    'grouptoresolve' => 'system',
                    'status' => 'status'
                ];
                $table = $tableLookup[$lowerKey];

                $link2 = new MySqliDB(DB_CREDENTIALS);
                $lookup = $link2->get($table, null, [ "{$table}ID id", "{$table}Name name" ]);
                $link2->disconnect();

                $lookup = array_combine(array_column($lookup, 'id'), array_column($lookup, 'name'));
                $namedVals = array_map(function ($num) use ($lookup) {
                    return $lookup[$num];
                }, $val);

                $link->where($key, $namedVals, 'IN');
            }
            
            // requiredBy uses a shortened naming anti-pattern
            elseif (strcasecmp($key, 'requiredBy') === 0) {
                $table = 'requiredBy'; // mind the capitalization
                $id = 'reqById';
                $name = 'requiredBy';
                $link2 = new MySqliDB(DB_CREDENTIALS);
                $temp = $link2->get($table, null, [ $id, $name ]);
                $lookup = array_reduce($temp, function ($dict, $row) use ($id, $name) {
                    $dict[$row[$id]] = $row[$name];
                    return $dict;
                }, []);
                $link->where($key, $lookup[$val]);
            }
            
            // next_step's col names don't match the table name
            elseif (strcasecmp($key, 'next_step') === 0) {
                $table = 'bdNextStep';
                $id = 'bdNextStepID';
                $name = 'nextStepName';
                $link2 = new MySqliDB(DB_CREDENTIALS);
                $temp = $link2->get($table, null, [ $id, $name ]);
                $link2->disconnect();
                $lookup = array_reduce($temp, function ($dict, $row) use ($id, $name) {
                    $dict[$row[$id]] = $row[$name];
                    return $dict;
                }, []);
                $link->where('nextStep', $lookup[$val]);
            } elseif ($view === 'bart_def' && strcasecmp($key, 'status') === 0) {
                $table = $key;
                $id = "{$table}ID";
                $name = "{$table}Name";
                $link2 = new MySqliDB(DB_CREDENTIALS);
                $temp = $link2->get($table, null, [ $id, $name ]);
                $link2->disconnect();
                $lookup = array_reduce($temp, function ($dict, $row) use ($id, $name) {
                    $dict[$row[$id]] = $row[$name];
                    return $dict;
                }, []);
                $link->where($key, $lookup[$val]);
            }
            // safetyCert uses yesNo as lookup
            elseif (($view === 'deficiency' && strcasecmp($key, 'safetyCert') === 0)) {
                $table = 'yesNo';
                $id = "{$table}ID";
                $name = "{$table}Name";
                $link2 = new MySqliDB(DB_CREDENTIALS);
                $temp = $link2->get($table, null, [ $id, $name ]);
                $link2->disconnect();
                $lookup = array_reduce($temp, function ($dict, $row) use ($id, $name) {
                    $dict[$row[$id]] = $row[$name];
                    return $dict;
                }, []);
                $link->where($key, $lookup[$val]);
            } else {
                $link->where($key, $val);
            }
            unset($get[$key]);
        }
    }

    $link->orderBy('id', 'ASC');
    if ($includeComments = (array_search('comment', $fields) !== false)) {
        $link->orderBy('comment_date', 'DESC');
    }

    $defs = $link->get($view, null, $fields);

    // if comments included, combine defs and put comments in extra cols
    if ($includeComments) {
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
    // echo $e;
} catch (\Error $e) {
    error_log($e);
    http_response_code(500);
    // echo $e;
} finally {
    if (is_a($link, 'MySqliDB')) $link->disconnect();
    exit;
}
