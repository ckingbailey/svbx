/* ADD safetyCert, identifiedBy to def view DOWN */
ALTER VIEW deficiency AS
    SELECT CDL.defID AS id,
    loc.locationName AS location,
    sev.severityName AS severity,
    stat.statusName AS status,
    sys.systemName AS systemAffected,
    grp.systemName AS groupToResolve,
    CDL.description AS description,
    CDL.specLoc AS specLoc,
    req.requiredBy AS requiredBy,
    DATE_FORMAT(CDL.dueDate, "%d %b %Y") AS dueDate,
    type.defTypeName AS defType,
    CDL.actionOwner AS actionOwner,
    com.cdlCommText AS comment
    FROM CDL
    LEFT JOIN location loc ON CDL.location = loc.locationID
    LEFT JOIN severity sev ON CDL.severity = sev.severityID
    LEFT JOIN status stat ON CDL.status = stat.statusID
    LEFT JOIN system sys ON CDL.systemAffected = sys.systemID
    LEFT JOIN system grp ON CDL.groupToResolve = grp.systemID
    LEFT JOIN requiredBy req ON CDL.requiredBy = req.reqByID
    LEFT JOIN defType type ON CDL.defType = type.defTypeID
    LEFT JOIN cdlComments com ON CDL.defID = com.defID