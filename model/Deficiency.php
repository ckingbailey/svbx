<?php
namespace SVBX;

use MysqliDb;

class Deficiency
{
    const DATE_FORMAT = 'Y-m-d';
    protected $timestampField = 'lastUpdated';
    protected $creationFields = [
        'created_by',
        'dateCreated'
    ];
    // NOTE: prop names do not nec. have to match db col names
    //  (but it could help)
    protected $props = [
        'ID' => null,
        'safetyCert' => null,
        'systemAffected' => null,
        'location' => null,
        'specLoc' => null,
        'status' => null,
        'severity' => null,
        'dueDate' => null,
        'groupToResolve' => null,
        'requiredBy' => null,
        'contractID' => null,
        'identifiedBy' => null,
        'defType' => null,
        'description' => null,
        'spec' => null,
        'actionOwner' => null,
        'evidenceType' => null,
        'repo' => null,
        'evidenceID' => null,
        'evidenceLink' => null,
        'oldID' => null,
        'closureComments' => null,
        'created_by' => null, // validate: userID
        'updated_by' => null, // validate: userID
        'dateCreated' => null, // validate: date (before lastUpdated, dateClosed?)
        'lastUpdated' => null,
        'dateClosed' => null, // validate against status || set
        'closureRequested' => null, // validate against status??
        'closureRequestedBy' => null, // validate: userID
        'comments' => [],
        'newComment' => null,
        'pics' => [],
        'newPic' => null
    ];

    protected $table = 'CDL';
    protected $commentsTable = 'cdlComments';
    protected $picssTable = 'cdlPics';

    protected $fields = [
        'ID' =>  'defID',
        'safetyCert' => 'safetyCert',
        'systemAffected' => 'systemAffected',
        'location' => 'location',
        'specLoc' => 'specLoc',
        'status' => 'status',
        'severity' => 'severity',
        'dueDate' => 'dueDate',
        'groupToResolve' => 'groupToResolve',
        'requiredBy' => 'requiredBy',
        'contractID' => 'contractID',
        'identifiedBy' => 'identifiedBy',
        'defType' => 'defType',
        'description' => 'description',
        'spec' => 'spec',
        'actionOwner' => 'actionOwner',
        'evidenceType' => 'evidenceType',
        'repo' => 'repo',
        'evidenceID' => 'evidenceID',
        'evidenceLink' => 'evidenceLink',
        'oldID' => 'oldID',
        'closureComments' => 'closureComments',
        'created_by' => 'created_by',
        'updated_by' => 'updated_by',
        'dateCreated' => 'dateCreated',
        'dateClosed' => 'dateClosed',
        'closureRequested' => 'closureRequested',
        'closureRequestedBy' => 'closureRequestedBy'
    ];

    protected $requiredFields = [
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

    protected $associatedObjects = [
        'comments', // these are not strictly 'props' but are actually associated Objects
        'newComment', // these are not strictly 'props' but are actually associated Objects
        'pics', // these are not strictly 'props' but are actually associated Objects
        'newPic' // these are not strictly 'props' but are actually associated Objects
    ];
    
    static protected $foreignKeys = [
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
            $this->props['ID'] = $id;

            $this->set($data);
            // TODO: check for associated Objects => 'attachments' and 'comments'
            // TODO: check for new Attachment or Comment and link it to this Def
        } elseif (!empty($id)) { // This is a known Def. Query for its data
            try {
                $link = new MysqliDb(DB_CREDENTIALS);
                $link->where($this->fields['ID'], $id);
                if ($data = $link->getOne($this->table, array_values($this->fields))) {
                    $this->props['ID'] = $id;
                    $this->set($data);
                } else throw new \Exception("No Deficiency record found @ ID = $id");
            } catch (\Exception $e) {
                throw $e;
            } finally {
                if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
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
        if (is_string($props) && array_key_exists($props, $this->props)) {
            $this->props[$props] = trim($val);
        } elseif (is_array($props)) {
            foreach ($props as $key => $value) {
                // nullify any indexed props
                // set new vals for any string keys
                if (is_string($key) && array_key_exists($key, $this->props)) {
                    $this->props[$key] = empty(self::$foreignKeys[$key])
                        ? trim($value)
                        : intval($value);
                } elseif (is_numeric($key) && array_key_exists($value, $this->props)) {
                    $this->props[$value] = null;
                }
            }
        }
    }

    private function getInsertableFieldNames() {
        $fields = array_values($this->fields);
        unset($fields[array_search($this->fields['ID'], $fields)]);
        return $fields;
    }

    public function getNonNullProps() {
        return array_reduce($this->getInsertableFieldNames(), function ($acc, $prop) {
            if ($this->props[$prop] !== null) $acc[$prop] = $this->props[$prop];
            return $acc;
        }, []);
    }

    public function sanitize($props = null) { // TODO: takes an optional (String) single prop or (Array) of props to sanitize
        // TODO: intval props that ought to be int
    }

    // TODO: validate types of props (string, int, date)
    public function validateCreationInfo($action, $props = null) { // TODO: takes an optional (String) single prop or (Array) of props to validate
        if ($action === 'insert') {
            if (empty($this->props['created_by'])) throw new \Exception('Missing value @ `created_by`');
            if (empty($this->props['dateCreated'])) $this->set('dateCreated', date(self::DATE_FORMAT));
        }
    }

    public function validateModInfo($action, $props = null) { // TODO: takes an optional (String) single prop or (Array) of props to validate
        if ($action !== 'insert' && $action !== 'update')
            throw new \Exception("Invalid action, $action, passed to validateModInfo");
        if (empty($this->props['updated_by'])) {
            if ($action === 'update' || ($action === 'insert'
                && $this->props['updated_by'] !== $this->props['created_by']
                && !$this->props['updated_by'] = $this->props['created_by']))
            {
                throw new \Exception('Missing value @ `updated_by`');
            }
        }
    }

    public function validateRequiredInfo($action, $props = null) { // TODO: takes an optional (String) single prop or (Array) of props to validate
        // TODO: map each required field to type, validate or coerce types
        foreach ($this->requiredFields as $field) {
            if (($action === 'insert' && empty($this->props[$field]))
                || ($action === 'update' && $this->props[$field] === ''))
            {
                throw new \Exception("Missing required info @ `$field`");
            }
        }
    }

    public function validateClosureInfo($props = null) { // TODO: takes an optional (String) single prop or (Array) of props to validate
        if (intval($this->props['status']) === 2) { // TODO: numerical props should already be (int) by this point
            if (empty($this->props['repo'])) throw new \Exception('Missing closure info @ `repo`');
            if (empty($this->props['evidenceID'])) throw new \Exception('Missing closure info @ `evidenceID`');
            if (empty($this->props['evidenceType'])) throw new \Exception('Missing closure info @ `evidenceType`');
            if (empty($this->props['dateClosed'])) $this->set('dateClosed', date(self::DATE_CREATED));
        }
    }

    public function validate($action, $props = null) { // TODO: takes an optional (String) single prop or (Array) of props to validate
        $this->validateCreationInfo($action);
        $this->validateModInfo($action);
        $this->validateRequiredInfo($action);
        $this->validateClosureInfo();
    }

    // TODO: add fn to handle relatedAsset, newComment, newAttachment
    public function insert() {
        $newID = null;
        $this->set($this->timestampField, null); // lastUpdated gets timestamp by mysql

        $this->validate('insert');

        $insertableData = $this->getNonNullProps();
        unset($insertableData['ID']);
        $cleanData = filter_var_array($insertableData, FILTER_SANITIZE_SPECIAL_CHARS);
        
        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            if ($newID = $link->insert($this->table, $cleanData)) {
                $this->set('ID', $newID);
            }
            $link->disconnect();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
            return $this->get('ID');
        }
    }
    
    public function update() {
        // TODO: strip fields that never get updated, e.g., dateCreated
        $this->set($this->timestampField, null); // TODO: this should not be settable by constructor
        $this->set($this->creationFields, null);

        echo 'CREATION_INFO: ' . print_r($this->creationFields, true);

        $this->validate('update');

        // TODO: sanitize should be a method that mutates the object's own props
        // TODO: need an array of updatable keys
        $updatableData = $this->getNonNullProps();
        unset($updatableData['ID']);
        echo 'props before cleaning: ' . $this;
        $cleanData = filter_var_array($updatableData, FILTER_SANITIZE_SPECIAL_CHARS);

        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            $link->where($this->fields['ID'], $this->props['ID']);
            // TODO: re-instantiate with new vals on success
            echo 'Cleaned data before insert: ' . print_r($cleanData, true);
            if (!$success = $link->update($this->table, $cleanData)) {
                throw new \Exception("There was a problem updating the Deficiency {$this->props['ID']}");
            }
            $this->__construct($this->props['ID']);
        } catch (\Exception $e) {
            throw $e;
        } finally  {
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
            return $success;
        }
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
            return $this->props;
        } elseif (is_array($props)) {
            return array_reduce($this->props, function($acc, $prop) {
                if (array_key_exists($prop, $this->props)) {
                    $acc[$prop] = $this->props[$prop];
                }
                return $acc;
            }, []);
        } elseif (is_string($props)) {
            return $this->props[$props];
        }
    }

    public function getReadable($props = null) {
        if ($props === null) { // join and return all props
            try {
                $link = new MysqliDb(DB_CREDENTIALS);
                
                if (empty($this->props['ID'])) throw new \Exception('No ID found for Deficiency');
                $link->where($this->fields['ID'], $this->props['ID']);
                
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
        return print_r($this->get(), true);
    }
}
