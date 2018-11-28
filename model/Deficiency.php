<?php
namespace SVBX;

use MysqliDb;

class Deficiency
{
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
    private $contract = null;
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
    private $attachments = [];
    private $newAttachment = null;

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
        'contractID as contract',
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

    private $associatedObjects = [
        'comments', // these are not strictly 'props' but are actually associated Objects
        'newComment', // these are not strictly 'props' but are actually associated Objects
        'attachments', // these are not strictly 'props' but are actually associated Objects
        'newAttachment' // these are not strictly 'props' but are actually associated Objects
    ];
    
    // do not insert or update these fields on the Deficiency table
    private $filterKeys = [
        'ID' => true,
        'assets' => true,
        'comments' => true,
        'newComment' => true,
        'attachments' => true,
        'newAttachment' => true
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
        'contract' => [
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

            $this->assignDataToProps($data);
            // TODO: check for associated Objects => 'attachments' and 'comments'
            // TODO: check for new Attachment or Comment and link it to this Def
        } elseif (!empty($id)) {
            $this->ID = $id;
            try {
                $link = new MysqliDb(DB_CREDENTIALS);
                $link->where('defID', $this->ID);
                $data = $link->getOne('CDL', $this->fields);

                $this->assignDataToProps($data);
            } catch (Exception $e) {
                throw new Exception($e->getMessage);
            } finally {
                if (is_a($link, 'MysqliDb')) $link->disconnect();
            }
            // TODO: query for associated Objects => 'attachments' and 'comments'
        } elseif (!empty($data)) {
            $this->assignDataToProps($data);
            // TODO: the above should be a dedicated function
        } else {
            // no id or props = no good
            throw new Exception('What is this? You tried to instantiate a new Deficiency without passing any data or id');
        }

        //---------------------------------------------------------------------------------//
        // foreach ($this->data as $fieldName => $val) {
        //     if (empty($data[$fieldName])) continue;
        //     else $this->data[$fieldName] = $data[$fieldName];
        // }
        // // if createdBy, updatedBy, dateCreated not provided, set values for them
        // if (empty($this->data['updatedBy'])) $this->data['updatedBy'] = $_SESSION['userID'];

        // // TODO: check for defID before checking creation deets
        // // check creation details in db before setting them in obj
        // // if (empty($this->data['createdBy']) || empty($this->data['dateCreated'])) {
        //     // $link = new MysqliDb(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        //     // $link->where('defID', $this->data['defID']);
        //     // $creationStamp = $link->get('deficiency', ['createdBy', 'dateCreated']);
        // if (empty($this->data['createdBy'])) $this->data['createdBy'] = $_SESSION['userID'];
        // if (empty($this->data['dateCreated'])) $this->data['dateCreated'] = date('Y-m-d H:i:s');
        // // }
    }

    private function assignDataToProps($data) {
        foreach ($data as $key => $val) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $val;
            }
        }
    }
    
    public function __toString() {
        $props = [
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
            'contract' => $this->contract,
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
            'attachments' => $this->attachments,
            'newAttachment' => $this->newAttachment
        ];

        return print_r($props, true);
    }
    
    // TODO: add fn to handle relatedAsset, newComment, newAttachment
    public function insert() {
        $link = new MysqliDb(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        $cleanData = $this->filter_data();
        $cleanData = filter_var_array($cleanData, FILTER_SANITIZE_SPECIAL_CHARS);
        
        $newID = $link->insert('deficiency', $cleanData);
        $link->disconnect();
        
        return $newID;
    }
    
    public function update() {
        // validate against user $role
        $link = new MysqliDb(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        $cleanData = $this->filter_data();
        $cleanData = filter_var_array($cleanData, FILTER_SANITIZE_SPECIAL_CHARS);
        
        $link->where('defID', $this->data['defID']);
        $updateID = $link->update('deficiency', $cleanData) ? $this->data['defID'] : null;
        $link->disconnect();

        if (empty($updateID)) throw new Exception("There was a problem updating the record {$this->data['defID']}");

        return $updateID;
    }
    
    private function filter_data() { // TODO: this should filter NULL vals and keep '' vals
        $filterKeys = $this->filterKeys;
        $data = $this->data;
        
        foreach ($data as $fieldName => $val) { // TODO: can't I use array_filter sans callback here?
            if (empty($val) || !empty($filterKeys[$fieldName])) unset($data[$fieldName]);
        }
        
        return $data;
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
}

function getFilterOptions($link, $queryParams) {
    $options = [];
    foreach ($queryParams as $fieldName => $params) {
        $table = $params['table'];
        $fields = $params['fields'];
        if (!empty($params['join']))
            $link->join($params['join']['joinTable'], $params['join']['joinOn'], $params['join']['joinType']);
        if (!empty($params['where'])) {
            $whereParams = $params['where'];
            if (gettype($whereParams) === 'string')
            // if where is string, use it as raw where query
                $link->where($whereParams);
            elseif (!empty($whereParams['comparison']))
                $link->where($whereParams['field'], $whereParams['value'], $whereParams['comparison']);
            else $link->where($whereParams['field'], $whereParams['value']);
        }
        if (!empty($params['groupBy'])) $link->groupBy($params['groupBy']);
        if (!empty($params['orderBy'])) $link->orderBy($params['orderBy']);
        if ($result = $link->get($table, null, $fields)) {
            $options[$fieldName] = [];
            foreach ($result as $row) {
                $fieldNames = array_keys($row);
                $value = $row[$fieldNames[0]];
                if (count($fieldNames) > 1) $text = $row[$fieldNames[1]];
                else $text = $value;
                $options[$fieldName][$value] = $text;
            }
        } else {
            $options[$fieldName] = "Unable to retrieve $fieldName list";
        }
    }
    return $options;
}
