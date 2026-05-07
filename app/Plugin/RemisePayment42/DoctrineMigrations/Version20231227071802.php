<?php

namespace Plugin\RemisePayment42\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231227071802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE dtb_payment SET payment_method = 'コンビニ・電子マネー・QRコード・銀行決済' WHERE id = (SELECT id FROM plg_remise_payment4_remise_payment WHERE kind = '2') AND payment_method = 'コンビニ・電子マネー・銀行決済'");
    }

    public function down(Schema $schema): void
    {
    }
}
