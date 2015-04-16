<?php

namespace Claroline\UserTrackingBundle\Migrations\pdo_sqlsrv;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/04/16 12:03:06
 */
class Version20150416120303 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_user_tracking_configuration (
                id INT IDENTITY NOT NULL, 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE TABLE claro_user_tracking_widgets (
                usertrackingconfiguration_id INT NOT NULL, 
                widget_id INT NOT NULL, 
                PRIMARY KEY (
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