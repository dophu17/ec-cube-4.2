<?php

namespace Plugin\RemisePayment42\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240718073752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $Table = $schema->getTable('plg_remise_payment4_remise_ac_type');
        if ($Table->hasColumn('stop_display')) {
            $this->addSql('UPDATE plg_remise_payment4_remise_ac_type SET stop_display = ? WHERE stop = 1 AND stop_display is null', ['マイページから解約不可']);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
