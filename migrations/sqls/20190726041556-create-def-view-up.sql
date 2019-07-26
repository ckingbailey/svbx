/* create view UP */
CREATE VIEW deficiency AS
    SELECT CDL.defID AS _id,
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
    JOIN location loc ON CDL.location = loc.locationID
    JOIN severity sev ON CDL.severity = sev.severityID
    JOIN status stat ON CDL.status = stat.statusID
    JOIN system sys ON CDL.systemAffected = sys.systemID
    JOIN system grp ON CDL.groupToResolve = grp.systemID
    JOIN requiredBy req ON CDL.requiredBy = req.reqByID
    JOIN defType type ON CDL.defType = type.defTypeID
    JOIN cdlComments com ON CDL.defID = com.defID
    ORDER BY CDL.defID