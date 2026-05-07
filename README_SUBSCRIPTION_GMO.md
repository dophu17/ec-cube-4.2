# Tài liệu đặc tả Subscription cho EC-CUBE 4.2 (GMO Payment)

## 1) Mục tiêu kinh doanh

- Xây dựng mô hình doanh thu định kỳ (subscription) làm kênh tăng trưởng chính.
- Cho phép khách mua lẻ hoặc mua định kỳ ngay tại giỏ hàng/checkout.
- Hỗ trợ chu kỳ linh hoạt (hàng tuần, hàng tháng, mỗi 3 tháng, ...).
- Tự động tạo đơn gia hạn và thu tiền qua GMO Payment.

---

## 2) Phạm vi

### 2.1 MVP (bắt buộc)

- Đăng ký subscription từ checkout.
- Chỉ tạo subscription sau khi thanh toán kỳ đầu thành công.
- Chạy cron định kỳ để xử lý các kỳ đến hạn.
- Retry khi charge lỗi (mặc định: ngày +1/+3/+7).
- MyPage: danh sách/chi tiết/tạm dừng/tiếp tục/hủy.
- Admin: danh sách/lọc/chi tiết/retry/hủy/force-bill.

### 2.2 Phase 2

- Skip 1 kỳ.
- Đổi chu kỳ.
- Cập nhật địa chỉ giao hàng.
- Dashboard doanh thu (MRR/churn/tỷ lệ charge lỗi).

---

## 3) Quy tắc nghiệp vụ

- Loại mua: `one_time` hoặc `subscription`.
- Sản phẩm/combo chỉ được mua định kỳ khi `subscription_enabled = true`.
- Mô hình chu kỳ:
  - `weekly` + `interval_count`
  - `monthly` + `interval_count` (ví dụ: `3` = mỗi 3 tháng)
- Trạng thái subscription:
  - `pending_activation`
  - `active`
  - `paused`
  - `past_due`
  - `cancelled`
  - `expired`
- Mốc billing:
  - Kỳ đầu: charge ngay.
  - Các kỳ sau: theo `next_billing_at`.

### 3.1 Các quyết định chính sách (cần chốt trước khi code)

- Sau `max_retry`: chuyển sang `past_due` (mặc định MVP), chưa tự hủy ngay.
- Nếu khách cập nhật thẻ hợp lệ và charge lại thành công: cho phép `past_due -> active`.
- Chính sách giá: khóa theo snapshot trong MVP (chưa tự động re-price đến khi có quyết định ở Phase 2).

---

## 4) Kiến trúc kỹ thuật (EC-CUBE 4.2)

- Ưu tiên code trong `app/Customize`.
- Hạn chế sửa core trong `src/Eccube/*` trừ khi bắt buộc.
- Thành phần chính:
  - `SubscriptionService`
  - `SubscriptionBillingService`
  - `SubscriptionSchedulerService`
  - `DunningService`
  - `GmoPaymentService` (gateway duy nhất)

---

## 5) Data model đề xuất

### 5.1 `dtb_subscription`

- `id`, `customer_id`, `base_order_id`, `status`
- `plan_type`, `interval_count`
- `next_billing_at`, `last_billed_at`, `cancelled_at`
- `retry_count`, `max_retry`
- Snapshot tiền:
  - `subtotal_amount`, `discount_amount`, `shipping_fee`, `tax_amount`, `total_amount`
- Snapshot địa chỉ giao hàng
- Trường GMO:
  - `payment_gateway` (`gmo`)
  - `gmo_member_id`
  - `gmo_card_seq`
  - `gmo_last_order_id`
- `create_date`, `update_date`

### 5.2 Bảng `dtb_subscription_item`

- `id`, `subscription_id`, `product_id`, `product_class_id`
- Snapshot sản phẩm (tên, biến thể, đơn giá, thuế, số lượng)
- `is_combo`, `combo_code`
- `create_date`, `update_date`

### 5.3 Bảng `dtb_subscription_order`

- `id`, `subscription_id`, `order_id`
- `billing_cycle_key` (idempotency key, ví dụ: `subscriptionId + cycleDate`)
- `billing_scheduled_at`, `billing_executed_at`, `billing_status`
- Trường giao dịch GMO:
  - `gmo_order_id`
  - `gmo_access_id`
  - `gmo_access_pass` (không khuyến nghị lưu; nếu bắt buộc thì mã hóa at-rest)
  - `gmo_tran_id`
  - `gmo_approve`
  - `gmo_status`
- `error_code`, `error_message`
- `create_date`, `update_date`

### 5.4 Bảng `dtb_subscription_event_log`

- `id`, `subscription_id`, `event_type`, `payload` (json), `create_date`

### 5.5 Index bắt buộc

- `dtb_subscription(status, next_billing_at)`
- `dtb_subscription(customer_id, status)`
- `dtb_subscription_order(subscription_id, billing_status)`
- `dtb_subscription_order(billing_cycle_key)` unique

---

## 6) Luồng nghiệp vụ chính

### 6.1 Tạo subscription từ checkout

1. Người dùng chọn `one_time` hoặc `subscription` và chu kỳ.
2. Hệ thống thực hiện thanh toán kỳ đầu qua GMO.
3. Nếu thành công:
   - tạo subscription
   - lưu snapshot item/địa chỉ/số tiền
   - set `next_billing_at`
4. Gửi email thông báo đăng ký thành công.

### 6.2 Cron billing định kỳ

1. `subscription:run` chạy mỗi 5-15 phút.
2. Lấy các subscription `active` có `next_billing_at <= now()`.
3. Lock theo từng subscription để tránh charge trùng.
4. Tạo order mới từ snapshot.
5. Charge GMO bằng member/card đã lưu.
6. Nếu thành công:
   - cập nhật `last_billed_at`
   - tính lại `next_billing_at`
   - reset `retry_count`
7. Nếu thất bại:
   - phân loại lỗi (soft/hard decline)
   - tăng `retry_count` theo policy
   - lên lịch retry
   - chuyển `past_due` khi vượt ngưỡng retry

### 6.3 Tạm dừng / Tiếp tục / Hủy

- Pause: dừng tạo kỳ mới.
- Resume: tính lại kỳ charge tiếp theo theo policy.
- Cancel: dừng vĩnh viễn, set `cancelled_at`.

---

## 7) Tích hợp GMO Payment (bắt buộc)

### 7.1 Nguyên tắc

- Chỉ dùng GMO Payment.
- Không lưu raw PAN/CVV.
- Dùng tokenization và member/card-on-file.
- `SiteID/SitePass` cho member/card API.
- `ShopID/ShopPass` cho transaction API.

### 7.2 Chính sách 3DS/SCA và recurring

- Giao dịch đầu nên tuân thủ 3DS/SCA (CIT).
- Các kỳ recurring nên chạy dạng MIT với metadata credential-on-file theo chuẩn GMO hỗ trợ.
- Nếu issuer yêu cầu step-up auth đột xuất: chuyển `past_due` và yêu cầu khách xác thực/cập nhật thẻ.

### 7.3 Service GMO bắt buộc

Tạo `Customize\Service\Payment\GmoPaymentService` với các hàm tối thiểu:

- `registerMember(Customer $customer)`
- `saveCardToken(Customer $customer, string $token)`
- `entryTran(string $orderId, int $amount, string $jobCd)`
- `execTranWithSavedCard(...)`
- `searchTrade(string $orderId)`
- `alterTran(...)` (phase 2)

### 7.4 Biến môi trường

- `GMO_API_BASE_URL`
- `GMO_SITE_ID`
- `GMO_SITE_PASS`
- `GMO_SHOP_ID`
- `GMO_SHOP_PASS`
- `GMO_JOB_CD` (recommended `CAPTURE`)
- `GMO_MOCK_MODE` (chỉ local)
- `GMO_3DS_ENABLED` (phase 2 hoặc flow kỳ đầu)

---

## 8) Route/Endpoint

### Front (MyPage)

- `GET /mypage/subscriptions`
- `GET /mypage/subscriptions/{id}`
- `POST /mypage/subscriptions/{id}/pause`
- `POST /mypage/subscriptions/{id}/resume`
- `POST /mypage/subscriptions/{id}/cancel`

### Admin

- `GET /admin/subscription`
- `GET /admin/subscription/{id}`
- `POST /admin/subscription/{id}/retry-now`
- `POST /admin/subscription/{id}/force-bill`
- `POST /admin/subscription/{id}/cancel`

### Command

- `bin/console subscription:run --limit=100 --dry-run=0`

---

## 9) UI/UX

### Cart/Checkout

- Toggle "Mua định kỳ".
- Dropdown chu kỳ: 1 tuần, 2 tuần, 1 tháng, 3 tháng, ...
- Hiển thị số tiền mỗi kỳ và ngày charge dự kiến.

### MyPage

- Danh sách subscription, trạng thái, ngày charge tiếp theo.
- Hành động: pause/resume/cancel.

### Admin

- Lọc theo trạng thái, next billing date, retry_count.
- Hành động: retry now / force bill / cancel.

---

## 10) Bảo mật và vận hành

- Dùng idempotency key cho billing và callback/webhook.
- Dùng distributed lock cho `subscription:run`.
- Mask token/secret trong log.
- Bật CSRF protection cho thao tác MyPage/Admin.
- Lưu audit log cho thao tác quản trị.
- Không hiển thị mặc định các trường GMO nhạy cảm trên Admin UI.

### 10.1 Chiến lược đối soát (reconciliation)

- Webhook/callback chỉ được chấp nhận khi pass kiểm tra chữ ký/nguồn gửi.
- Có job đối soát hằng ngày:
  - so sánh trạng thái billing nội bộ với `searchTrade(orderId)` trên GMO
  - tự sửa các trạng thái lệch có thể xử lý tự động
  - tạo cảnh báo vận hành nếu còn mismatch chưa xử lý được

---

## 11) Xử lý lỗi thanh toán (Dunning)

Chính sách retry mặc định:

- Retry ngày +1, +3, +7 với lỗi tạm thời/soft decline.
- Hard decline (thẻ hết hạn, invalid, nghi ngờ gian lận): không retry mù; yêu cầu khách thao tác.
- Sau max retry: chuyển `past_due`, gửi chuỗi thông báo (email/in-app).

---

## 12) Kế hoạch kiểm thử

### Unit test

- Tính `next_billing_at` cho weekly/monthly.
- Tạo lịch retry (1/3/7).
- Chuyển trạng thái và điều kiện chặn.
- Sinh idempotency key.

### Integration test

- Tạo subscription sau checkout thành công.
- Cron tạo order gia hạn đúng theo snapshot.
- Fail -> retry -> success.
- Pause/cancel không tạo kỳ mới.
- Chạy cron trùng không gây double charge.

---

## 13) Migration và rollout

1. Deploy migration + code, tắt cron qua feature flag.
2. Test luồng trên staging bằng GMO sandbox.
3. Bật cron production theo batch nhỏ.
4. Theo dõi failed charge rate, recovery rate, churn, duplicate charge.

### 13.1 Kế hoạch rollback

- Tắt `SUBSCRIPTION_ENABLED` và trigger scheduler.
- Giữ nguyên các order/subscription hiện có, không sửa dữ liệu nóng.
- Chỉ mở lại sau khi hotfix được xác nhận.

---

## 14) Feature flag

- `SUBSCRIPTION_ENABLED=true`
- `SUBSCRIPTION_CRON_BATCH=100`
- `SUBSCRIPTION_RETRY_DAYS=1,3,7`
- `SUBSCRIPTION_MAX_RETRY=3`
- `SUBSCRIPTION_LOCK_TTL=300`

---

## 15) Gợi ý SLO/KPI

- Tỷ lệ charge trùng: `0`.
- Tỷ lệ phục hồi sau failed payment: mục tiêu >= 30% (tùy business).
- Ngưỡng cảnh báo failed renewal rate: cấu hình được (ví dụ 5%).
- SLO độ trễ cron: subscription đến hạn được xử lý trong 30 phút.

---

## 16) Prompt AI coding (copy/paste)

```text
Bạn đang làm trong dự án EC-CUBE 4.2. Hãy triển khai Subscription với cổng thanh toán duy nhất là GMO Payment.

Yêu cầu:
1) Tạo code trong app/Customize, hạn chế sửa core.
2) Tạo các bảng dtb_subscription, dtb_subscription_item, dtb_subscription_order, dtb_subscription_event_log; bổ sung các cột GMO: gmo_member_id, gmo_card_seq, gmo_order_id, gmo_access_id, gmo_tran_id, gmo_approve, gmo_status.
3) Checkout flow: user chọn subscription + chu kỳ, xử lý kỳ đầu qua GMO (EntryTran + ExecTran), thành công mới tạo subscription active.
4) Tạo command subscription:run: quét đến hạn, lock từng subscription, tạo order mới, charge GMO bằng member/card đã lưu, success thì cập nhật next_billing_at, fail thì retry 1/3/7 ngày.
5) Tạo MyPage và Admin cho quản lý subscription (list/detail/pause/resume/cancel/retry-now).
6) Tạo GmoPaymentService với registerMember, saveCardToken, entryTran, execTranWithSavedCard, searchTrade.
7) Không lưu raw card/CVV; bắt buộc mask secret trong log; idempotency cho billing/callback.
8) Bàn giao: migration + code đầy đủ + test + tài liệu run local GMO sandbox.
```

---

## 17) Triển khai MVP trong repo này (test chu kỳ 10 phút)

Đã có code sẵn trong `app/Customize`:

- Migration: `app/DoctrineMigrations/Version20260507103000.php`
- Command: `bin/console subscription:run` (tùy chọn `--limit`, `--dry-run`)
- Checkout: checkbox/chọn chu kỳ trên màn hình xác nhận (chỉ **thành viên đăng nhập**), lưu ý `[TEST] Mỗi 10 phút` chỉ hiện khi `SUBSCRIPTION_ALLOW_TEST_INTERVAL=1`

### Biến môi trường `.env.local` / server

Tham khảo thêm các biến GMO hiện có (`GMO_*`). Subscription:

```dotenv
SUBSCRIPTION_ENABLED=1
SUBSCRIPTION_ALLOW_TEST_INTERVAL=1
SUBSCRIPTION_MAX_RETRY=3
SUBSCRIPTION_RETRY_DAYS=1,3,7
```

**Lưu ý MVP kỹ thuật:** các lần gia hạn (`subscription:run`) hiện gọi lại **`GmoApiClient::payWithTestCard`**, tức cùng cơ chế với chỉ định GMO mock / test card trong env (`GMO_MOCK_MODE`, `GMO_TEST_CARD_*`). Chưa phải card-on-file / MIT production — phù hợp localhost / sandbox chứ chưa đủ cho production như trong mục 7 tài liệu.

### Các bước test thủ công

1. Chạy migration: `bin/console doctrine:migrations:migrate` (xác nhận version `Version20260507103000`).
2. `bin/console cache:clear`
3. Đăng nhập MyPage, giỏ hàng → đặt hàng → ở bước **confirm** tick **Mua định kỳ**, chọn **\[TEST\] Mỗi 10 phút**, nhập GMO test card nếu dùng `GmoDirectCreditCard`.
4. Thanh toán thành công → vào DB kiểm tra `dtb_subscription` (trạng thái `active`, `next_billing_at` ≈ hiện tại +10 phút).
5. Sau ~10 phút (hoặc chỉnh tạm `next_billing_at` trong DB), chạy: `bin/console subscription:run --limit=5`
6. Kiểm tra đơn mới trong `dtb_order` và log `dtb_subscription_order` (`billing_status=success`).
7. Lên cron mỗi 1–5 phút trong môi trường thật, ví dụ: `php bin/console subscription:run`
