# RQ-05-100_Su-kien-nghiep-vu-va-Ma-tran-chuyen-trang-thai

ユーザー: Nguyen Kien
作成日時: 8 tháng 5, 2026 9:52
最終更新日時: 11 tháng 5, 2026 (v1.3 — đối chiếu toàn bộ `app/Customize/`; sửa sự kiện / ma trận sai; thêm cột Trạng thái vs source)

# **Bảng định nghĩa sự kiện nghiệp vụ · Ma trận chuyển trạng thái (業務イベント定義表・状態遷移マトリクス)**

> **Dự án**: Website EC thực phẩm chức năng hỗ trợ sức khỏe lãnh đạo (EC-CUBE 4) **Phiên bản**: v1.3 **Ngày tạo**: 28 tháng 4, 2026
> 

## Đối chiếu source

Chuyển trạng thái Subscription thực tế trong code:

| Transition | Thực hiện bởi | File |
| --- | --- | --- |
| `pending_activation` → `active` | `SubscriptionActivator::activate()` | `SubscriptionActivator.php` |
| `active` → `past_due` | `BillingRunner::execute()` khi `retryCount > maxRetry` | `SubscriptionBillingRunner.php` |
| `past_due` → `active` | `AdminService::billNow()` (admin force → reset status trước khi charge) | `SubscriptionAdminService.php` |
| `active` → `cancelled` | `CancellationService::cancel()` (điều kiện: active, chưa fulfillment, chưa pre-bill thành công) | `SubscriptionCancellationService.php` |
| `{pending_activation, paused, past_due}` → `cancelled` | `AdminService::forceCancel()` (admin override) | `SubscriptionAdminService.php` |

**Không** tồn tại trong code: `active` → `paused`, `paused` → `active` (MyPage chưa có), skip, chatbot, SNS login, PDF, language toggle.

---

## **Sản phẩm 1: Bảng định nghĩa sự kiện nghiệp vụ**

| No | ID sự kiện | Tên sự kiện | Phân loại | Điều kiện phát sinh | F-ID | Đích xuất | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | EVT-001 | Tài khoản khách hàng đã được đăng ký | Thao tác do người | Đăng ký tài khoản hoàn tất, khách Active | F-016 | SCR-007, NTF-001 | EC-CUBE core `sendCustomerConfirmMail()`. **SNS (Google/LINE) chưa triển khai.** | EC-CUBE core (SNS chưa có) |
| 2 | EVT-002 | Khách hàng đã đăng nhập thành công | Thao tác do người | Xác thực hoàn tất, session tạo | F-017 | SCR-008 | EC-CUBE core auth. **Google + LINE chưa triển khai.** | EC-CUBE core (SNS chưa có) |
| 3 | EVT-003 | Sản phẩm đã được thêm vào giỏ hàng | Thao tác do người | Sản phẩm (thường/định kỳ) thêm vào giỏ | F-004 | SCR-004 | EC-CUBE core. | EC-CUBE core |
| 4 | EVT-004 | Đơn hàng mua lẻ đã được chốt | Thao tác do người | Thanh toán GMO thành công, Order tạo mới | F-005 | SCR-006, NTF-002 | EC-CUBE core checkout + Customize `GmoApiClient`. **Order status**: EC-CUBE dùng integer (`OrderStatus::NEW=1` → `PAID=6`), không phải string `new`/`paid`. **FRM-001 (PDF biên lai) chưa tồn tại.** | Đã có (thiếu FRM-001) |
| 5 | EVT-005 | Subscription đã được tạo (pending_activation) | Thao tác do người | Checkout định kỳ thành công. `ShoppingCompleteSubscriber` tạo Subscription (`pending_activation`) + Order kỳ đầu. | F-005, F-007 | SCR-006 | `ShoppingCompleteSubscriber.php`. **NTF-003 (email xác nhận đăng ký định kỳ) chưa triển khai** — dùng `sendOrderMail()` core chung. | Đã có (thiếu NTF-003) |
| 6 | EVT-005a | Subscription đã được kích hoạt lần đầu | Thay đổi trạng thái | `SubscriptionActivator::activate()`: `pending_activation` → `active`. | F-005, F-007 | — | Xảy ra trong cùng checkout flow (sau EVT-005). | Đã có |
| 7 | EVT-006 | Thời điểm thanh toán định kỳ đã đến | Thời gian | `next_billing_at ≤ NOW()` cho subscription `active` | F-013 | BAT-001 | `subscription:run` command. **Không** cố định 02:00 JST — tần suất do cron hệ thống. | Đã có |
| 8 | EVT-007 | Thanh toán định kỳ tự động đã thành công | Thay đổi trạng thái | `BillingRunner`: GMO OK → `SubscriptionOrder.billing_status = success`, reset `retry_count`, tính `next_billing_at` / `next_fulfillment_at` mới | F-013 | NTF-005 | `notifyRenewalSuccess()`. **FRM-001 chưa tồn tại.** | Đã có (thiếu FRM-001) |
| 9 | EVT-008 | Thanh toán định kỳ tự động đã thất bại | Thay đổi trạng thái | GMO lỗi → `retry_count += 1`. **Nếu chưa vượt max_retry**: vẫn `active`, dời `next_billing_at` theo retry offset. **Nếu vượt**: → `past_due`. | F-014 | NTF-006 / NTF-008 | **Sửa v1.2**: charge thất bại lần đầu **không** chuyển ngay sang `past_due` — chỉ khi vượt `max_retry`. | Đã có (sửa mô tả) |
| 10 | EVT-009 | Admin force-bill trên past_due đã thành công | Thay đổi trạng thái | `AdminService::billNow()`: `past_due` → `active` (reset status + retry_count), rồi charge. | F-013 | NTF-005 | **Sửa v1.2**: không có dunning retry tự động `past_due` → `active`. Chỉ admin force-bill. | Đã có (sửa mô tả) |
| 11 | EVT-010 | Subscription chuyển past_due (hết retry) | Thay đổi trạng thái | `retry_count > max_retry` → set `past_due` + `next_billing_at +365 ngày` (dừng). | F-014 | NTF-008 | **Sửa v1.2**: code set `past_due`, **không** tự `cancelled`. | Đã có (sửa: past_due, không cancelled) |
| 12 | EVT-011 | Kỳ giao hàng đã được skip | Thao tác do người | Khách nhấn Skip trên MyPage | F-009 | SCR-008, NTF-009 | **Chưa triển khai.** Không có Skip trong code. Không có entity `SubscriptionSkip`. | Chưa (Phase 2) |
| 13 | EVT-012 | Subscription đã được tạm dừng (pause) | Thao tác do người | Khách nhấn Pause trên MyPage: `active` → `paused` | F-010 | SCR-008 | **Chưa triển khai.** MyPage controller chưa có. Entity có constant `STATUS_PAUSED` nhưng không có code thực hiện transition này. | Chưa (Phase 2) |
| 14 | EVT-013 | Subscription đã được kích hoạt lại (resume) | Thao tác do người | Khách nhấn Resume: `paused` → `active` | F-010 | SCR-008 | **Chưa triển khai.** Tương tự EVT-012. | Chưa (Phase 2) |
| 15 | EVT-014 | Subscription đã bị hủy | Thao tác do người / Admin | (1) `CancellationService::cancel()` — chỉ từ `active`, điều kiện chưa fulfillment + chưa pre-bill. (2) `AdminService::forceCancel()` — từ bất kỳ trạng thái chưa kết thúc. | F-011 | — | **NTF-010 (email xác nhận hủy) chưa triển khai.** Cancel không gửi email. **Tokushouhou UI chưa có** (MyPage chưa có). | Một phần (hủy có, email/UI chưa) |
| 16 | EVT-015 | Chu kỳ giao hàng đã được thay đổi | Thao tác do người | Khách thay đổi cycle trên MyPage | F-012 | SCR-008 | **Chưa triển khai.** MyPage chưa có. | Chưa (Phase 2) |
| 17 | EVT-016 | Chatbot đã nhận câu hỏi | Thao tác do người | Khách gửi tin nhắn Chatbot | F-015 | SCR-013 | **Chưa triển khai.** Không có Chatbot. | Chưa (Phase 2) |
| 18 | EVT-017 | Chatbot đã trả lời tư vấn | Thay đổi trạng thái | Gemini API trả kết quả | F-015 | SCR-013 | **Chưa triển khai.** | Chưa (Phase 2) |
| 19 | EVT-018 | Ngôn ngữ hiển thị đã được chuyển | Thao tác do người | Khách chuyển JP↔EN | F-020 | SCR-014 | **Chưa triển khai.** Không có language toggle component. EC-CUBE core hỗ trợ locale nhưng không có UI toggle riêng. | Chưa (Phase 2) |
| 20 | EVT-019 | Quản trị viên đã cập nhật sản phẩm | Thao tác do người | Admin lưu sản phẩm | F-003 | SCR-A02 | EC-CUBE core admin product. **Không** có Custom Field riêng trong Customize. | EC-CUBE core |
| 21 | EVT-020 | PDF biên lai đã được sinh | Thay đổi trạng thái | Đơn `paid` → sinh PDF Invoice Seido | F-019 | FRM-001 | **Chưa triển khai.** Không có PDF generation code. | Chưa (Phase 2) |
| 22 | EVT-021 | Thẻ tín dụng đã được cập nhật | Thao tác do người | Khách cập nhật thẻ qua GMO token | F-006 | SCR-008 | **Chưa triển khai.** SaveMember/SaveCard/MyPage update-card chưa có. | Chưa (Phase 2) |
| 23 | EVT-022 | Nhắc trước giao hàng đã được gửi | Thời gian | Batch gửi email nhắc 3 ngày trước charge | BAT-003 | NTF-004 | **Chưa triển khai.** BAT-003 không tồn tại. | Chưa (Phase 2) |
| 24 | EVT-023 | Admin đã cập nhật trạng thái shipped | Thao tác do người | Admin nhập tracking, Order → shipped | F-021 | SCR-A03 | EC-CUBE core `sendShippingNotifyMail()`. | EC-CUBE core |
| 25 | EVT-024 | Đơn hàng đã được giao thành công | Thay đổi trạng thái | Order → delivered | F-021 | SCR-A03 | EC-CUBE core order status management. | EC-CUBE core |

### **Hạng mục đã lược bỏ (省略項目)**

Không lược bỏ sự kiện nào — tất cả giữ nguyên với cột Trạng thái vs source phản ánh thực tế.

---

## **Sản phẩm 2: Ma trận chuyển trạng thái**

### **Entity: Subscription (Đăng ký định kỳ) — Thống nhất RQ-05-40**

| Before \ After | pending_activation | active | paused | past_due | cancelled | expired |
| --- | --- | --- | --- | --- | --- | --- |
| **pending_activation** | — | EVT-005a ✅ | × | × | EVT-014 (admin force) ✅ | × |
| **active** | × | — | EVT-012 ⛔ Phase 2 | EVT-010 ✅ | EVT-014 ✅ | × |
| **paused** | × | EVT-013 ⛔ Phase 2 | — | × | EVT-014 (admin force) ✅ | × |
| **past_due** | × | EVT-009 ✅ (admin force-bill) | × | — | EVT-014 (admin force) ✅ | × |
| **cancelled** | × | × | × | × | — | × |
| **expired** | × | × | × | × | × | — |

> **Ghi chú v1.3:**
> - ✅ = có trong code. ⛔ = entity có constant nhưng code transition chưa triển khai.
> - `active` → `past_due`: chỉ khi `retryCount > maxRetry` (EVT-010), **không** phải ngay lần thất bại đầu (sửa v1.2 EVT-008).
> - `past_due` → `active`: chỉ qua **admin force-bill** (`AdminService::billNow`), không có dunning tự động.
> - `active`/`paused` ↔ pause/resume: `STATUS_PAUSED` tồn tại trên entity nhưng **không có code MyPage thực hiện transition**.
> - `expired`: constant tồn tại, **không** có code transition nào dẫn tới `expired`.
> - v1.2 ghi `past_due → cancelled` qua EVT-010 — **sai**. Code set `past_due`, không tự cancel. Admin có thể force cancel từ `past_due`.

---

### **Entity: Order (Đơn hàng) — EC-CUBE core integer status**

EC-CUBE dùng `mtb_order_status` integer, **không phải** string. Ma trận thực tế:

| Before \ After | NEW (1) | PROCESSING (4) | PAID (6) | DELIVERED (5) | CANCEL (3) |
| --- | --- | --- | --- | --- | --- |
| **NEW (1)** | — | EVT-004/005a/007 (checkout/billing → processing) | × | × | User/Admin hủy |
| **PROCESSING (4)** | × | — | Xác nhận thanh toán | × | Admin hủy |
| **PAID (6)** | × | × | — | EVT-023 (shipping confirm) | Admin hủy |
| **DELIVERED (5)** | × | × | × | — | × |
| **CANCEL (3)** | × | × | × | × | — |

> **Ghi chú v1.3:**
> - v1.2 dùng string state (`new`, `paid`, `shipped`, `delivered`). Thực tế EC-CUBE dùng **integer** (`OrderStatus::NEW=1`, `PROCESSING=4`, `PAID=6`, `DELIVERED=5`, `CANCEL=3`).
> - `RenewalOrderFactory` tạo order ở `PROCESSING (4)`, sau đó `PurchaseFlow::commit` chuyển sang trạng thái tiếp.
> - **Không** có `shipped` riêng — EC-CUBE core dùng Shipping entity để quản lý trạng thái giao hàng, không phải OrderStatus.
> - `refunded` — **không** tồn tại trong core.

---

### **Entity: Subscription Order / Billing — Thống nhất RQ-05-40**

| Before \ After | pending | success | failed |
| --- | --- | --- | --- |
| **pending** | — | EVT-007 (charge OK) | EVT-008 (charge fail) |
| **success** | × | — | × |
| **failed** | × | × | — |

> **Ghi chú v1.3:**
> - v1.2 dùng 4 trạng thái: `scheduled`, `success`, `failed`, `retry`. Code thực tế chỉ có **3**: `pending`, `success`, `failed` (`SubscriptionOrder::BILLING_STATUS_*`).
> - **Không** có state `scheduled` hay `retry`. Mỗi lần retry tạo **row mới** (`pending` → `success`/`failed`).
> - `pending` là trạng thái khởi tạo; `success` và `failed` là trạng thái kết thúc cho row đó.

---

### **Entity: SubscriptionSkip (Bỏ qua kỳ giao)**

| Trạng thái vs source |
| --- |
| **Chưa triển khai.** Entity `SubscriptionSkip` **không tồn tại** trong repo. Chức năng Skip thuộc Phase 2. |

---

### **Hạng mục đã lược bỏ / điều chỉnh so với bản v1.2**

| Nội dung cũ (v1.2) | Lý do |
| --- | --- |
| EVT-008: "active → past_due ngay khi thất bại" | **Sai.** Thất bại lần đầu: vẫn `active` + dời `next_billing_at`. Chỉ vượt `max_retry` → `past_due`. |
| EVT-009: "Dunning retry thành công: past_due → active" (tự động) | **Sai.** Không có dunning tự động từ `past_due`. Chỉ admin force-bill. |
| EVT-010: "past_due → cancelled" | **Sai.** Code set `past_due`, **không** tự `cancelled`. |
| EVT-011~013: Skip, Pause, Resume | **Chưa triển khai.** MyPage chưa có. |
| EVT-014: "Tokushouhou form hủy trên MyPage" | **Một phần.** Cancel logic có (CLI + Admin), nhưng MyPage UI + email chưa có. |
| EVT-016/017: Chatbot | **Chưa triển khai.** |
| EVT-018: Language toggle | **Chưa triển khai.** |
| EVT-020: PDF biên lai | **Chưa triển khai.** |
| EVT-021: Cập nhật thẻ | **Chưa triển khai.** |
| EVT-022: Nhắc trước giao hàng (BAT-003) | **Chưa triển khai.** |
| Ma trận Order: string states (new, paid, shipped, delivered, cancelled) | EC-CUBE dùng **integer** (`OrderStatus::NEW=1`, etc.). Không có `shipped` trên Order — dùng Shipping entity. |
| Ma trận Billing: 4 states (scheduled, success, failed, retry) | Chỉ 3 states: `pending`, `success`, `failed`. Retry tạo row mới. |
| Entity SubscriptionSkip | **Không tồn tại.** |
