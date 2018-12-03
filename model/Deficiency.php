<?php
namespace SVBX;

use MysqliDb;

class Deficiency
{
    const DATE_FORMAT = 'Y-m-d';
    const MOD_HISTORY = [
        'created_by',
        'updated_by',
        'dateCreated',
        'lastUpdated',
        'dateClosed'
    ];
    const CLOSURE_INFO = [
        'evidenceType',
        'repo',
        'evidenceID',
        'dateClosed'
    ];
    // NOTE: prop names do not nec. have to match db col names
    //  (but it could help)
    private $ID = null;
    private $safetyCert = null;
    private $systemAffected = null;
    private $location = null;
    private $specLoc = null;
    private $status = null;
    private $severity = null;
    private $dueDate = null;
    private $groupToResolve = null;
    private $requiredBy = null;
    private $contractID = null;
    private $identifiedBy = null;
    private $defType = null;
    private $description = null;
    private $spec = null;
    private $actionOwner = null;
    private $evidenceType = null;
    private $repo = null;
    private $evidenceID = null;
    private $evidenceLink = null;
    private $oldID = null;
    private $closureComments = null;
    private $created_by = null; // validate: userID
    private $updated_by = null; // validate: userID
    private $dateCreated = null; // validate: date (before lastUpdated; dateClosed?)
    private $lastUpdated = null;
    private $dateClosed = null; // validate against status || set
    private $closureRequested = null; // validate against status??
    private $closureRequestedBy = null; // validate: userID
    private $comments = [];
    private $newComment = null;
    private $pics = [];
    private $newPic = null;

    private $table = 'CDL';
    private $commentsTable = 'cdlComments';
    private $picssTable = 'cdlPics';

    private $fields = [
        'safetyCert',
        'systemAffected',
        'location',
        'specLoc',
        'status',
        'severity',
        'dueDate',
        'groupToResolve',
        'requiredBy',
        'contractID',
        'identifiedBy',
        'defType',
        'description',
        'spec',
        'actionOwner',
        'evidenceType',
        'repo',
        'evidenceID',
        'evidenceLink',
        'oldID',
        'closureComments',
        'created_by',
        'updated_by',
        'dateCreated',
        'lastUpdated',
        'dateClosed',
        'closureRequested',
        'closureRequestedBy'
    ];

    private $requiredFields = [
        'safetyCert',
        'systemAffected',
        'location',
        'specLoc',
        'status',
        'severity',
        'dueDate',
        'groupToResolve',
        'requiredBy',
        'contractID',
        'identifiedBy',
        'defType',
        'description'
    ];

    private $associatedObjects = [
        'comments', // these are not strictly 'props' but are actually associated Objects
        'newComment', // these are not strictly 'props' but are actually associated Objects
        'pics', // these are not strictly 'props' but are actually associated Objects
        'newPic' // these are not strictly 'props' but are actually associated Objects
    ];
    
    static private $foreignKeys = [
        'safetyCert' => [
            'table' => 'yesNo',
            'fields' => ['yesNoID', 'yesNoName']
        ],
        'systemAffected' => [
            'table' => 'system',
            'fields' => ['systemID', 'systemName']
        ],
        'groupToResolve' => [
            'table' => 'system',
            'alias' => 'groupToResolve',
            'fields' => ['systemID', 'systemName']
        ],
        'location' => [
            'table' => 'location',
            'fields' => ['locationID', 'locationName']
        ],
        'status' => [
            'table' => 'status',
            'fields' => ['statusID', 'statusName'],
            'where' => [
                [
                    'field' => 'statusName',
                    'value' => 'open'
                ],
                [
                    'field' => 'statusName',
                    'value' => 'closed'
                ]
            ]
        ],
        'severity' => [
            'table' => 'severity',
            'fields' => ['severityID', 'severityName']
        ],
        'requiredBy' => [
            'table' => 'requiredBy',
            'fields' => ['reqByID', 'requiredBy']
        ],
        'contractID' => [
            'table' => 'contract',
            'fields' => ['contractID', 'contractName']
        ],
        'defType' => [
            'table' => 'defType',
            'fields' => ['defTypeID', 'defTypeName']
        ],
        'evidenceType' => [
            'table' => 'evidenceType',
            'fields' => ['eviTypeID', 'eviTypeName']
        ],
        'repo' => [
            'table' => 'repo',
            'fields' => ['repoID', 'repoName']
        ]
    ];
    
    // TODO: check for incoming defID in DB and validate persisted data against incoming data
    public function __construct($id = null, array $data = []) {
        if (!empty($id) && !empty($data)) { // This is a known Def
            $this->ID = $id;

            $this->set($data);
            // TODO: check for associated Objects => 'attachments' and 'comments'
            // TODO: check for new Attachment or Comment and link it to this Def
        } elseif (!empty($id)) { // This is a known Def. Query for its data
            try {
                $link = new MysqliDb(DB_CREDENTIALS);
                $link->where('defID', $id);
                if ($data = $link->getOne('CDL', $this->fields)) {
                    $this->ID = $id;
                    $this->set($data);
                } else throw new \Exception("No Deficiency record found @ ID = $id");
            } catch (\Exception $e) {
                throw $e;
            } finally {
                if (is_a($link, 'MysqliDb')) $link->disconnect();
            }
            // TODO: query for associated Objects => 'attachments' and 'comments'
        } elseif (!empty($data)) {
            $this->set($data);
        } else {
            // no id or props = no good
            throw new Exception('What is this? You tried to instantiate a new Deficiency without passing any data or id');
        }

        //---------------------------------------------------------------------------------//
        // // TODO: check for defID before checking creation deets <-- this only pertains to update
        // // check creation details in db before setting them in obj
        // // if (empty($this->data['createdBy']) || empty($this->data['dateCreated'])) {
        //     // $link = new MysqliDb(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        //     // $link->where('defID', $this->data['defID']);
        //     // $creationStamp = $link->get('deficiency', ['createdBy', 'dateCreated']);
        // if (empty($this->data['createdBy'])) $this->data['createdBy'] = $_SESSION['userID'];
        // if (empty($this->data['dateCreated'])) $this->data['dateCreated'] = date('Y-m-d H:i:s');
        // // }
    }

    public function set($props, $val = null) {
        if (is_string($props) && property_exists(__CLASS__, $props)) {
            // if a date key is passed by itself, set to current date
            if (strpos(strtolower($props), 'date') !== false) {
                $val = $val ?: time();
                $this->$props = date(self::DATE_FORMAT, $val);
            } else $this->$props = $val;
        } elseif (is_array($props)) {
            foreach ($props as $key => $val) {
                // nullify any indexed props
                // set new vals for any string keys
                if (is_string($key) && property_exists(__CLASS__, $key)) {
                    $this->$key = empty(self::$foreignKeys[$key])
                        ? $val
                        : intval($val);
                } elseif (is_numeric($key) && property_exists(__CLASS__, $val)) {
                    $this->$val = null;
                }
            }
        }
    }

    public function getNonNullProps() {
        return array_reduce($this->fields, function ($acc, $prop) {
            if ($this->$prop !== null) $acc[$prop] = $this->$prop;
            return $acc;
        }, []);
    }

    public function sanitize($props = null) { // TODO: takes an optional (String) single prop or (Array) of props to sanitize
        // TODO: intval props that ought to int
    }

    public function validate($props = null) { // TODO: takes an optional (String) single prop or (Array) of props to validate
        // TODO: validate dates, required info, closure info where appropriate
    }

    // TODO: add fn to handle relatedAsset, newComment, newAttachment
    public function insert() {
        $this->lastUpdated = null; // lastUpdated gets timestamp by mysql

        // validate creation info
        if (empty($this->created_by)) throw new \Exception('Missing value @ `created_by`');
        if (empty($this->dateCreated)) $this->set('dateCreated');
        
        // validate mod info
        if (empty($this->updated_by) || !$this->updated_by === $this->created_by) {
            if (!$this->updated_by = $this->created_by)
                throw new \Exception('Missing value @ `updated_by`');
        }
        
        // validate required info
        foreach ($this->requiredFields as $field) {
            if (empty($this->$field)) throw new \Exception("Missing required info @ `$field`");
        }
        
        // validate / set closure info if appropriate
        if (intval($this->status) === 2) { // TODO: numerical props should already be (int) by this point
            if (empty($this->repo)) throw new \Exception('Missing closure info @ `repo`');
            if (empty($this->evidenceID)) throw new \Exception('Missing closure info @ `evidenceID`');
            if (empty($this->evidenceType)) throw new \Exception('Missing closure info @ `evidenceType`');
            if (empty($this->dateClosed)) $this->set('dateClosed');
        }
        
        $insertableData = $this->getNonNullProps();
        $cleanData = filter_var_array($insertableData, FILTER_SANITIZE_SPECIAL_CHARS);
        
        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            if ($newID = $link->insert($this->table, $cleanData)) {
                $this->ID = $newID;
            }
            $link->disconnect();
        } catch (\Exception $e) { throw $e; }
        finally { if (is_a($link, 'MysqliDb')) $link->disconnect(); }
        
        return $this->ID;
    }
    
    public function update() {
        // TODO: strip fields that never get updated, e.g., dateCreated
        return false;
    }
    
    // TODO: this could check for which value should be selected (the value that is on data for the corresponding field)
    // in that case it would have to take a Defificency object as argument
    static function getLookUpOptions() {
        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            $options = [];
            
            foreach (self::$foreignKeys as $childField => $lookup) {
                $table = $lookup['table'];
                $fields = $lookup['fields'];
                $fields[0] .= ' AS id';
                $fields[1] .= ' AS name';
                
                if (!empty($lookup['where'])) {
                    $i = 0;
                    foreach ($lookup['where'] as $where) {
                        if ($i === 0) $link->where($where['field'], $where['value']);
                        else $link->orWhere($where['field'], $where['value']);
                        $i++;
                    }
                }
                
                $options[$childField] = $link->get($table, null, $fields);
                // $options[$childField] = [$table => [ $fields ]];
            }
            
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
            return $options;
        } catch (Exception $e) {
            error_log($e);
            throw $e;
        }
    }

    public function get($props = null) {
        if ($props === null) { // return all props
            return [
                'ID' => $this->ID,
                'safetyCert' => $this->safetyCert,
                'systemAffected' => $this->systemAffected,
                'location' => $this->location,
                'specLoc' => $this->specLoc,
                'status' => $this->status,
                'severity' => $this->severity,
                'dueDate' => $this->dueDate,
                'groupToResolve' => $this->groupToResolve,
                'requiredBy' => $this->requiredBy,
                'contractID' => $this->contractID,
                'identifiedBy' => $this->identifiedBy,
                'defType' => $this->defType,
                'description' => $this->description,
                'spec' => $this->spec,
                'actionOwner' => $this->actionOwner,
                'evidenceType' => $this->evidenceType,
                'repo' => $this->repo,
                'evidenceID' => $this->evidenceID,
                'evidenceLink' => $this->evidenceLink,
                'oldID' => $this->oldID,
                'closureComments' => $this->closureComments,
                'created_by' => $this->created_by,
                'updated_by' => $this->updated_by,
                'dateCreated' => $this->dateCreated,
                'lastUpdated' => $this->lastUpdated,
                'dateClosed' => $this->dateClosed,
                'closureRequested' => $this->closureRequested,
                'closureRequestedBy' => $this->closureRequestedBy,
                'comments' => $this->comments,
                'newComment' => $this->newComment,
                'pics' => $this->pics,
                'newPic' => $this->newPic
            ];
        }
        return $this->$props;
    }

    public function getReadable($props = null) {
        if ($props === null) { // join and return all props
            try {
                $link = new MysqliDb(DB_CREDENTIALS);
                
                if (empty($this->ID)) throw new \Exception('No ID found for Deficiency');
                $link->where('defID', $this->ID);
                
                $props = $this->get();
                $lookupFields = [];
                
                foreach (self::$foreignKeys as $childField => $lookup) {
                    $lookupTable = $lookup['table'];
                    $alias = !empty($lookup['alias']) ? $lookup['alias'] : '';
                    $lookupKey = $lookup['fields'][0];
                    $displayName = sprintf("%s.%s",
                        ($alias ?: $lookupTable),
                        $lookup['fields'][1]
                    );

                    $join = $lookupTable . ($alias ? " as {$alias}" : '');
                    $joinOn = sprintf("%s.%s = %s.%s",
                        $this->table,
                        $childField,
                        ($alias ?: $lookupTable),
                        $lookupKey
                    );

                    $link->join($join, $joinOn, 'LEFT');

                    $lookupFields[] = $displayName . ' as ' . $childField;
                }

                if (!$readable = $link->getOne($this->table, $lookupFields))
                    throw new \Exception('There was a problem fetching from the lookup fields: ' . $link->getLastQuery());
                
                return $readable + $props;
            } catch (\Exception $e) {
                throw $e;
            } finally {
                if (is_a($link, 'MysqliDb')) $link->disconnect();
            }
        }
    }
    
    public function __toString() {
        return print_r([
            'ID' => $this->ID,
            'safetyCert' => $this->safetyCert,
            'systemAffected' => $this->systemAffected,
            'location' => $this->location,
            'specLoc' => $this->specLoc,
            'status' => $this->status,
            'severity' => $this->severity,
            'dueDate' => $this->dueDate,
            'groupToResolve' => $this->groupToResolve,
            'requiredBy' => $this->requiredBy,
            'contractID' => $this->contractID,
            'identifiedBy' => $this->identifiedBy,
            'defType' => $this->defType,
            'description' => $this->description,
            'spec' => $this->spec,
            'actionOwner' => $this->actionOwner,
            'evidenceType' => $this->evidenceType,
            'repo' => $this->repo,
            'evidenceID' => $this->evidenceID,
            'evidenceLink' => $this->evidenceLink,
            'oldID' => $this->oldID,
            'closureComments' => $this->closureComments,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'dateCreated' => $this->dateCreated,
            'lastUpdated' => $this->lastUpdated,
            'dateClosed' => $this->dateClosed,
            'closureRequested' => $this->closureRequested,
            'closureRequestedBy' => $this->closureRequestedBy,
            'comments' => $this->comments,
            'newComment' => $this->newComment,
            'pics' => $this->pics,
            'newPic' => $this->newPic
        ], true);
    }
}
