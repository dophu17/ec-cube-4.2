# RQ-05-40_Bang-dinh-nghia-trang-thai

ユーザー: Nguyen Kien
作成日時: 8 tháng 5, 2026 9:50
最終更新日時: 11 tháng 5, 2026 (v1.3 — đối chiếu entity const + `BillingRunner` + `Activator` + EC-CUBE `OrderStatus`; sửa nhiều điểm sai)

# **Bảng định nghĩa trạng thái (状態定義表)**

> **Dự án**: Website EC thực phẩm chức năng — PRJ-SABURI-001 **Phiên bản**: v1.3
> 

## **Subscription States**

Const: `Customize\Entity\Subscription::STATUS_*`

| No | ID trạng thái | Thực thể | Giá trị DB | Định nghĩa | Ban đầu? | Kết thúc? | Nguồn chuyển đổi chính | Đích chuyển đổi chính | Căn cứ (source) | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | SS-001 | Subscription (E-005) | `pending_activation` | Chờ kích hoạt. Const tồn tại trong entity; `AdminService` reject charge nếu ở trạng thái này. | (thiết kế) | — | — | active, cancelled | Entity const | **Trong flow hiện tại không đi qua:** `SubscriptionActivator` tạo trực tiếp `active` sau khi `Order.paymentDate` đã có. Dự phòng cho flow kỳ đầu chưa thanh toán. | Const có / flow chưa dùng |
| 2 | SS-002 | Subscription (E-005) | `active` | Đang hoạt động. Cron (`subscription:run` → `BillingRunner`) quét `active` + `next_billing_at ≤ now`. | ✅ Có (thực tế — `Activator` set luôn `active`) | — | (tạo mới), paused, past_due (admin) | paused, past_due, cancelled | `SubscriptionActivator`, `BillingRunner`, `AdminService` | Trạng thái chính. Retry **diễn ra khi còn active** (tăng `retry_count`); chỉ vượt `max_retry` mới chuyển `past_due`. | Đã có |
| 3 | SS-003 | Subscription (E-005) | `paused` | Tạm dừng do user / admin. Cron bỏ qua (query chỉ `active`). | — | — | active | active (resume), cancelled | Entity const, `AdminService` | **Chưa có** controller / route MyPage cho pause/resume. Admin reject charge khi `paused` — khách cần resume trước. | Const có / MyPage chưa có |
| 4 | SS-004 | Subscription (E-005) | `past_due` | Thanh toán thất bại **vượt `max_retry`**. `BillingRunner` set `next_billing_at` = +365 ngày (dừng tự động; vận hành admin tay). | — | — | active (khi `retry_count > max_retry`) | active (admin `billNow`), cancelled | `BillingRunner` L168–170, `AdminService` | RQ cũ ghi "14 ngày → cancelled": **sai** — code **không** tự cancel, chỉ chuyển `past_due` rồi dừng. Admin có thể kích hoạt lại qua `billNow` (reset `active`, retry_count = 0). Retry offset: `SUBSCRIPTION_RETRY_DAYS` (mặc định 1,3,7) — diễn ra **trước** khi sang `past_due`. | Đã có (khác RQ cũ) |
| 5 | SS-005 | Subscription (E-005) | `cancelled` | Đã hủy vĩnh viễn. `cancelled_at` được ghi. **Không** có cột `cancel_reason` trong entity. | — | ✅ Có | active (CancellationService / admin), paused (admin force), past_due (admin force), pending_activation (admin force) | — (kết thúc) | `CancellationService`, `AdminService::cancelSubscription` / `forceCancel` | RQ cũ ghi "lý do hủy": entity **không** lưu; có thể ghi vào `SubscriptionEventLog.payload`. | Đã có (thiếu reason) |
| 6 | SS-006 | Subscription (E-005) | `expired` | Hết hạn. Const tồn tại; `AdminService` coi là trạng thái kết thúc (reject charge). | — | ✅ Có | active (thiết kế) | — (kết thúc) | Entity const | **Chưa** có flow nào set `expired` trong code. Dự phòng cho gói có thời hạn cố định (Phase 2). | Const có / flow chưa dùng |

## **Order States (EC-CUBE 4.2 — `mtb_order_status`)**

EC-CUBE dùng **master table `mtb_order_status`** với **integer ID**, không phải chuỗi. Entity: `Eccube\Entity\Master\OrderStatus`. Bản v1.2 ghi sai tên/giá trị — bảng dưới sửa lại theo const trong source.

| No | ID trạng thái | Thực thể | Const / ID | Tên (JP) | Định nghĩa | Ban đầu? | Kết thúc? | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 7 | OS-001 | Order (E-004) | `NEW = 1` | 新規受付 | Đơn mới tạo | ✅ Có | — | — | Đã có |
| 8 | OS-002 | Order (E-004) | `CANCEL = 3` | 注文取消し | Đã hủy đơn | — | ✅ Có | — | Đã có |
| 9 | OS-003 | Order (E-004) | `IN_PROGRESS = 4` | 対応中 | Đang xử lý (admin đang chuẩn bị giao) | — | — | — | Đã có |
| 10 | OS-004 | Order (E-004) | `DELIVERED = 5` | 発送済み | Đã gửi hàng | — | ✅ Có | — | Đã có |
| 11 | OS-005 | Order (E-004) | `PAID = 6` | 入金済み | Đã thanh toán (nhận tiền) | — | — | — | Đã có |
| 12 | OS-006 | Order (E-004) | `PENDING = 7` | 決済処理中 | Đang chờ xử lý thanh toán | — | — | — | Đã có |
| 13 | OS-007 | Order (E-004) | `PROCESSING = 8` | 購入処理中 | Đang xử lý mua hàng (checkout chưa xong) | ✅ Có | — | Tạm thời — xóa nếu checkout bị hủy | Đã có |
| 14 | OS-008 | Order (E-004) | `RETURNED = 9` | 返品 | Đã trả hàng | — | ✅ Có | — | Đã có |

**Lưu ý:** v1.2 liệt kê "shipped / delivered / refunded" — EC-CUBE 4.2 không có trạng thái tên đó. `DELIVERED` (5) nghĩa là "発送済み" (đã gửi), không phải "đã nhận". Không có `refunded` mặc định trong `mtb_order_status`.

## **Billing States (Subscription Order)**

Const: `Customize\Entity\SubscriptionOrder::BILLING_STATUS_*`

| No | ID trạng thái | Thực thể | Giá trị DB | Định nghĩa | Ban đầu? | Kết thúc? | Nguồn chuyển đổi chính | Đích chuyển đổi chính | Căn cứ (source) | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 15 | BS-001 | Subscription Order (E-007) | `pending` | Row vừa tạo, chưa charge GMO. | ✅ Có | — | — | success, failed | `BillingRunner` L80 | v1.2 ghi "scheduled" — code dùng `pending`. | Đã có (tên khác v1.2) |
| 16 | BS-002 | Subscription Order (E-007) | `success` | Charge GMO thành công. | — | ✅ Có | pending | — | `BillingRunner` L118 | — | Đã có |
| 17 | BS-003 | Subscription Order (E-007) | `failed` | Charge thất bại (lỗi GMO hoặc exception). | — | ✅ Có | pending | — | `BillingRunner` L162 | `error_code` / `error_message` ghi kèm. Retry **tạo row mới** (billing_cycle_key mới) chứ không chuyển trạng thái hàng cũ. | Đã có |

**Lưu ý:** v1.2 liệt kê trạng thái `retry` — code **không** có billing status riêng cho retry. Khi retry, `BillingRunner` **tạo `SubscriptionOrder` mới** (với `billing_cycle_key` mới) bắt đầu từ `pending`. Trạng thái `failed` là **kết thúc** của row đó.

---

### **Hạng mục đã lược bỏ / điều chỉnh so với bản v1.2**

| Nội dung cũ (v1.2) | Lý do |
| --- | --- |
| SS-001 `pending_activation` là "trạng thái ban đầu" | Activator tạo trực tiếp `active`; `pending_activation` chưa qua flow nào. |
| SS-004 "Quá 14 ngày past_due → cancelled" | Code **không** tự cancel; sau max retry → `past_due` + dừng (next_billing +365d). |
| SS-005 "Lưu lý do hủy" | Entity **không** có cột `cancel_reason`. |
| Order States: tên chuỗi `new/paid/shipped/delivered/refunded` | EC-CUBE dùng **integer const** `NEW=1`, `CANCEL=3`, …, `RETURNED=9`. Không có `refunded`. |
| BS: trạng thái `scheduled` và `retry` | Code dùng `pending` / `success` / `failed`; retry = row mới. |
