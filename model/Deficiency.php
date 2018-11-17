<?php
namespace SVBX;

use MysqliDb;

class Deficiency
{
    private $data = [
        'defID' => null,
        'safetyCert' => null,
        'systemAffected' => null,
        'location' => null,
        'specLoc' => null,
        'status' => null,
        'severity' => null,
        'dueDate' => null,
        'groupToResolve' => null,
        'requiredBy' => null,
        'contract' => null,
        'identifiedBy' => null,
        'defType' => null,
        'description' => null,
        'spec' => null,
        'actionOwner' => null,
        'evidenceType' => null,
        'repo' => null,
        'evidenceLink' => null,
        'oldID' => null,
        'closureComments' => null,
        'createdBy' => null, // validate: userID
        'updatedBy' => null, // validate: userID
        'dateCreated' => null, // validate: date (before lastUpdated, dateClosed?)
        'lastUpdated' => null,
        'dateClosed' => null, // validate against status || set
        'closureRequested' => null, // validate against status??
        'closureRequestedBy' => null, // validate: userID
        'assets' => [],
        'comments' => [],
        'newComment' => null,
        'attachments' => [],
        'newAttachment' => null
    ];
    
    private $filterKeys = [
        'defID' => true,
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
    public function __construct($id = null, array $data = [], $dbClass = 'MySqliDB') {
        foreach ($this->data as $fieldName => $val) {
            if (empty($data[$fieldName])) continue;
            else $this->data[$fieldName] = $data[$fieldName];
        }
        // if createdBy, updatedBy, dateCreated not provided, set values for them
        if (empty($this->data['updatedBy'])) $this->data['updatedBy'] = $_SESSION['userID'];

        // TODO: check for defID before checking creation deets
        // check creation details in db before setting them in obj
        // if (empty($this->data['createdBy']) || empty($this->data['dateCreated'])) {
            // $link = new MysqliDb(DB_HOST, DB_USER, DB_PWD, DB_NAME);
            // $link->where('defID', $this->data['defID']);
            // $creationStamp = $link->get('deficiency', ['createdBy', 'dateCreated']);
        if (empty($this->data['createdBy'])) $this->data['createdBy'] = $_SESSION['userID'];
        if (empty($this->data['dateCreated'])) $this->data['dateCreated'] = date('Y-m-d H:i:s');
        // }
    }
    
    public function __toString() {
        return print_r($this->data, true);
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
    
    private function filter_data() {
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
