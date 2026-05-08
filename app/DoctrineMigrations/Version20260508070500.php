<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508070500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pre-billing fulfillment timestamps for subscriptions.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        if (!str_contains($platform, 'postgres')) {
            return;
        }

        $this->addSql('ALTER TABLE dtb_subscription ADD COLUMN IF NOT EXISTS next_fulfillment_at TIMESTAMPTZ DEFAULT NULL');
        $this->addSql('ALTER TABLE dtb_subscription ADD COLUMN IF NOT EXISTS last_fulfilled_at TIMESTAMPTZ DEFAULT NULL');

        // Backfill cho dữ liệu cũ: fulfillment = billing + 1 day (pre-billing T-1).
        $this->addSql("UPDATE dtb_subscription SET next_fulfillment_at = next_billing_at + INTERVAL '1 day' WHERE next_fulfillment_at IS NULL");
        $this->addSql('ALTER TABLE dtb_subscription ALTER COLUMN next_fulfillment_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        if (!str_contains($platform, 'postgres')) {
            return;
        }

        $this->addSql('ALTER TABLE dtb_subscription DROP COLUMN IF EXISTS last_fulfilled_at');
        $this->addSql('ALTER TABLE dtb_subscription DROP COLUMN IF EXISTS next_fulfillment_at');
    }
}
