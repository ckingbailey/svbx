/* allow NULL repo DOWN */
ALTER TABLE CDL
    MODIFY repo
        INT(11) NOT NULL;