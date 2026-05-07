<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Subscription MVP tables (dtb_subscription, items, renewal orders, event log).
 */
final class Version20260507103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Subscription (GMO) core tables.';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if ($sm->tablesExist(['dtb_subscription'])) {
            return;
        }

        $platform = $this->connection->getDatabasePlatform()->getName();

        if (str_contains($platform, 'mysql')) {
            $this->addSql("CREATE TABLE dtb_subscription (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                customer_id INT UNSIGNED NOT NULL,
                base_order_id INT UNSIGNED NOT NULL,
                status VARCHAR(32) NOT NULL,
                plan_type VARCHAR(32) NOT NULL,
                interval_count SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                next_billing_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                last_billed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                cancelled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                retry_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                max_retry SMALLINT UNSIGNED NOT NULL DEFAULT 3,
                subtotal_amount INT NOT NULL,
                discount_amount INT NOT NULL DEFAULT 0,
                shipping_fee INT NOT NULL DEFAULT 0,
                tax_amount INT NOT NULL DEFAULT 0,
                total_amount INT NOT NULL,
                payment_gateway VARCHAR(32) NOT NULL DEFAULT 'gmo',
                gmo_member_id VARCHAR(255) DEFAULT NULL,
                gmo_card_seq VARCHAR(64) DEFAULT NULL,
                gmo_last_order_id VARCHAR(255) DEFAULT NULL,
                create_date DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                update_date DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                discriminator_type VARCHAR(255) NOT NULL DEFAULT 'subscription',
                INDEX idx_subscription_status_next (status, next_billing_at),
                INDEX idx_subscription_customer_status (customer_id, status),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB");

            $this->addSql("CREATE TABLE dtb_subscription_item (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                subscription_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED DEFAULT NULL,
                product_class_id INT UNSIGNED DEFAULT NULL,
                product_name_snapshot VARCHAR(255) NOT NULL,
                product_code_snapshot VARCHAR(255) DEFAULT NULL,
                quantity INT NOT NULL,
                unit_price_snapshot INT NOT NULL DEFAULT 0,
                tax_rate_snapshot DECIMAL(10, 2) DEFAULT NULL,
                is_combo SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                combo_code VARCHAR(64) DEFAULT NULL,
                create_date DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                update_date DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                discriminator_type VARCHAR(255) NOT NULL DEFAULT 'subscriptionitem',
                INDEX idx_subscription_item_sub (subscription_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB");

            $this->addSql("CREATE TABLE dtb_subscription_order (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                subscription_id INT UNSIGNED NOT NULL,
                order_id INT UNSIGNED DEFAULT NULL,
                billing_cycle_key VARCHAR(128) NOT NULL,
                billing_scheduled_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                billing_executed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                billing_status VARCHAR(32) NOT NULL,
                gmo_order_id VARCHAR(255) DEFAULT NULL,
                gmo_access_id VARCHAR(255) DEFAULT NULL,
                gmo_access_pass VARCHAR(255) DEFAULT NULL,
                gmo_tran_id VARCHAR(255) DEFAULT NULL,
                gmo_approve VARCHAR(255) DEFAULT NULL,
                gmo_status VARCHAR(64) DEFAULT NULL,
                error_code VARCHAR(255) DEFAULT NULL,
                error_message LONGTEXT DEFAULT NULL,
                create_date DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                update_date DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                discriminator_type VARCHAR(255) NOT NULL DEFAULT 'subscriptionorder',
                UNIQUE INDEX uniq_subscription_cycle (billing_cycle_key),
                INDEX idx_subscription_order_lookup (subscription_id, billing_status),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB");

            $this->addSql("CREATE TABLE dtb_subscription_event_log (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                subscription_id INT UNSIGNED NOT NULL,
                event_type VARCHAR(64) NOT NULL,
                payload LONGTEXT DEFAULT NULL,
                create_date DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                discriminator_type VARCHAR(255) NOT NULL DEFAULT 'subscriptioneventlog',
                INDEX idx_subscription_event_sub (subscription_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB");
        } elseif (str_contains($platform, 'postgres')) {
            // PostgreSQL DDL
            $this->addSql("CREATE TABLE dtb_subscription (
                id SERIAL NOT NULL,
                customer_id INT NOT NULL,
                base_order_id INT NOT NULL,
                status VARCHAR(32) NOT NULL,
                plan_type VARCHAR(32) NOT NULL,
                interval_count SMALLINT NOT NULL DEFAULT 1,
                next_billing_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                last_billed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                retry_count SMALLINT NOT NULL DEFAULT 0,
                max_retry SMALLINT NOT NULL DEFAULT 3,
                subtotal_amount INT NOT NULL,
                discount_amount INT NOT NULL DEFAULT 0,
                shipping_fee INT NOT NULL DEFAULT 0,
                tax_amount INT NOT NULL DEFAULT 0,
                total_amount INT NOT NULL,
                payment_gateway VARCHAR(32) NOT NULL DEFAULT 'gmo',
                gmo_member_id VARCHAR(255) DEFAULT NULL,
                gmo_card_seq VARCHAR(64) DEFAULT NULL,
                gmo_last_order_id VARCHAR(255) DEFAULT NULL,
                create_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                update_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                discriminator_type VARCHAR(255) NOT NULL DEFAULT 'subscription',
                PRIMARY KEY(id)
            )");
            $this->addSql('CREATE INDEX idx_subscription_status_next ON dtb_subscription (status, next_billing_at)');
            $this->addSql('CREATE INDEX idx_subscription_customer_status ON dtb_subscription (customer_id, status)');

            $this->addSql("CREATE TABLE dtb_subscription_item (
                id SERIAL NOT NULL,
                subscription_id INT NOT NULL,
                product_id INT DEFAULT NULL,
                product_class_id INT DEFAULT NULL,
                product_name_snapshot VARCHAR(255) NOT NULL,
                product_code_snapshot VARCHAR(255) DEFAULT NULL,
                quantity INT NOT NULL,
                unit_price_snapshot INT NOT NULL DEFAULT 0,
                tax_rate_snapshot NUMERIC(10, 2) DEFAULT NULL,
                is_combo SMALLINT NOT NULL DEFAULT 0,
                combo_code VARCHAR(64) DEFAULT NULL,
                create_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                update_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                discriminator_type VARCHAR(255) NOT NULL DEFAULT 'subscriptionitem',
                PRIMARY KEY(id)
            )");
            $this->addSql('CREATE INDEX idx_subscription_item_sub ON dtb_subscription_item (subscription_id)');

            $this->addSql("CREATE TABLE dtb_subscription_order (
                id SERIAL NOT NULL,
                subscription_id INT NOT NULL,
                order_id INT DEFAULT NULL,
                billing_cycle_key VARCHAR(128) NOT NULL,
                billing_scheduled_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                billing_executed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                billing_status VARCHAR(32) NOT NULL,
                gmo_order_id VARCHAR(255) DEFAULT NULL,
                gmo_access_id VARCHAR(255) DEFAULT NULL,
                gmo_access_pass VARCHAR(255) DEFAULT NULL,
                gmo_tran_id VARCHAR(255) DEFAULT NULL,
                gmo_approve VARCHAR(255) DEFAULT NULL,
                gmo_status VARCHAR(64) DEFAULT NULL,
                error_code VARCHAR(255) DEFAULT NULL,
                error_message TEXT DEFAULT NULL,
                create_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                update_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                discriminator_type VARCHAR(255) NOT NULL DEFAULT 'subscriptionorder',
                PRIMARY KEY(id)
            )");
            $this->addSql('CREATE UNIQUE INDEX uniq_subscription_cycle ON dtb_subscription_order (billing_cycle_key)');
            $this->addSql('CREATE INDEX idx_subscription_order_lookup ON dtb_subscription_order (subscription_id, billing_status)');

            $this->addSql("CREATE TABLE dtb_subscription_event_log (
                id SERIAL NOT NULL,
                subscription_id INT NOT NULL,
                event_type VARCHAR(64) NOT NULL,
                payload TEXT DEFAULT NULL,
                create_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                discriminator_type VARCHAR(255) NOT NULL DEFAULT 'subscriptioneventlog',
                PRIMARY KEY(id)
            )");
            $this->addSql('CREATE INDEX idx_subscription_event_sub ON dtb_subscription_event_log (subscription_id)');
        } else {
            // SQLite fallback (development) – không áp dụng cho môi trường Postgres/MySQL
            $this->addSql('CREATE TABLE dtb_subscription (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                customer_id INTEGER NOT NULL,
                base_order_id INTEGER NOT NULL,
                status VARCHAR(32) NOT NULL,
                plan_type VARCHAR(32) NOT NULL,
                interval_count SMALLINT NOT NULL DEFAULT 1,
                next_billing_at DATETIME NOT NULL,
                last_billed_at DATETIME DEFAULT NULL,
                cancelled_at DATETIME DEFAULT NULL,
                retry_count SMALLINT NOT NULL DEFAULT 0,
                max_retry SMALLINT NOT NULL DEFAULT 3,
                subtotal_amount INTEGER NOT NULL,
                discount_amount INTEGER NOT NULL DEFAULT 0,
                shipping_fee INTEGER NOT NULL DEFAULT 0,
                tax_amount INTEGER NOT NULL DEFAULT 0,
                total_amount INTEGER NOT NULL,
                payment_gateway VARCHAR(32) NOT NULL DEFAULT \'gmo\',
                gmo_member_id VARCHAR(255) DEFAULT NULL,
                gmo_card_seq VARCHAR(64) DEFAULT NULL,
                gmo_last_order_id VARCHAR(255) DEFAULT NULL,
                create_date DATETIME NOT NULL,
                update_date DATETIME NOT NULL,
                discriminator_type VARCHAR(255) NOT NULL DEFAULT \'subscription\'
            )');
            $this->addSql('CREATE INDEX idx_subscription_status_next ON dtb_subscription (status, next_billing_at)');
            $this->addSql('CREATE INDEX idx_subscription_customer_status ON dtb_subscription (customer_id, status)');

            $this->addSql('CREATE TABLE dtb_subscription_item (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                subscription_id INTEGER NOT NULL,
                product_id INTEGER DEFAULT NULL,
                product_class_id INTEGER DEFAULT NULL,
                product_name_snapshot VARCHAR(255) NOT NULL,
                product_code_snapshot VARCHAR(255) DEFAULT NULL,
                quantity INTEGER NOT NULL,
                unit_price_snapshot INTEGER NOT NULL DEFAULT 0,
                tax_rate_snapshot DOUBLE PRECISION DEFAULT NULL,
                is_combo SMALLINT NOT NULL DEFAULT 0,
                combo_code VARCHAR(64) DEFAULT NULL,
                create_date DATETIME NOT NULL,
                update_date DATETIME NOT NULL,
                discriminator_type VARCHAR(255) NOT NULL DEFAULT \'subscriptionitem\'
            )');
            $this->addSql('CREATE INDEX idx_subscription_item_sub ON dtb_subscription_item (subscription_id)');

            $this->addSql('CREATE TABLE dtb_subscription_order (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                subscription_id INTEGER NOT NULL,
                order_id INTEGER DEFAULT NULL,
                billing_cycle_key VARCHAR(128) NOT NULL,
                billing_scheduled_at DATETIME NOT NULL,
                billing_executed_at DATETIME DEFAULT NULL,
                billing_status VARCHAR(32) NOT NULL,
                gmo_order_id VARCHAR(255) DEFAULT NULL,
                gmo_access_id VARCHAR(255) DEFAULT NULL,
                gmo_access_pass VARCHAR(255) DEFAULT NULL,
                gmo_tran_id VARCHAR(255) DEFAULT NULL,
                gmo_approve VARCHAR(255) DEFAULT NULL,
                gmo_status VARCHAR(64) DEFAULT NULL,
                error_code VARCHAR(255) DEFAULT NULL,
                error_message CLOB DEFAULT NULL,
                create_date DATETIME NOT NULL,
                update_date DATETIME NOT NULL,
                discriminator_type VARCHAR(255) NOT NULL DEFAULT \'subscriptionorder\'
            )');
            $this->addSql('CREATE UNIQUE INDEX uniq_subscription_cycle ON dtb_subscription_order (billing_cycle_key)');
            $this->addSql('CREATE INDEX idx_subscription_order_lookup ON dtb_subscription_order (subscription_id, billing_status)');

            $this->addSql('CREATE TABLE dtb_subscription_event_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                subscription_id INTEGER NOT NULL,
                event_type VARCHAR(64) NOT NULL,
                payload CLOB DEFAULT NULL,
                create_date DATETIME NOT NULL,
                discriminator_type VARCHAR(255) NOT NULL DEFAULT \'subscriptioneventlog\'
            )');
            $this->addSql('CREATE INDEX idx_subscription_event_sub ON dtb_subscription_event_log (subscription_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        foreach (['dtb_subscription_event_log', 'dtb_subscription_order', 'dtb_subscription_item', 'dtb_subscription'] as $table) {
            if ($sm->tablesExist([$table])) {
                $this->addSql('DROP TABLE '.$table);
            }
        }
    }
}
