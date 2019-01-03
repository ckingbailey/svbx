-- BART closure info DOWN
ALTER TABLE BARTDL
    DROP dateClosed,
    DROP repo,
    DROP evidenceID,
    DROP evidenceType,
    DROP evidenceLink,
    DROP closureComment;