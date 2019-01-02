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
    protected $timestampField = 'lastUpdated';
    protected $creationFields = [
        'created_by',
        'dateCreated'
    ];
    // NOTE: prop names do not nec. have to match db col names
    //  (but it could help)
    protected $props = [
        'id' => null,
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
    public $commentsTable = [
        'table' => 'cdlComments',
        'field' => 'cdlCommText',
        'defID' => 'defID',
        'commID' => 'cdlCommID'
    ];
    protected $picsTable = 'cdlPics';

    protected $fields = [
        'id' =>  'defID',
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
    
    protected static $foreignKeys = [
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
        ],
        'created_by' => [
            'table' => 'users_enc',
            'alias' => 'cb',
            'fields' => [ 'username', 'firstname', ' ', 'lastname' ],
            'concat' => true
        ],
        'updated_by' => [
            'table' => 'users_enc',
            'alias' => 'ub',
            'fields' => [ 'username', 'firstname', ' ', 'lastname' ],
            'concat' => true
        ]
    ];
    
    public function __construct($id = null, array $data = []) {
        if (!empty($id) && !empty($data)) { // This is a known Def
            $this->props['id'] = $id;

            $this->set($data);
            // TODO: check for associated Objects => 'attachments' and 'comments'
            // TODO: check for new Attachment or Comment and link it to this Def
        } elseif (!empty($id)) { // This is a known Def. Query for its data
            try {
                $link = new MysqliDb(DB_CREDENTIALS);
                $link->where($this->fields['id'], $id);
                if ($data = $link->getOne($this->table, array_values($this->fields))) {
                    error_log(__FILE__ . '(' . __LINE__ . ') def fetched from db: ' . PHP_EOL . print_r($data, true));
                    $this->props['id'] = $id;
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
    }

    public function set($props, $val = null) {
        $numericProps = array_filter(static::$foreignKeys, function($key) {
            return array_search($key, static::MOD_HISTORY) === false;
        }, ARRAY_FILTER_USE_KEY);
        error_log(__FILE__ . '(' . __LINE__ . ') numeric props:' . PHP_EOL . print_r($numericProps, true));
        if (is_string($props)
            && key_exists($props, $this->props)
            && $props !== $this->timestampField)
        {
            $this->props[$props] = trim($val);
        } elseif (is_array($props)) {
            error_log(__FILE__ . '(' . __LINE__ . ") props to iterate" . PHP_EOL . print_r($props, true));
            foreach ($props as $key => $value) {
                // nullify any indexed props
                // set new vals for any string keys
                if (is_string($key)
                    && ($propsKey = key_exists($key, $this->props)
                        ? $key
                        : array_search($key, $this->fields))
                ) {
                    $this->props[$propsKey] = empty($numericProps[$propsKey])
                        ? trim($value)
                        : intval($value);
                } elseif (is_numeric($key) && array_key_exists($value, $this->props)) {
                    $this->props[$value] = null;
                }
            }
        }
    }

    private function propsToFields($props = null) {
        $props = $props ?: array_keys($this->props);
        return array_reduce($props, function($acc, $prop) {
            if (!empty($this->fields[$prop]) && $this->props[$prop] !== null) {
                $val = $this->props[$prop] !== '' ? $this->props[$prop] : null;
                $acc[$this->fields[$prop]] = $val;
            }
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
            if (empty($this->props['dateClosed'])) $this->set('dateClosed', date(static::DATE_FORMAT));
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
        $this->set($this->timestampField, null);

        $this->validate('insert');

        $insertableData = $this->propsToFields();
        unset($insertableData[$this->fields['id']]);
        unset($insertableData[$this->fields['lastUpdated']]);
        $cleanData = filter_var_array($insertableData, FILTER_SANITIZE_SPECIAL_CHARS);
        error_log(__FILE__ . '(' . __LINE__ . ') data ready for insert ' . print_r($cleanData, true));
        
        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            if ($newID = $link->insert($this->table, $cleanData)) {
                $this->set('id', $newID);
            } else throw new \Exception('There was a problem inserting the deficiency: ' . $link->getLastError());
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
            return $this->id;
        } catch (\Exception $e) {
            throw $e;
        } finally {
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
        }
    }
    
    public function update() {
        $this->set($this->timestampField, null);
        $this->set($this->creationFields, null);

        $this->validate('update');

        // TODO: sanitize should be a method that mutates the object's own props
        // TODO: need an array of updatable keys
        $updatableData = $this->propsToFields();
        unset($updatableData['id']);
        $cleanData = filter_var_array($updatableData, FILTER_SANITIZE_SPECIAL_CHARS);
        error_log('def data ready for update: ' . PHP_EOL . print_r($cleanData, true));

        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            $link->where($this->fields['id'], $this->props['id']);
            if (!$success = $link->update($this->table, $cleanData)) {
                throw new \Exception("There was a problem updating the Deficiency {$this->props['id']}");
            }
            $this->__construct($this->props['id']);
        } catch (\Exception $e) {
            throw $e;
        } finally  {
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
            return $success;
        }
    }
    
    // TODO: this could check for which value should be selected (the value that is on data for the corresponding field)
    // in that case it would have to take a Defificency object as argument
    public static function getLookUpOptions() {
        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            $options = [];
            $foreignKeys = array_filter(static::$foreignKeys, function($key) {
                return array_search($key, static::MOD_HISTORY) === false;
            }, ARRAY_FILTER_USE_KEY);
            
            foreach ($foreignKeys as $childField => $lookup) {
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
            }
            
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
            return $options;
        } catch (Exception $e) {
            error_log($e);
            throw $e;
        }
    }

    public function get($props = null) {
        if ($props === null) {
            return $this->props;
        } elseif (is_array($props)) {
            return array_filter($this->props, function($key) use ($props) {
                return array_search($key, $props) !== false;
            }, ARRAY_FILTER_USE_KEY);
            // return array_reduce($this->props, function($acc, $prop) {
            //     if (array_key_exists($prop, $this->props)) {
            //         $acc[$prop] = $this->props[$prop];
            //     }
            //     return $acc;
            // }, []);
        } elseif (is_string($props)) {
            return $this->props[$props];
        }
    }

    public function getReadable($props = null) { // TODO: takes an optional array of props to join and return
        // TODO: test for presence of $props in foreignKeys
        $foreignKeys = $props ?
            array_filter(static::$foreignKeys, function($key) use ($props) {
                return array_search($key, $props) !== false;
            }, ARRAY_FILTER_USE_KEY)
            : static::$foreignKeys; 
        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            
            if (empty($this->props['id'])) throw new \Exception('No ID found for Deficiency');
            $link->where($this->fields['id'], $this->props['id']);
            
            $lookupFields = [];
            
            foreach ($foreignKeys as $childField => $lookup) {
                $lookupTable = $lookup['table'];
                $alias = !empty($lookup['alias']) ? $lookup['alias'] : '';
                $lookupKey = $lookup['fields'][0];
                $selectField = !empty($lookup['concat']) && $lookup['concat'] === true
                    ? sprintf('CONCAT(%s)',
                        implode(array_map(function($field) use ($alias, $lookupTable) {
                            return empty(trim($field))
                                ? "'$field'"
                                : ($alias ?: $lookupTable) . '.' . $field;
                        }, array_slice($lookup['fields'], 1)), ','))
                    : sprintf("%s.%s",
                    ($alias ?: $lookupTable),
                    $lookup['fields'][1]
                );

                $join = $lookupTable . ($alias ? " AS {$alias}" : '');
                $joinOn = sprintf("%s.%s = %s.%s",
                    $this->table,
                    $childField,
                    ($alias ?: $lookupTable),
                    $lookupKey
                );

                $link->join($join, $joinOn, 'LEFT');

                $lookupFields[] = $selectField . ' AS ' . $childField;
            }

            if (!$readable = $link->getOne($this->table, $lookupFields))
                throw new \Exception('There was a problem fetching on the lookup fields: ' . $link->getLastQuery());
            
            return $readable + $this->get();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
        }
    }
    
    public function __toString() {
        return print_r($this->get(), true);
    }
}
