<?php
namespace SVBX;

use MysqliDb;

class Deficiency
{
    protected static $table = 'CDL';
    public $commentsTable = [
        'table' => 'cdlComments',
        'field' => 'cdlCommText',
        'defID' => 'defID',
        'commID' => 'cdlCommID'
    ];
    protected $picsTable = 'cdlPics';
    const DATE_FORMAT = 'Y-m-d';
    const MOD_HISTORY = [
        'created_by',
        'updated_by',
        'dateCreated',
        'lastUpdated',
        'dateClosed'
    ];
    const TIMESTAMP_FIELD = 'lastUpdated';
    protected $creationFields = [
        'created_by',
        'dateCreated'
    ];
    // NOTE: prop names do not nec. have to match db col names
    //  (but it could help for clarity)
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
        'bartDefID' => null,
        'closureComments' => null,
        'created_by' => null, // validate: username
        'updated_by' => null, // validate: username
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

    protected $filters = [
        'id' => 'intval',
        'safetyCert' => 'intval',
        'systemAffected' => 'intval',
        'location' => 'intval',
        'specLoc' => 'FILTER_SANITIZE_SPECIAL_CHARS',
        'status' => 'intval',
        'severity' => 'intval',
        'dueDate' => 'date',
        'groupToResolve' => 'intval',
        'requiredBy' => 'intval',
        'contractID' => 'intval',
        'identifiedBy' => 'FILTER_SANITIZE_SPECIAL_CHARS',
        'defType' => 'intval',
        'description' => 'FILTER_SANITIZE_SPECIAL_CHARS',
        'spec' => 'FILTER_SANITIZE_SPECIAL_CHARS',
        'actionOwner' => 'FILTER_SANITIZE_SPECIAL_CHARS',
        'evidenceType' => 'intval',
        'repo' => 'intval',
        'evidenceID' => 'FILTER_SANITIZE_SPECIAL_CHARS',
        'evidenceLink' => 'FILTER_SANITIZE_SPECIAL_CHARS',
        'oldID' => 'FILTER_SANITIZE_SPECIAL_CHARS',
        'bartDefID' => 'FILTER_SANITIZE_NUMBER_INT',
        'closureComments' => 'FILTER_SANITIZE_SPECIAL_CHARS',
        'created_by' => false,
        'updated_by' => false,
        'dateCreated' => 'date',
        'dateClosed' => 'date',
        'lastUpdated' => 'date',
        'closureRequested' => 'intval',
        'closureRequestedBy' => false,
        'comments' => false,
        'newComment' => false,
        'pics' => false,
        'newPic' => false
    ];

    // maps object props to database fields
    protected static $fields = [
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
        'bartDefID' => 'bartDefID',
        'closureComments' => 'closureComments',
        'created_by' => 'created_by',
        'updated_by' => 'updated_by',
        'dateCreated' => 'dateCreated',
        'lastUpdated' => 'lastUpdated',
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
                    'value' => 'deleted',
                    'comparison' => '!='
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
                $fields = static::$fields + [static::TIMESTAMP_FIELD => static::TIMESTAMP_FIELD];
                $link->where($fields['id'], $id);
                if ($data = $link->getOne(static::$table, array_values($fields))) {
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
            throw new \Exception('What is this? You tried to instantiate a new Deficiency without passing any data or id');
        }
    }

    public function set($props, $val = null) {
        $numericProps = array_filter(static::$foreignKeys, function($key) {
            return array_search($key, static::MOD_HISTORY) === false;
        }, ARRAY_FILTER_USE_KEY);
        if (is_string($props)
            && key_exists($props, $this->props)
            && $props !== static::TIMESTAMP_FIELD)
        {
            $this->props[$props] = trim($val);
        } elseif (is_array($props)) {
            foreach ($props as $key => $value) {
                // nullify any indexed props
                // set new vals for any string keys
                if (is_string($key)
                    && ($propsKey = key_exists($key, $this->props)
                        ? $key
                        : array_search($key, static::$fields))
                ) {
                    $this->props[$propsKey] = empty($numericProps[$propsKey])
                        ? trim($value)
                        : $this->falsyValueForZero($value);
                } elseif (is_numeric($key) && array_key_exists($value, $this->props)) {
                    $this->props[$value] = null;
                }
            }
        }
    }

    private function propsToFields($props = null) {
        $props = $props ?: array_keys($this->props);
        return array_reduce($props, function($acc, $prop) {
            if (!empty(static::$fields[$prop]) && $this->props[$prop] !== null) {
                $val = $this->props[$prop] !== '' ? $this->props[$prop] : null;
                $acc[static::$fields[$prop]] = $val;
            }
            return $acc;
        }, []);
    }

    public function sanitize($props = null) {
        $props = $props ?: $this->props;
        $reverseLookup = array_flip(static::$fields);
        return array_reduce(array_keys($props), function($acc, $key) use ($props) {
            // if $key is not in filters, look it up its corresponding prop name in fields
            $propName = !empty($this->filters[$key]) ? $key : array_search($key, static::$fields);
            $filter = $this->filters[$propName];
            if (strpos($filter, 'FILTER') === 0)
                $acc[$key] = filter_var($props[$key], constant($filter)) ?: null;
            elseif ($filter !== false) {
                // this if condition should be temporary
                // use only until all '0000-00-00' dates are properly set to NULL
                if ($filter === 'date' && $props[$key] === '0000-00-00')
                    $props[$key] = null;
                $acc[$key] = $filter($props[$key]) ?: null;
            }
            else $acc[$key] = $props[$key];
            // trim & stripcslashes here should be temporary
            // may be removed once all tainted Defs are cleaned of '\r\n'
            if (is_string($acc[$key])) $acc[$key] = trim(stripcslashes($acc[$key]));
            return $acc;
        }, []);
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
        // TODO: empty() is inadequate as it will throw for 0 or '0', which may be valid vals
        foreach ($this->requiredFields as $field) {
            if (($action === 'insert' && empty($this->props[$field]))
                || ($action === 'update' && $this->props[$field] === ''))
            {
                throw new \Exception("Missing required info @ `$field`");
            }
        }
    }

    public function validateClosureInfo($props = null) { // TODO: takes an optional (String) single prop or (Array) of props to validate
        try {
            $db = new MysqliDb(DB_CREDENTIALS);
            
            // get IDs of 'closed' statuses
            $db->where('statusName', '%closed%', 'LIKE');
            $statusIDs = array_column($db->get('status', null, [ 'statusID' ]), 'statusID');
            
            // if it has a 'closed' status, make sure it has closure information
            if (array_search($this->props['status'], $statusIDs) !== FALSE) {
                if (empty($this->props['repo'])) throw new \Exception('Missing closure info @ `repo`');
                if (empty($this->props['evidenceID'])) throw new \Exception('Missing closure info @ `evidenceID`');
                if (empty($this->props['evidenceType'])) throw new \Exception('Missing closure info @ `evidenceType`');
                if (empty($this->props['dateClosed'])) $this->set('dateClosed', date(static::DATE_FORMAT));
            }
        } catch (\Exception $e) {
            error_log($e);
            throw $e;
        } catch (\Error $e) {
            error_log($e);
            throw $e;
        } finally {
            if (!empty($db) && is_a($db, 'MysqliDb')) $db->disconnect();
        }
    }

    public function validate($action, $props = null) { // TODO: takes an optional (String) single prop or (Array) of props to validate
        $this->validateCreationInfo($action);
        $this->validateModInfo($action);
        $this->validateRequiredInfo($action);
        $this->validateClosureInfo();
        if (!empty($this->props['evidenceLink'])
            && filter_var($this->props['evidenceLink'], FILTER_VALIDATE_URL) === false)
            throw new \Exception('Evidence link must be valid URL');
    }

    // TODO: add fn to handle relatedAsset, newComment, newAttachment
    public function insert() {
        $newID = null;
        $this->set(static::TIMESTAMP_FIELD, null);

        $this->validate('insert');

        $insertableData = $this->propsToFields();
        unset(
            $insertableData[static::$fields['id']],
            $insertableData[static::$fields['lastUpdated']]
        );
        $insertableData = $this->sanitize($insertableData);
        
        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            if ($newID = $link->insert(static::$table, $insertableData)) {
                $this->set('id', $newID);
            } else {
                throw new \Exception('There was a problem inserting the deficiency: ' . $link->getLastError());
            }
            return $this->props['id'];
        } catch (\Exception $e) {
            throw $e;
        } finally {
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
        }
    }
    
    public function update() {
        $this->set(static::TIMESTAMP_FIELD, null);
        $this->set($this->creationFields, null);

        $this->validate('update');

        $updatableData = $this->propsToFields();
        // unset($updatableData['id']);
        $updatableData = $this->sanitize($updatableData);

        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            $link->where(static::$fields['id'], $this->props['id']);
            if (!$success = $link->update(static::$table, $updatableData)) {
                error_log($link->getLastQuery());
                throw new \Exception("There was a problem updating the Deficiency {$this->props['id']}");
            }
            $this->__construct($this->props['id']);
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
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
                if (empty($fields[1]))
                    $fields[1] = "{$lookup['fields'][0]} AS name";
                else $fields[1] .= ' AS name';
                
                if (!empty($lookup['where'])) {
                    $i = 0;
                    foreach ($lookup['where'] as $where) {
                        $comparator = !empty($where['comparison']) ? $where['comparison'] : '=';
                        $whereMethod = $i === 0 ? 'where' : 'orWhere';
                        if ($i === 0) $link->where($where['field'], $where['value'], $comparator);
                        else $link->orWhere($where['field'], $where['value'], $comparator);
                        $i++;
                    }
                }

                if (!empty($lookup['order'])) {
                    if (is_string($lookup['order']))
                        $link->orderBy($lookup['order']);
                    elseif (is_array($lookup['order']))
                        $direction = empty($lookup['order'][1]) ? 'ASC' : $lookup['order'][1];
                        $link->orderBy($lookup['order'][0], $direction);
                }
                
                $options[$childField] = $link->get($table, null, $fields);
            }
            
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
            return $options;
        } catch (\Exception $e) {
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

    /**
     * Returns all props, with specified numeric props as their string values from db lookup
     * If no props names passed, returns all string values
     */
    public function getReadable($props = null) { // TODO: takes an optional array of props to join and return
        // test for presence of $props in foreignKeys
        $foreignKeys = $props ?
            array_filter(static::$foreignKeys, function($key) use ($props) {
                return array_search($key, $props) !== false;
            }, ARRAY_FILTER_USE_KEY)
            : static::$foreignKeys; 
        try {
            $link = new MysqliDb(DB_CREDENTIALS);
            
            if (empty($this->props['id'])) throw new \Exception('No ID found for Deficiency');
            $link->where(static::$fields['id'], $this->props['id']);
            
            $lookupFields = [];
            
            foreach ($foreignKeys as $childField => $lookup) {
                $lookupTable = $lookup['table'];
                $alias = !empty($lookup['alias']) ? $lookup['alias'] : '';
                $lookupKey = $lookup['fields'][0];
                $nameField = empty($lookup['fields'][1]) ? $lookupKey : $lookup['fields'][1];
                $selectField = !empty($lookup['concat']) && $lookup['concat'] === true
                    ? sprintf('CONCAT(%s)',
                        implode(array_map(function($field) use ($alias, $lookupTable) {
                            return empty(trim($field))
                                ? "'$field'"
                                : ($alias ?: $lookupTable) . '.' . $field;
                        }, array_slice($lookup['fields'], 1)), ','))
                    : sprintf("%s.%s",
                    ($alias ?: $lookupTable),
                    $nameField
                );

                $join = $lookupTable . ($alias ? " AS {$alias}" : '');
                $joinOn = sprintf("%s.%s = %s.%s",
                    static::$table,
                    $childField,
                    ($alias ?: $lookupTable),
                    $lookupKey
                );

                $link->join($join, $joinOn, 'LEFT');

                $lookupFields[] = $selectField . ' AS ' . $childField;
            }

            if (!$readable = $link->getOne(static::$table, $lookupFields))
                throw new \Exception('There was a problem fetching on the lookup fields: ' . $link->getLastQuery());
            
            return $readable + $this->get();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
        }
    }

    public static function getTable() {
        return static::$table;
    }

    public static function getFields() {
        return static::$fields;
    }

    public static function getLookupMap() : array
    {
        $foreignKeys = static::$foreignKeys;
        return array_reduce(array_keys($foreignKeys),
            function ($lookups, $field) use ($foreignKeys) {
                $lookups[$field] = !empty($foreignKeys[$field]['concat']) && $foreignKeys[$field]['concat'] === true
                ? implode(array_slice($foreignKeys[$field]['fields'], 1))
                : $foreignKeys[$field]['fields'][1];
                return $lookups;
            }, []);
    }

    public static function getJoins(array $fields = []): array {
        $keys = array_keys(static::$foreignKeys);
        $fieldList = empty($fields)
        ? $keys
        : array_reduce($fields, function ($output, $field) use ($keys) {
            if (array_search($field, $keys) !== FALSE)
                $output[] = $field;
            return $output;
        }, []);

        return array_map(function ($childField) {
            $join = static::$foreignKeys[$childField];

            list($parentTable, $alias) = !empty($join['alias'])
            ? [ "{$join['table']} AS {$join['alias']}", $join['alias'] ]
            : [ $join['table'], $join['table'] ];
            
            return [
                'table' => $parentTable,
                'on' => static::$table . ".$childField = $alias.{$join['fields'][0]}",
                'type' => 'LEFT'
            ];
        }, $fieldList);
    }
    
    public function __toString() {
        return print_r($this->get(), true);
    }

    private function falsyValueForZero($val, $falsy = '') {
        $num = intval($val);
        return $num === 0 ? $falsy : $num;
    }
}
