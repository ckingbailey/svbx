/* create bart view UP */
CREATE VIEW bart_def AS
    SELECT ID as id,
    stat.statusName AS status,
    date_created,
    descriptive_title_vta AS description,
    resolution_vta AS resolution,
    next.nextStepName AS nextStep
    FROM BARTDL bart
    JOIN status stat ON bart.status = stat.statusID
    JOIN bdNextStep next ON bart.next_step = next.nextStepName
