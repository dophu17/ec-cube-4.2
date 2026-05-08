<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Normalize subscription datetime columns to timestamptz (UTC-safe).
 */
final class Version20260507120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize subscription datetime columns to timestamptz.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        if (!str_contains($platform, 'postgres')) {
            return;
        }

        $this->convertIfNeeded('dtb_subscription', 'next_billing_at');
        $this->convertIfNeeded('dtb_subscription', 'last_billed_at');
        $this->convertIfNeeded('dtb_subscription', 'cancelled_at');
        $this->convertIfNeeded('dtb_subscription', 'create_date');
        $this->convertIfNeeded('dtb_subscription', 'update_date');

        $this->convertIfNeeded('dtb_subscription_order', 'billing_scheduled_at');
        $this->convertIfNeeded('dtb_subscription_order', 'billing_executed_at');
        $this->convertIfNeeded('dtb_subscription_order', 'create_date');
        $this->convertIfNeeded('dtb_subscription_order', 'update_date');

        $this->convertIfNeeded('dtb_subscription_event_log', 'create_date');
    }

    public function down(Schema $schema): void
    {
        // irreversible normalization
    }

    private function convertIfNeeded(string $table, string $column): void
    {
        $type = $this->connection->fetchOne(
            "SELECT data_type
             FROM information_schema.columns
             WHERE table_name = :table AND column_name = :column",
            ['table' => $table, 'column' => $column]
        );

        if ('timestamp with time zone' === $type || null === $type) {
            return;
        }

        $this->addSql(sprintf(
            "ALTER TABLE %s ALTER COLUMN %s TYPE timestamptz USING %s AT TIME ZONE 'UTC'",
            $table,
            $column,
            $column
        ));
    }
}

