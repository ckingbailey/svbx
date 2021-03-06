/* def view add comment_date UP */
CREATE OR REPLACE VIEW deficiency AS
    SELECT CDL.defID AS id,
    LPAD(bartDefID, 4, '0') as bartDefID,
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
    CDL.safetyCert safetyCert,
    repo.repoName repo,
    evi.eviTypeName evidenceType,
    CDL.evidenceID evidenceID,
    CDL.evidenceLink evidenceLink,
    CDL.FinalGroup FinalGroup,
    CDL.closureComments closureComments,
    com.cdlCommText AS comment,
    com.date_created AS comment_date
    FROM CDL
    LEFT JOIN location loc ON CDL.location = loc.locationID
    LEFT JOIN severity sev ON CDL.severity = sev.severityID
    LEFT JOIN status stat ON CDL.status = stat.statusID
    LEFT JOIN system sys ON CDL.systemAffected = sys.systemID
    LEFT JOIN system grp ON CDL.groupToResolve = grp.systemID
    LEFT JOIN requiredBy req ON CDL.requiredBy = req.reqByID
    LEFT JOIN defType type ON CDL.defType = type.defTypeID
    LEFT JOIN repo ON CDL.repo = repo.repoID
    LEFT JOIN evidenceType evi ON CDL.evidenceType = evi.eviTypeID
    LEFT JOIN cdlComments com ON CDL.defID = com.defID
