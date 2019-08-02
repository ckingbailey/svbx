/* Replace with your SQL commands */
ALTER VIEW bart_def AS
    SELECT ID as id,
    stat.statusName AS status,
    bart.date_created,
    descriptive_title_vta AS description,
    resolution_vta AS resolution,
    next.nextStepName AS nextStep,
    com.bdCommText AS comment
    FROM BARTDL bart
    LEFT JOIN status stat ON bart.status = stat.statusID
    LEFT JOIN bdNextStep next ON bart.next_step = next.bdNextStepID
    LEFT JOIN bartdlComments com ON bart.ID = com.bartdlID
