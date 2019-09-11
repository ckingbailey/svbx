/* allow NULL repo UP */
ALTER TABLE CDL
    MODIFY repo
        INT(11) DEFAULT NULL;