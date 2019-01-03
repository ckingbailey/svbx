-- BART closure info UP
ALTER TABLE BARTDL ADD (
    dateClosed date,
    repo int(11),
    evidenceID varchar(100),
    evidenceType int(11),
    evidenceLink varchar(255),
    closureComment varchar(1000)
);