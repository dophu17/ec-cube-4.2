# RQ-05-60_Danh-sach-batch

ユーザー: Nguyen Kien
作成日時: 8 tháng 5, 2026 9:50
最終更新日時: 11 tháng 5, 2026 (v1.3 — đối chiếu `app/Customize/Command`, `BillingRunner`, `services.yaml`; sửa mô tả sai; thêm cột Trạng thái vs source)

# **Danh sách xử lý batch (バッチ処理一覧)**

> **Dự án**: Website EC thực phẩm chức năng — PRJ-SABURI-001 **Phiên bản**: v1.3
> 

## Đối chiếu source

Repo hiện có **2 Symfony command** trong `app/Customize/Command/`:

| Command | File | Mục đích |
| --- | --- | --- |
| `subscription:run` | `SubscriptionRunCommand.php` | Quét subscription `active` đến hạn → charge GMO (bao gồm retry nếu `next_billing_at` đã được dời bởi lần charge thất bại trước). |
| `subscription:cancel` | `SubscriptionCancelCommand.php` | Hủy **một** subscription theo ID (kiểm tra luật nghiệp vụ `CancellationService`). Chạy thủ công, **không phải cron tự động**. |

**Không** tồn tại trong repo: `subscription:dunning`, `subscription:notify-upcoming`, `subscription:cleanup-logs`, distributed lock, stagger delay 200ms, env `SUBSCRIPTION_CRON_BATCH`.

---

| No | ID batch | Tên batch | Tổng quan xử lý | Cơ chế kích hoạt | Đối tượng xử lý | Khả năng xử lý lại | F-ID | Phân loại phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | BAT-001 | Subscription Billing (`subscription:run`) | Quét `active` + `next_billing_at ≤ NOW()` → lấy tối đa `--limit` (mặc định 50). Với mỗi subscription: tạo `SubscriptionOrder` (`pending`), tạo Order gia hạn (`RenewalOrderFactory`), gọi GMO (`payWithTestCard` trong MVP — chưa card-on-file production), ghi event log. Thành công → reset `retry_count`, tính `next_billing_at` / `next_fulfillment_at` mới. Thất bại → tăng `retry_count`; nếu vượt `max_retry` → set `past_due` + `next_billing_at` +365 ngày (dừng tự động). Email thông báo qua `SubscriptionMailNotifier`. | Cron — **tần suất do hệ thống cấu hình** (ví dụ mỗi 5–15 phút hoặc 1 lần/ngày). **Không** cố định 02:00 JST trong code. | Subscription (E-005), Subscription Item (E-006), Subscription Order (E-007), Order (E-004), Event Log (E-008) | Có — mỗi lần charge tạo `billing_cycle_key` mới (unique constraint); nếu crash giữa chừng, row `pending` tồn tại nhưng subscription vẫn query lại được lần kế. **Không** có distributed lock / TTL trong code MVP. | F-013, F-014 | Đối tượng lần này | `bin/console subscription:run --limit=100 --dry-run`. Feature flag: `SUBSCRIPTION_ENABLED`. Retry offset: `SUBSCRIPTION_RETRY_DAYS` (mặc định 1,3,7). Max retry: `SUBSCRIPTION_MAX_RETRY` (mặc định 3). **Dunning (F-014) tích hợp trong command này** — không tách command riêng. | Đã có |
| 2 | BAT-002 | Subscription Cancel (`subscription:cancel`) | Hủy **một** subscription theo ID. Gọi `CancellationService::cancel` (kiểm tra: phải `active`, chưa fulfillment, chưa pre-bill thành công kỳ hiện tại). Nếu không đủ điều kiện → exception. | **Thủ công** — admin/dev chạy CLI khi cần. Không phải cron định kỳ. | Subscription (E-005) | Có — idempotent (nếu đã `cancelled` thì service báo lỗi). | F-011 (admin) | Đối tượng lần này | `bin/console subscription:cancel {id}`. Không cần `SUBSCRIPTION_ENABLED`. | Đã có |

---

### **Hạng mục đã lược bỏ / điều chỉnh so với bản v1.2**

| Nội dung cũ (v1.2) | Lý do |
| --- | --- |
| BAT-001: "Cron hàng ngày 02:00 JST + mỗi 15 phút retry" | Giờ chạy do cron hệ thống; code **không** cố định lịch. |
| BAT-001: "Distributed lock (TTL=300s)", "Stagger delay 200ms", "`SUBSCRIPTION_CRON_BATCH`" | **Không** tồn tại trong code MVP. |
| BAT-002: `subscription:dunning` tách riêng, "14 ngày past_due → tự động hủy" | **Không** có command riêng; dunning tích hợp trong `subscription:run`. Sau max retry → `past_due` (dừng), **không** tự cancel. |
| BAT-003: `subscription:notify-upcoming` (email nhắc 3 ngày trước charge) | **Không** tồn tại trong repo. |
| BAT-004: `subscription:cleanup-logs` (xóa event log > 90 ngày) | **Không** tồn tại trong repo. |
