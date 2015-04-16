<?php

namespace Claroline\UserTrackingBundle\Migrations\pdo_oci;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/04/16 12:03:05
 */
class Version20150416120303 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_user_tracking_configuration (
                id NUMBER(10) NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'CLARO_USER_TRACKING_CONFIGURATION' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE CLARO_USER_TRACKING_CONFIGURATION ADD CONSTRAINT CLARO_USER_TRACKING_CONFIGURATION_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE CLARO_USER_TRACKING_CONFIGURATION_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER CLARO_USER_TRACKING_CONFIGURATION_AI_PK BEFORE INSERT ON CLARO_USER_TRACKING_CONFIGURATION FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT CLARO_USER_TRACKING_CONFIGURATION_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT CLARO_USER_TRACKING_CONFIGURATION_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'CLARO_USER_TRACKING_CONFIGURATION_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT CLARO_USER_TRACKING_CONFIGURATION_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            CREATE TABLE claro_user_tracking_widgets (
                usertrackingconfiguration_id NUMBER(10) NOT NULL, 
                widget_id NUMBER(10) NOT NULL, 
                PRIMARY KEY(
                    usertrackingconfiguration_id, widget_id
                )
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_434341A083D1626F ON claro_user_tracking_widgets (usertrackingconfiguration_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_434341A0FBE885E2 ON claro_user_tracking_widgets (widget_id)
        ");
        $this->addSql("
            ALTER TABLE claro_user_tracking_widgets 
            ADD CONSTRAINT FK_434341A083D1626F FOREIGN KEY (usertrackingconfiguration_id) 
            REFERENCES claro_user_tracking_configuration (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_user_tracking_widgets 
            ADD CONSTRAINT FK_434341A0FBE885E2 FOREIGN KEY (widget_id) 
            REFERENCES claro_widget (id) 
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_user_tracking_widgets 
            DROP CONSTRAINT FK_434341A083D1626F
        ");
        $this->addSql("
            DROP TABLE claro_user_tracking_configuration
        ");
        $this->addSql("
            DROP TABLE claro_user_tracking_widgets
        ");
    }
}