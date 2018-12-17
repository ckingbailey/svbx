<?php
namespace SVBX;

use MysqliDb;

class BARTDeficiency extends Deficiency {
    protected $table = 'BARTDL';

    protected $props = [
        'ID' => null,
        'created_by' => null,
        'date_created' => null,
        'updated_by' => null,
        'creator' => null,
        'next_step' => null,
        'bic' => null,
        'status' => null,
        'descriptive_title_vta' => null,
        'root_prob_vta' => null,
        'resolution_vta' => null,
        'priority_vta' => null,
        'agree_vta' => null,
        'safety_cert_vta' => null,
        'resolution_disputed' => null,
        'structural' => null,
        'id_bart' => null,
        'description_bart' => null,
        'cat1_bart' => null,
        'cat2_bart' => null,
        'cat3_bart' => null,
        'level_bart' => null,
        'dateOpen_bart' => null,
        'dateClose_bart' => null,
        'Form_Modified' => null,
        'dateClosed' => null,
        'repo' => null,
        'evidenceID' => null,
        'evidenceType' => null,
        'evidenceLink' => null,
        'closureComment' => null,
        'attachments' => [],
        'newAttachment' => null,
        'comments' => [],
        'newComment' => null
    ];

    protected $fields = [
        'ID' => 'ID',
        'created_by' => 'created_by',
        'date_created' => 'date_created',
        'updated_by' => 'updated_by',
        'creator' => 'creator',
        'next_step' => 'next_step',
        'bic' => 'bic',
        'status' => 'status',
        'descriptive_title_vta' => 'descriptive_title_vta',
        'root_prob_vta' => 'root_prob_vta',
        'resolution_vta' => 'resolution_vta',
        'priority_vta' => 'priority_vta',
        'agree_vta' => 'agree_vta',
        'safety_cert_vta' => 'safety_cert_vta',
        'resolution_disputed' => 'resolution_disputed',
        'structural' => 'structural',
        'id_bart' => 'id_bart',
        'description_bart' => 'description_bart',
        'cat1_bart' => 'cat1_bart',
        'cat2_bart' => 'cat2_bart',
        'cat3_bart' => 'cat3_bart',
        'level_bart' => 'level_bart',
        'dateOpen_bart' => 'dateOpen_bart',
        'dateClose_bart' => 'dateClose_bart',
        'Form_Modified' => 'Form_Modified',
        'dateClosed' => 'dateClosed',
        'repo' => 'repo',
        'evidenceID' => 'evidenceID',
        'evidenceType' => 'evidenceType',
        'evidenceLink' => 'evidenceLink',
        'closureComment' => 'closureComment'
    ];

    protected $requiredField = [
        'creator',
        'status',
        'description',
        'root_prob_vta',
        'resolution_vta',
        'priority_vta',
        'safety_cert_vta'
    ];

    static protected $foreignKeys = [
        'safety_cert_vta' => [
            'table' => 'yesNo',
            'fields' => [ 'yesNoID', 'yesNoName' ]
        ],
        'next_step' => [
            'table' => 'bdNextStep',
            'fields' => [ 'bdNextStepID', 'nextStepName' ]
        ],
        'creator' => [
            'table' => 'bdParties',
            'fields' => [ 'partyID', 'partyName' ]
        ],
        'bic' => [
            'table' => 'bdParties',
            'fields' => [ 'partyID', 'partyName' ]
        ],
        'status' => [
            'table' => 'status',
            'fields' => [ 'statusID', 'statusName' ]
        ],
        'agree_vta' => [
            'table' => 'agreeDisagreee',
            'fields' => [ 'agreeDisagreeID', 'agreeDisagreeName' ]
        ],
        'evidenceType' => [
            'table' => 'evidenceType',
            'fields' => [ 'eviTypeID', 'eviTypeName' ]
        ],
        'repo' => [
            'table' => 'repo',
            'fields' => [ 'repoID', 'repoName' ]
        ]
    ];
}