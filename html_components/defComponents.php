<?php
$attachmentFormats = preg_replace('/\s+/', ' ', file_get_contents('allowedFormats.csv'));

function returnSectionHeading($innerHTML) {
    return "<h5 class='grey-bg pad'>$innerHTML</h5>";
}

function returnCollapseHeading($id, $text, $expanded = false) {
    $collapsed = $expanded ? '' : 'collapsed';
    return "
    <a data-toggle='collapse' href='#$id' role='button' aria-expanded='$expanded' aria-controls='$id' class='$collapsed'>
        $text
        <i class='typcn typcn-arrow-sorted-down'></i>
    </a>";
}

/*
** @param (string) $sectionHeading = the heading element of seciont, with title
** @param (string) $id = the id of the element to be collapsed
** @param (string) $content = the html content of the collapsible section
** @return (string) html content complete with heading, button, and collapse content
*/
function returnCollapseSection($sectionName, $id, $content, $addClasses = '', $expanded = false) {
    $collapse = $expanded ? '' : 'collapse';
    $classList = $addClasses ? $collapse.' '.$addClasses : $collapse;
    $section = "%s<section id='%s' class='$classList'>%s</section>";

    $sectionHeading = returnSectionHeading(returnCollapseHeading($id, $sectionName, $expanded));
    return sprintf($section, $sectionHeading, $id, $content);
}

function printSection($sectionTitle, $content) {
    print returnSectionHeading($sectionTitle);
    print $content;
}

function iterateRows(array $rowGroup) {
    $group = '';
    foreach ($rowGroup as $row) {
        $options = count($row) === 1 ? ['colWd' => 6] : [];
        $group .= returnRow($row, $options);
    }
    return $group;
}

function returnLabel($for, $text, $required = '', $str = "<label for='%s'%s>%s</label>") {
    $requiredAttr = $required ? " class='required'" : '';
    return sprintf($str, $for, $requiredAttr, $text);
}

function checkboxLabel($for, $text, $required = '') {
    $str =  "<label for='%s' class='form-check-label check-label-left'%s>%s</label>";
    return returnLabel($for, $text, $required, $str);
}

function returnSubarrays(array $arr, $num, $key) {
    $reducer = function ($acc, $el) use ($num, $key) {
        static $i = 0;

        $index = floor($i/$num);

        $acc[$index][] = $el[$key];

        $i++;

        return $acc;
    };

    return array_reduce($arr, $reducer, array());
}

function wrapArrayEls(array $arr, $format) {
    $acc = array();

    foreach($arr as $el) {
        if (is_array($el)) {
            $acc[] = wrapArrayEls($el, $format);
        } else $acc[] = sprintf($format, $el);
    }

    return $acc;
}

function returnPhotoSection($pics, $imgFormat) {
    $arr = returnSubarrays($pics, 3, 'pathToFile');
    $acc = '';

    foreach (wrapArrayEls(
        $arr,
        $imgFormat
        ) as $el)
    {
        $acc .= returnRow($el, ['colWd' => 4]);
    }

    return $acc;
}


function getAttachments($cnxn, $id) {
    $sql = "SELECT bdaFilepath, filename from bartdlAttachments WHERE bartdlID = ?";
    if (!$stmt = $cnxn->prepare($sql)) printSqlErrorAndExit($cnxn, $sql);
    if (!$stmt->bind_param('i', $id)) printSqlErrorAndExit($stmt, $sql);
    if (!$stmt->execute()) printSqlErrorAndExit($stmt, $sql);
    $attachments = stmtBindResultArray($stmt) ?: [];
    $stmt->close();
    return $attachments;
}

function renderAttachmentsAsAnchors(array $attachments = []) {
    $list = '';
    if (count($attachments)) {
        foreach ($attachments as $attachment) {
            $list .= "<li><a href='"
                . htmlentities(stripcslashes($attachment['bdaFilepath']))."'>"
                . htmlentities(stripcslashes($attachment['filename']))."</a></li>";
        }
    }
    return sprintf("<ul class='pl-0 mb-0'>%s</ul>", $list);
}

function returnCommentsHTML(array $comments) {
    $content = '';
    $commentFormat = "
        <div class='thin-grey-border pad mb-3'>
            <h6 class='d-flex flex-row justify-content-between text-secondary'><span>%s</span><span>%s</span></h6>
            <p>%s</p>
        </div>";

    foreach ($comments as $comment) {
        $userFullName = $comment['firstname'].' '.$comment['lastname'];
        $text = stripcslashes($comment['cdlCommText']);
        $content .= sprintf($commentFormat, $userFullName, $comment['date_created'], $text);
    }

    return $content;
}

/* takes:
    1. user first- + lastname;
    2. date_created;
    3. comment text
*/
$commentFormat = "
    <div class='thin-grey-border pad mb-3'>
        <h6 class='d-flex flex-row justify-content-between text-secondary'><span>%s</span><span>%s</span></h6>
        <p>%s</p>
    </div>";

$requiredElements = [
    'safetyCert' => [
        "label" => returnLabel('safetyCert', 'Safety Certifiable', 1),
        "tagName" => 'select',
        'element' => "<select name='safetyCert' id='safetyCert' class='form-control' required>%s</select>",
        "type" => '',
        "query" => "SELECT YesNoID, YesNoName FROM yesNo ORDER BY YesNoName",
        'value' => ''
    ],
    'systemAffected' => [
        "label" => returnLabel('systemAffected', 'System Affected', 1),
        "tagName" => 'select',
        'element' => "<select name='systemAffected' id='systemAffected' class='form-control' required>%s</select>",
        "type" => '',
        "query" => "SELECT SystemID, SystemName FROM system ORDER BY SystemName",
        'value' => ''
    ],
    'location' => [
        "label" => returnLabel('location', 'General Location', 1),
        "tagName" => 'select',
        'element' => "<select name='location' id='location' class='form-control' required>%s</select>",
        "type" => '',
        "query" => "SELECT LocationID, LocationName FROM location ORDER BY LocationName",
        'value' => ''
    ],
    'specLoc' => [
        "label" => returnLabel('specLoc', 'Specific Location', 1),
        "tagName" => "input",
        "element" => "<input type='text' name='specLoc' id='specLoc' value='%s' class='form-control' required>",
        "type" => 'text',
        "name" => 'SpecLoc',
        "id" => 'SpecLoc',
        "query" => null,
        'value' => ''
    ],
    'status' => [
        "label" => returnLabel('status', 'Status', 1),
        "tagName" => "select",
        "element" => "<select name='status' id='status' class='form-control' required>%s</select>",
        "type" => null,
        "query" => "SELECT StatusID, StatusName FROM status WHERE (StatusID <> 3 AND StatusID <> 4) ORDER BY StatusID",
        'value' => ''
    ],
    'severity' => [
        "label" => returnLabel('severity', 'Severity', 1),
        "tagName" => "select",
        "element" => "<select name='severity' id='severity' class='form-control' required>%s</select>",
        "type" => null,
        "query" => "SELECT SeverityID, SeverityName FROM severity ORDER BY SeverityName",
        'value' => ''
    ],
    'dueDate' => [
        "label" => returnLabel('dueDate', 'To be resolved by', 1),
        "tagName" => "input",
        "element" => "<input type='date' name='dueDate' id='dueDate' value='%s' class='form-control' required>",
        "type" => 'date',
        "query" => null,
        'value' => ''
    ],
    'groupToResolve' =>[
        "label" => returnLabel('groupToResolve', 'Group to Resolve', 1),
        "tagName" => "select",
        "element" => "<select name='groupToResolve' id='groupToResolve' class='form-control' required>%s</select>",
        "type" => null,
        "query" => "SELECT SystemID, SystemName FROM system ORDER BY SystemName",
        'value' => ''
    ],
    'requiredBy' => [
        "label" => returnLabel('requiredBy', 'Required for', 1),
        "tagName" => "select",
        "element" => "<select name='requiredBy' id='requiredBy' class='form-control' required>%s</select>",
        "type" => null,
        "query" => "SELECT ReqByID, RequiredBy FROM requiredBy ORDER BY RequiredBy",
        'value' => ''
    ],
    'contractID' => [
        'label' => returnLabel('contractID', 'Contract', 1),
        'tagName' => 'select',
        'element' => "<select name='contractID' id='contractID' class='form-control' required>%s</select>",
        'type' => null,
        'query' => "SELECT contractID, contractName FROM contract ORDER BY contractID",
        'value' => ''
    ],
    'identifiedBy' => [
        "label" => returnLabel('identifiedBy', 'Identified By', 1),
        "tagName" => "input",
        "element" => "<input type='text' name='identifiedBy' id='identifiedBy' class='form-control' value='%s' required>",
        "type" => 'text',
        "query" => null,
        'value' => ''
    ],
    'defType' => [
        'label' => returnLabel('defType', 'Deficiency type', 1),
        'tagName' => "select",
        'element' => "<select name='defType' id='defType' class='form-control' required>%s</select>",
        'type' => null,
        'query' => 'SELECT defTypeID, defTypeName FROM defType',
        'value' => ''
    ],
    'description' => [
        "label" => returnLabel('description', 'Deficiency description', 1),
        "tagName" => "textarea",
        "element" => "<textarea name='description' id='description' class='form-control' maxlength='1000' required>%s</textarea>",
        "type" => null,
        "query" => null,
        'value' => ''
    ]
];

$optionalElements = [
    'spec' => [
        "label" => returnLabel('spec', 'Spec or Code'),
        "tagName" => "input",
        "element" => "<input type='text' name='spec' id='spec' value='%s' class='form-control'>",
        "type" => 'text',
        "query" => null,
        'value' => ''
    ],
    'actionOwner' => [
        "label" => returnLabel('actionOwner', 'Action Owner'),
        "tagName" => "input",
        "element" => "<input type='text' name='actionOwner' id='actionOwner' value='%s' class='form-control'>",
        "type" => 'text',
        "query" => null,
        'value' => ''
    ],
    'oldID' => [
        "label" => returnLabel('oldID', 'Old Id'),
        "tagName" => "input",
        "element" => "<input type='text' name='oldID' id='oldID' value='%s' class='form-control'>",
        "type" => 'text',
        "query" => null,
        'value' => ''
    ],
    'CDL_pics' => [
        'label' => returnLabel('CDL_pics', 'Upload Photo'),
        'tagName' => 'input',
        'element' => "<input type='file' accept='image/*' name='CDL_pics' id='CDL_pics' class='form-control form-control-file'>",
        'type' => 'file',
        'query' => null
    ],
    'cdlCommText' => [
        "label" => returnLabel('cdlCommText', 'Add comment'),
        "tagName" => "textarea",
        "element" => "<textarea name='cdlCommText' id='cdlCommText' class='form-control' maxlength='1000'>%s</textarea>",
        "type" => null,
        "query" => null,
        'value' => ''
    ]
];

$closureElements = [
    'evidenceType' => [
        "label" => returnLabel('evidenceType', 'Evidence Type'),
        "tagName" => 'select',
        'element' => "<select name='evidenceType' id='evidenceType' class='form-control'>%s</select>",
        "type" => '',
        "query" => "SELECT EviTypeID, EviTypeName FROM evidenceType ORDER BY EviTypeName",
        'value' => ''
    ],
    'repo' => [
        'label' => returnLabel('repo', 'Evidence Repository'),
        'tagName' => 'select',
        'element' => "<select name='repo' id='repo' class='form-control'>%s</select>",
        'type' => '',
        'query' => "SELECT RepoID, RepoName FROM repo ORDER BY RepoName",
        'value' => ''
    ],
    'evidenceLink' => [
        'label' => returnLabel('evidenceLink', 'Repository Number'),
        'tagName' => "input",
        'element' => "<input type='text' name='evidenceLink' id='evidenceLink' class='form-control' value='%s'>",
        'type' => 'text',
        'query' => null,
        'value' => ''
    ],
    'closureComments' => [
        "label" => returnLabel('closureComments', 'Closure Comments'),
        "tagName" => "textarea",
        "element" => "<textarea name='closureComments' id='closureComments' class='form-control' maxlength='1000'>%s</textarea>",
        "type" => null,
        "query" => null,
        'value' => ''
    ]
];

$bartDefEls = [ 'topElement' => &$topElements, 'vtaELements' => &$vtaElements, 'bartElements' => &$bartElements ];

$generalElements = [
    'creator' => [
        'label' => returnLabel('creator', 'Creator', 'required'),
        'tagName' => 'select',
        'element' => "<select name='creator' id='creator' class='form-control' required>%s</select>",
        'value' => '',
        'query' => "SELECT partyID, partyName from bdParties WHERE partyName <> '' ORDER BY partyID"
    ],
    'next_step' => [
        'label' => returnLabel('next_step', 'Next step'),
        'tagName' => 'select',
        'element' => "<select name='next_step' id='next_step' class='form-control'>%s</select>",
        'query' => "SELECT bdNextStepID, nextStepName FROM bdNextStep WHERE nextStepName <> '' ORDER BY bdNextStepID",
        'value' => ''
    ],
    'bic' => [
        'label' => returnLabel('bic', 'Ball in court'),
        'tagName' => 'select',
        'element' => "<select name='bic' id='bic' class='form-control'>%s</select>",
        'query' => "SELECT partyID, partyName from bdParties WHERE partyName <> '' ORDER BY partyID",
        'value' => ''
    ],
    'status' => [
        'label' => returnLabel('status', 'Status', 1),
        'tagName' => 'select',
        'element' => "<select name='status' id='status' class='form-control' required>%s</select>",
        'query' => "SELECT statusID, statusName from status WHERE statusID <> 3",
        'value' => ''
    ],
    'descriptive_title_vta' => [
        'label' => returnLabel('descriptive_title_vta', 'Description', 'required'),
        'tagName' => 'textarea',
        'element' => "<textarea name='descriptive_title_vta' id='descriptive_title_vta' class='form-control' maxlength='1000' required>%s</textarea>",
        'query' => null
    ]
];

$vtaElements = [
    'root_prob_vta' => [
        'label' => returnLabel('root_prob_vta', 'Root problem', true),
        'tagName' => 'textarea',
        'element' => "<textarea name='root_prob_vta' id='root_prob_vta' class='form-control' required>%s</textarea>",
        'query' => null
    ],
    'resolution_vta' => [
        'label' => returnLabel('resolution_vta', 'Resolution', 1),
        'tagName' => 'textarea',
        'element' => "<textarea name='resolution_vta' id='resolution_vta' class='form-control' required>%s</textarea>",
        'query' => null
    ],
    'priority_vta' => [
        'label' => returnLabel('priority_vta', 'Priority', 1),
        'tagName' => 'select',
        'element' => "<select name='priority_vta' id='priority_vta' class='form-control' required>%s</select>",
        'value' => '',
        'query' => [ 1, 2, 3 ]
    ],
    'agree_vta' => [
        'label' => returnLabel('agree_vta', 'Agree'),
        'tagName' => 'select',
        'element' => "<select name='agree_vta' id='agree_vta' class='form-control'>%s</select>",
        'value' => '',
        'query' => "SELECT agreeDisagreeID, agreeDisagreeName FROM agreeDisagree WHERE agreeDisagreeName <> ''"
    ],
    'safety_cert_vta' => [
        'label' => returnLabel('safety_cert_vta', 'Safety Certiable', 1),
        'tagName' => 'select',
        'element' => "<select name='safety_cert_vta' id='safety_cert_vta' class='form-control' required>%s</select>",
        'value' => '',
        'query' => 'SELECT yesNoID, yesNoName from yesNo'
    ],
    'bartdlAttachments' => [
        'label' => returnLabel('bartdlAttachments', 'Attachments'),
        'element' => "<div class='border-radius thin-grey-border pad scroll-y' style='height: 6.8rem'>%s</div>",
        'query' => "SELECT filepath, filename from bartdlAttachments WHERE bartdlID = ?"
    ],
    'attachment' => [
        'label' => returnLabel('attachment', 'Upload attachment'),
        'tagName' => 'input',
        'type' => 'file',
        'element' => "
            <input name='attachment' id='attachment' type='file' accept='$attachmentFormats' class='form-control'>
            <label class='text-red'>max. allowed file size 4Mb</label>"
    ],
    'bdCommText' => [
        'label' => returnLabel('bdCommText', 'Add comment'),
        'tagName' => 'textarea',
        'element' => "<textarea name='bdCommText' id='bdCommText' class='form-control'>%s</textarea>",
        'value' => ''
    ],
    'resolution_disputed' => [
        'label' => checkboxLabel('resolution_disputed', 'Resolution disputed'),
        'tagName' => 'input',
        'type' => 'checkbox',
        'element' => "<input name='resolution_disputed' id='resolution_disputed' type='checkbox' value='1' class='form-check-input' %s>",
        'value' => '',
        'query' => null
    ],
    'structural' => [
        'label' => checkboxLabel('structural', 'Stuctural'),
        'tagName' => 'input',
        'type' => 'checkbox',
        'element' => "<input name='structural' id='structural' type='checkbox' value='1' class='form-check-input' %s>",
        'value' => '',
        'query' => null
    ]
];

$bartElements = [
    'id_bart' => [
        'label' => returnLabel('id_bart', 'BART ID'),
        'tagName' => 'input',
        'type' => 'text',
        'element' => "<input name='id_bart' id='id_bart' type='text' value='%s' class='form-control'>",
        'value' => ''
    ],
    'description_bart' => [
        'label' => returnLabel('description_bart', 'Description'),
        'tagName' => 'textarea',
        'element' => "<textarea name='description_bart' id='description_bart' maxlength='1000' class='form-control'>%s</textarea>",
        'value' => ''
    ],
    'cat1_bart' => [
        'label' => returnLabel('cat1_bart', 'Category 1'),
        'tagName' => 'input',
        'type' => 'text',
        'element' => "<input name='cat1_bart' id='cat1_bart' type='text' maxlength='3' value='%s' class='form-control'>",
        'value' => ''
    ],
    'cat2_bart' => [
        'label' => returnLabel('cat2_bart', 'Category 2'),
        'tagName' => 'input',
        'type' => 'text',
        'element' => "<input name='cat2_bart' id='cat2_bart' type='text' maxlength='3' value='%s' class='form-control'>",
        'value' => ''
    ],
    'cat3_bart' => [
        'label' => returnLabel('cat3_bart', 'Category 3'),
        'tagName' => 'input',
        'type' => 'text',
        'element' => "<input name='cat3_bart' id='cat3_bart' type='text' maxlength='3' value='%s' class='form-control'>",
        'value' => ''
    ],
    'level_bart' => [
        'label' => returnLabel('level_bart', 'Level'),
        'tagName' => 'select',
        'element' => "<select name='level_bart' id='level_bart' class='form-control'>%s</select>",
        'value' => '',
        'query' => [ 'PROGRAM' => 'PROGRAM', 'PROJECT' => 'PROJECT' ]
    ],
    'dateOpen_bart' => [
        'label' => returnLabel('dateOpen_bart', 'Date open'),
        'tagName' => 'input',
        'type' => 'date',
        'element' => "<input name='dateOpen_bart' id='dateOpen_bart' type='date' value='%s' class='form-control'>",
        'value' => ''
    ],
    'dateClose_bart' => [
        'label' => returnLabel('dateClose_bart', 'Date closed'),
        'tagName' => 'input',
        'type' => 'date',
        'value' => '',
        'element' => "<input name='dateClose_bart' id='dateClose_bart' type='date' value='%s' class='form-control'>"
    ]
];
