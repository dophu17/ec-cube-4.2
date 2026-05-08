# Load Test Data cho Subscription (PostgreSQL)

Tài liệu này giúp bạn fake dữ liệu để test tải theo kịch bản:

- Tổng customer trong DB: **50.000**
- Subscription đến hạn để chạy cron: **1.000**

Script chỉ dùng cho môi trường test/staging.

## 1) File script

- `scripts/sql/seed_subscription_load_test_pgsql.sql`

Script dạng "top-up":

- Nếu customer chưa đủ 50.000 thì tự thêm cho đủ.
- Nếu subscription test đến hạn chưa đủ 1.000 thì tự thêm cho đủ.
- Có thể chạy lại nhiều lần, script chỉ bù thêm phần thiếu.

## 2) Chạy script

Từ thư mục project:

`-f scripts/...` bên trong container **không thấy** file host. Dùng redirect từ máy của bạn:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.pgsql.yml \
  exec -T postgres psql -U dbuser -d eccubedb \
  < scripts/sql/seed_subscription_load_test_pgsql.sql
```

## 3) Đổi số lượng khi cần

Mặc định script dùng:

- `target_customers = 50000`
- `target_due_subscriptions = 1000`

Bạn có thể override ngay khi chạy:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.pgsql.yml \
  exec -T postgres psql -U dbuser -d eccubedb \
  -v target_customers=50000 \
  -v target_due_subscriptions=1000 \
  < scripts/sql/seed_subscription_load_test_pgsql.sql
```

## 4) Verify nhanh

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.pgsql.yml \
  exec -T postgres psql -U dbuser -d eccubedb -c "SELECT COUNT(*) FROM dtb_customer;"

docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.pgsql.yml \
  exec -T postgres psql -U dbuser -d eccubedb -c "SELECT COUNT(*) FROM dtb_subscription WHERE gmo_member_id LIKE 'loadtest_member_%' AND status='active' AND next_billing_at <= now();"
```

## 5) Chạy batch test

Để test logic xử lý (không charge thật ra GMO), đảm bảo `.env.local` đang để:

- `GMO_MOCK_MODE=1`

Sau đó chạy:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.pgsql.yml \
  exec -T ec-cube php bin/console subscription:run --limit=5
```

Hoặc để cron chạy theo cấu hình ổn định đã chốt: **5 phút + limit=5**.

## 6) Lưu ý quan trọng

- Script dùng dữ liệu 1 customer mẫu hiện có để clone các cột bắt buộc.
- Subscription test được đánh dấu bằng `gmo_member_id` dạng `loadtest_member_<customer_id>`.
- `base_order_id` của subscription test lấy từ subscription có sẵn đầu tiên; nếu không có thì lấy order mới nhất.
- Chỉ chạy trên môi trường test/staging, không dùng trực tiếp trên production.

## 6.1) Xem email subscription khi test local

Trong môi trường local hiện tại, mail đang đi qua MailCatcher (ví dụ `MAILER_DSN=smtp://mailcatcher:1025`), nên email **không vào inbox thật** ngay.

Cách xem email:

1. Mở giao diện MailCatcher trên máy local (thường là):
   - `http://localhost:1080`
   - hoặc cổng đã map trong Docker Compose của bạn.
2. Chạy batch/cron subscription.
3. Refresh MailCatcher để thấy các email:
   - gia hạn thành công (`renewal_success`)
   - gia hạn thất bại (`renewal_failed`)
   - tạm dừng sau khi vượt retry (`renewal_final_failed`)

Kiểm tra nhanh cấu hình mail trong container:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.pgsql.yml \
  exec -T ec-cube printenv MAILER_DSN
```

Nếu muốn gửi email ra hộp thư thật, cần đổi `MAILER_DSN` sang SMTP thực tế (SES/SendGrid/Gmail SMTP...), sau đó recreate service `ec-cube`.

---

## 7) Thống kê tiến độ / thời gian (mock vs GMO thật)

Dùng cùng bộ SQL dưới đây trước và sau khi đổi `GMO_MOCK_MODE`; so sánh cột **`first_created` → `last_activity`** và phân phối `billing_status`.

**Renewal (`dtb_subscription_order`) theo trạng thái:**

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.pgsql.yml exec -T postgres psql -U dbuser -d eccubedb -c "
SELECT so.billing_status AS status,
       COUNT(*) AS cnt,
       MIN(so.create_date) AS first_created,
       MAX(COALESCE(so.billing_executed_at, so.update_date)) AS last_activity
FROM dtb_subscription_order so
JOIN dtb_subscription s ON s.id = so.subscription_id
WHERE s.gmo_member_id LIKE 'loadtest_member_%'
GROUP BY so.billing_status
ORDER BY so.billing_status;
"
```

**Còn bao nhiêu subscription loadtest đang “đến hạn”:**

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.pgsql.yml exec -T postgres psql -U dbuser -d eccubedb -c "
SELECT COUNT(*) AS due_active_loadtest
FROM dtb_subscription s
WHERE s.gmo_member_id LIKE 'loadtest_member_%'
  AND s.status = 'active'
  AND s.next_billing_at <= now();
"
```

**Thời gian xử lý từng lần gia hạn (chỉ tham khảo):** các cột Doctrine `timestamp`/`datetimetz` thường **lưu đến giây**, nên chênh lệch `(billing_executed_at - create_date)` có thể **ra 0s** trong mock; đo chính xác hơn nên nhìn `site.log` hoặc thêm instrumentation.

**Throughput lý thuyết với cron `*/5 + limit 5`:** tối đa **60 subscription thành công/giờ** khi luôn đủ đến hạn và không lỗi.
