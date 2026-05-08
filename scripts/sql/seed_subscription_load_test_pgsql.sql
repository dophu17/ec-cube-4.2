\set ON_ERROR_STOP on
\set target_customers 50000
\set target_due_subscriptions 1000

BEGIN;

CREATE TEMP TABLE seed_cfg (
    target_customers int NOT NULL,
    target_due_subscriptions int NOT NULL
);

INSERT INTO seed_cfg (target_customers, target_due_subscriptions)
VALUES (:target_customers, :target_due_subscriptions);

DO $$
DECLARE
    v_target_customers int;
    v_target_due_subscriptions int;
    v_current_customers int;
    v_need_customers int;
    v_inserted_customers int := 0;
    v_template_customer_id int;
    v_template_base_order_id int;
    v_template_subtotal int := 1000;
    v_template_discount int := 0;
    v_template_shipping int := 0;
    v_template_tax int := 0;
    v_template_total int := 1000;
    v_current_due_loadtest_subscriptions int;
    v_need_subscriptions int;
    v_inserted_subscriptions int := 0;
BEGIN
    SELECT target_customers, target_due_subscriptions
    INTO v_target_customers, v_target_due_subscriptions
    FROM seed_cfg
    LIMIT 1;

    SELECT COUNT(*) INTO v_current_customers FROM dtb_customer;
    v_need_customers := GREATEST(v_target_customers - v_current_customers, 0);

    SELECT id
    INTO v_template_customer_id
    FROM dtb_customer
    ORDER BY id
    LIMIT 1;

    IF v_template_customer_id IS NULL THEN
        RAISE EXCEPTION 'Không có dữ liệu trong dtb_customer để làm template.';
    END IF;

    IF v_need_customers > 0 THEN
        INSERT INTO dtb_customer (
            customer_status_id,
            sex_id,
            job_id,
            country_id,
            pref_id,
            name01,
            name02,
            kana01,
            kana02,
            company_name,
            postal_code,
            addr01,
            addr02,
            email,
            phone_number,
            birth,
            password,
            salt,
            secret_key,
            first_buy_date,
            last_buy_date,
            buy_times,
            buy_total,
            note,
            reset_key,
            reset_expire,
            point,
            create_date,
            update_date,
            gmo_epsilon_credit_card_expiration_date,
            card_change_request_mail_send_date,
            discriminator_type
        )
        SELECT
            c.customer_status_id,
            c.sex_id,
            c.job_id,
            c.country_id,
            c.pref_id,
            'LoadTest' || gs::text,
            'Customer' || gs::text,
            c.kana01,
            c.kana02,
            c.company_name,
            c.postal_code,
            c.addr01,
            c.addr02,
            'loadtest+' || to_char(clock_timestamp(), 'YYYYMMDDHH24MISS') || '_' || gs::text || '@example.local',
            c.phone_number,
            c.birth,
            c.password,
            c.salt,
            md5(random()::text || clock_timestamp()::text || gs::text),
            c.first_buy_date,
            c.last_buy_date,
            c.buy_times,
            c.buy_total,
            c.note,
            NULL,
            NULL,
            COALESCE(c.point, 0),
            date_trunc('second', now()),
            date_trunc('second', now()),
            NULL,
            NULL,
            c.discriminator_type
        FROM dtb_customer c
        CROSS JOIN generate_series(1, v_need_customers) gs
        WHERE c.id = v_template_customer_id;

        GET DIAGNOSTICS v_inserted_customers = ROW_COUNT;
    END IF;

    -- Ưu tiên lấy template từ subscription đã có; fallback sang order mới nhất.
    SELECT
        s.base_order_id,
        s.subtotal_amount,
        s.discount_amount,
        s.shipping_fee,
        s.tax_amount,
        s.total_amount
    INTO
        v_template_base_order_id,
        v_template_subtotal,
        v_template_discount,
        v_template_shipping,
        v_template_tax,
        v_template_total
    FROM dtb_subscription s
    ORDER BY s.id
    LIMIT 1;

    IF v_template_base_order_id IS NULL THEN
        SELECT o.id
        INTO v_template_base_order_id
        FROM dtb_order o
        ORDER BY o.id DESC
        LIMIT 1;
    END IF;

    IF v_template_base_order_id IS NULL THEN
        RAISE EXCEPTION 'Không tìm thấy base order để tạo subscription test.';
    END IF;

    SELECT COUNT(*)
    INTO v_current_due_loadtest_subscriptions
    FROM dtb_subscription s
    WHERE s.gmo_member_id LIKE 'loadtest_member_%'
      AND s.status = 'active'
      AND s.next_billing_at <= now();

    v_need_subscriptions := GREATEST(v_target_due_subscriptions - v_current_due_loadtest_subscriptions, 0);

    IF v_need_subscriptions > 0 THEN
        WITH candidate_customers AS (
            SELECT c.id
            FROM dtb_customer c
            LEFT JOIN dtb_subscription s
              ON s.customer_id = c.id
             AND s.gmo_member_id LIKE 'loadtest_member_%'
            WHERE s.id IS NULL
            ORDER BY c.id DESC
            LIMIT v_need_subscriptions
        )
        INSERT INTO dtb_subscription (
            customer_id,
            base_order_id,
            status,
            plan_type,
            interval_count,
            next_billing_at,
            last_billed_at,
            cancelled_at,
            retry_count,
            max_retry,
            subtotal_amount,
            discount_amount,
            shipping_fee,
            tax_amount,
            total_amount,
            payment_gateway,
            gmo_member_id,
            gmo_card_seq,
            gmo_last_order_id,
            create_date,
            update_date,
            discriminator_type
        )
        SELECT
            cc.id,
            v_template_base_order_id,
            'active',
            'test_minute',
            10,
            date_trunc('second', now() - interval '1 minute'),
            NULL,
            NULL,
            0,
            3,
            v_template_subtotal,
            v_template_discount,
            v_template_shipping,
            v_template_tax,
            v_template_total,
            'gmo',
            'loadtest_member_' || cc.id::text,
            '0',
            NULL,
            date_trunc('second', now()),
            date_trunc('second', now()),
            'subscription'
        FROM candidate_customers cc;

        GET DIAGNOSTICS v_inserted_subscriptions = ROW_COUNT;
    END IF;

    RAISE NOTICE 'Customers hiện có: %, thêm mới: %, target: %',
        v_current_customers, v_inserted_customers, v_target_customers;
    RAISE NOTICE 'Due loadtest subscriptions hiện có: %, thêm mới: %, target: %',
        v_current_due_loadtest_subscriptions, v_inserted_subscriptions, v_target_due_subscriptions;
END
$$;

COMMIT;

-- Kết quả kiểm tra nhanh
SELECT COUNT(*) AS customer_count FROM dtb_customer;
SELECT COUNT(*) AS due_loadtest_subscription_count
FROM dtb_subscription
WHERE gmo_member_id LIKE 'loadtest_member_%'
  AND status = 'active'
  AND next_billing_at <= now();
