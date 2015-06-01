<?php

namespace Claroline\UserTrackingBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/06/01 10:59:04
 */
class Version20150601105902 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_user_tracking_tab (
                id INT AUTO_INCREMENT NOT NULL, 
                owner_id INT NOT NULL, 
                home_tab_id INT NOT NULL, 
                user_id INT DEFAULT NULL, 
                group_id INT DEFAULT NULL, 
                role_id INT DEFAULT NULL, 
                INDEX IDX_6A5473347E3C61F9 (owner_id), 
                INDEX IDX_6A5473347D08FA9E (home_tab_id), 
                INDEX IDX_6A547334A76ED395 (user_id), 
                INDEX IDX_6A547334FE54D947 (group_id), 
                INDEX IDX_6A547334D60322AC (role_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE claro_user_tracking_configuration (
                id INT AUTO_INCREMENT NOT NULL, 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE claro_user_tracking_widgets (
                usertrackingconfiguration_id INT NOT NULL, 
                widget_id INT NOT NULL, 
                INDEX IDX_434341A083D1626F (usertrackingconfiguration_id), 
                INDEX IDX_434341A0FBE885E2 (widget_id), 
                PRIMARY KEY(
                    usertrackingconfiguration_id, widget_id
                )
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE claro_user_tracking_tab 
            ADD CONSTRAINT FK_6A5473347E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_user_tracking_tab 
            ADD CONSTRAINT FK_6A5473347D08FA9E FOREIGN KEY (home_tab_id) 
            REFERENCES claro_home_tab (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_user_tracking_tab 
            ADD CONSTRAINT FK_6A547334A76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id) 
            ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE claro_user_tracking_tab 
            ADD CONSTRAINT FK_6A547334FE54D947 FOREIGN KEY (group_id) 
            REFERENCES claro_group (id) 
            ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE claro_user_tracking_tab 
            ADD CONSTRAINT FK_6A547334D60322AC FOREIGN KEY (role_id) 
            REFERENCES claro_role (id) 
            ON DELETE SET NULL
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
            DROP FOREIGN KEY FK_434341A083D1626F
        ");
        $this->addSql("
            DROP TABLE claro_user_tracking_tab
        ");
        $this->addSql("
            DROP TABLE claro_user_tracking_configuration
        ");
        $this->addSql("
            DROP TABLE claro_user_tracking_widgets
        ");
    }
}