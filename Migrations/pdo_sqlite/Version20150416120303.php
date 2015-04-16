<?php

namespace Claroline\UserTrackingBundle\Migrations\pdo_sqlite;

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
                id INTEGER NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE TABLE claro_user_tracking_widgets (
                usertrackingconfiguration_id INTEGER NOT NULL, 
                widget_id INTEGER NOT NULL, 
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
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_user_tracking_configuration
        ");
        $this->addSql("
            DROP TABLE claro_user_tracking_widgets
        ");
    }
}