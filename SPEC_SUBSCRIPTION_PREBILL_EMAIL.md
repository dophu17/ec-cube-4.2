# Spec ngắn — Subscription pre-billing (T-1) + Email thông báo

## 1) Mục tiêu

- Thu tiền subscription **trước ngày fulfillment 1 ngày** (T-1).
- Khi xử lý subscription, hệ thống gửi email thông báo cho khách hàng.
- Giảm rủi ro sai lệch thời gian bằng cách chuẩn hóa theo **UTC** trong DB và logic so sánh.

## 2) Quyết định đã chốt

- **Timezone:** Dùng **UTC end-to-end** cho lưu trữ, so sánh `now()`, cron, query due.
- **Retry policy:** Giữ nguyên lịch retry **1/3/7 ngày** sau lần fail.
- **Order status:** Renewal order tạo ở trạng thái **`PROCESSING`**.
- **Hủy trước fulfillment:** Cho phép hủy **chỉ khi chưa thanh toán thành công** cho kỳ sắp fulfillment.
- **Email:** Nội dung **tiếng Việt**, địa chỉ `From` dùng thông tin công ty **Balocco**.

## 3) Phạm vi thay đổi (MVP)

### 3.1 Dữ liệu subscription

Thêm trường mới:

- `next_fulfillment_at` (datetime tz, not null): thời điểm fulfillment kỳ kế tiếp.
- `last_fulfilled_at` (datetime tz, nullable): fulfillment gần nhất đã hoàn tất.

Giữ trường hiện có:

- `next_billing_at`: đổi nghĩa thành **thời điểm charge** (không còn là fulfillment date).

### 3.2 Luồng tính lịch

- Khi kích hoạt subscription:
  - Tính `next_fulfillment_at` theo chu kỳ (`test_minute` / weekly / monthly).
  - Tính `next_billing_at = next_fulfillment_at - 1 day`.
- Cron chỉ quét due theo:
  - `status = active`
  - `next_billing_at <= CURRENT_TIMESTAMP()`

### 3.3 Luồng renewal

Khi charge thành công:

1. Tạo renewal order (status `PROCESSING`), commit payment.
2. Gửi email `renewal_success`.
3. Cập nhật subscription:
   - `last_billed_at = now_utc`
   - `last_fulfilled_at = next_fulfillment_at` (kỳ vừa chốt)
   - `next_fulfillment_at = compute_next_fulfillment_after(current next_fulfillment_at)`
   - `next_billing_at = next_fulfillment_at - 1 day`
   - `retry_count = 0`

Khi charge fail:

1. Ghi `subscription_order` trạng thái `failed`.
2. Gửi email `renewal_failed` (nêu lý do).
3. Retry theo `+1/+3/+7`.
4. Nếu vượt `max_retry`: set `past_due`, gửi email `renewal_final_failed`.

## 4) Quy tắc hủy trước fulfillment

- Nếu subscription đang `active`, chưa đến `next_fulfillment_at`, **và chưa có payment success cho kỳ hiện tại**, cho phép user/admin hủy.
- Nếu đã thanh toán thành công cho kỳ hiện tại (đã pre-bill thành công), **không cho hủy** bằng chức năng hủy thường.
- Sau khi hủy:
  - `status = cancelled`
  - `cancelled_at = now_utc`
  - không phát sinh renewal mới từ cron.

> Lưu ý vận hành: trường hợp đã charge thành công nhưng khách muốn dừng dịch vụ cần đi luồng hỗ trợ riêng (refund/điều chỉnh thủ công), không thuộc MVP này.

## 5) Email (tiếng Việt)

Template tối thiểu:

- `renewal_success`
- `renewal_failed`
- `renewal_final_failed`

Nội dung tối thiểu:

- Mã subscription
- Mã đơn renewal (nếu có)
- Số tiền
- Ngày fulfillment dự kiến
- Trạng thái thanh toán / lý do lỗi
- Hướng dẫn liên hệ hỗ trợ

Header:

- `From`: thông tin công ty Balocco (ví dụ: `Balocco <support@balocco.jp>`; xác nhận địa chỉ thật trước khi release).

## 6) Ảnh hưởng kỹ thuật

- Migration: thêm cột mới vào `dtb_subscription`.
- Entity + repository: mapping `datetimetz`; giữ query due bằng `CURRENT_TIMESTAMP()`.
- Service:
  - `SubscriptionScheduler`: thêm hàm tính fulfillment và pre-billing T-1.
  - `SubscriptionActivator`: set cả `next_fulfillment_at` + `next_billing_at`.
  - `SubscriptionBillingRunner`: update 2 mốc thời gian + gọi mail notifier.
- Mail:
  - service `SubscriptionMailNotifier`
  - twig templates email tiếng Việt.

## 7) Tiêu chí nghiệm thu

1. Subscription kỳ mới có `next_billing_at = next_fulfillment_at - 1 day`.
2. Cron tại thời điểm T-1 tạo order và charge đúng.
3. Sau success: email success được gửi; sau fail: email fail được gửi.
4. Retry chạy đúng lịch 1/3/7.
5. User chỉ hủy được trước fulfillment khi **chưa thanh toán thành công** kỳ đó; cron không xử lý tiếp subscription đã hủy.
6. Không phát sinh lỗi lệch timezone khi so sánh due với `now()`.

## 8) Ngoài phạm vi (phase sau)

- Auto-refund khi user hủy sau khi đã pre-bill.
- Nâng cấp queue/lock để chạy song song quy mô lớn.
- Dashboard SLA email và payment.
