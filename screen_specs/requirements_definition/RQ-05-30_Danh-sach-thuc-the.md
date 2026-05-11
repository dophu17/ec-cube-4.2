# RQ-05-30_Danh-sach-thuc-the

ユーザー: Nguyen Kien
作成日時: 8 tháng 5, 2026 9:50
最終更新日時: 9 tháng 5, 2026 (v1.3.1 — làm rõ GMO on-file: Subscription vs phương án Customer 1:1)

# **Danh sách thực thể dữ liệu (データエンティティ一覧)**

> **Dự án**: Website EC thực phẩm chức năng — PRJ-SABURI-001 **Phiên bản**: v1.3.1
> 

## Đối chiếu source (phạm vi repo hiện tại)

- **Core EC-CUBE:** `Eccube\Entity\Product`, `Category`, `Customer`, `Order`, … — bảng `dtb_product`, `dtb_category`, `dtb_customer`, `dtb_order` theo schema chuẩn.
- **Customize subscription (Doctrine):** `Customize\Entity\Subscription`, `SubscriptionItem`, `SubscriptionOrder`, `SubscriptionEventLog` — migration khởi tạo `Version20260507103000.php`, bổ sung cột fulfillment `Version20260508070500.php`, chỉnh kiểu datetime `Version20260507120000.php`.
- **GMO (không tách entity riêng ngoài subscription / order):** **credential on-file** (member / thẻ đã đăng ký) dùng để **thu phí định kỳ** được snapshot trên `dtb_subscription` (`gmo_member_id`, `gmo_card_seq`, …); chi tiết từng lần charge nằm trên `dtb_subscription_order` (+ `Customize\Service\Gmo\GmoApiClient`). Đây là “thông tin thanh toán GMO **gắn với gói subscription**” trong code hiện tại — **không loại trừ** thiết kế thay thế: nếu nghiệp vụ là **một bộ GMO duy nhất trên mỗi khách**, có thể lưu canonical trên `dtb_customer` (hoặc bảng phụ Customer) và Subscription chỉ tham chiếu; repo hiện tại **chưa** làm vậy.

**Trạng thái vs source:** **Đã có** = khớp entity/bảng hoặc hành vi tương đương trong repo; **Một phần** = core có nhưng mô tả RQ thêm field/flow chưa thấy trong Customize; **Chưa** = không có bảng/entity tương ứng trong repo; **Khái niệm** = output / tài liệu nghiệp vụ, không phải bảng DB trong Customize.

---

| No | ID thực thể (エンティティID) | Tên thực thể (エンティティ名) | Loại thực thể (エンティティ種別) | Định nghĩa (定義) | Định danh (識別子) | Phân loại phạm vi (スコープ区分) | Ghi chú (備考) | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | E-001 | Sản phẩm (Product) | Master | Thực phẩm chức năng bán trên site. **Trong Customize hiện tại không có cột** `subscription_enabled` **trên** `dtb_product` — lựa chọn “mua định kỳ” là field form checkout **không map** (`ShoppingOrderTypeExtension`: `subscription_enabled`, `subscription_cycle`) và payload lưu **session** theo `pre_order_id` cho đến khi thanh toán xong. Giá đơn lẻ / snapshot subscription lấy từ đơn tại thời điểm kích hoạt. | `dtb_product.id` | Đối tượng lần này | Core `Eccube\Entity\Product`; mở rộng subscription theo session + `SubscriptionItem` snapshot. | Một phần |
| 2 | E-002 | Danh mục sản phẩm (Category) | Master | Phân loại sản phẩm. RQ mô tả “theo triệu chứng” là thiết kế nghiệp vụ / cấu hình danh mục; **Customize không thêm** entity category riêng. | `dtb_category.id` | Đối tượng lần này | EC-CUBE chuẩn. | Đã có |
| 3 | E-003 | Khách hàng (Customer) | Master | Người dùng đăng ký; mua hàng; liên kết `Subscription`. **Thông tin GMO dùng để charge định kỳ** trong Customize hiện được giữ **theo từng gói** trên `dtb_subscription` (`gmo_member_id`, `gmo_card_seq`, …) — tức snapshot/credential **phục vụ recurring của subscription đó**, không phải “Customer không có thanh toán GMO” (về dữ liệu chỉ là **chưa** tách cột GMO trên `dtb_customer`). Nếu quy ước nghiệp vụ là **1 khách = 1 GMO member** cho mọi gói, lưu canonical trên Customer (hoặc bảng con) là hợp lý; **không bắt buộc** phải đổi code chỉ vì lý do mô hình này. Liên kết SNS: plugin / core nếu có. | `dtb_customer.id` | Đối tượng lần này | Core `Eccube\Entity\Customer`. | Một phần |
| 4 | E-004 | Đơn hàng (Order) | Transaction | Đơn mua thường và đơn **gia hạn** do `SubscriptionBillingRunner` tạo (Order chuẩn EC-CUBE). **Không** có cờ “đơn định kỳ” riêng trên entity Order trong Customize; liên hệ subscription qua `dtb_subscription.base_order_id` và `dtb_subscription_order.order_id`. | `dtb_order.id` | Đối tượng lần này | Core `Eccube\Entity\Order`. | Đã có |
| 5 | E-005 | Đăng ký định kỳ (Subscription) | Transaction | Gói định kỳ của khách: `customer_id`, `base_order_id`, `status`, `plan_type`, `interval_count`, `next_billing_at`, `next_fulfillment_at`, `last_billed_at` / `last_fulfilled_at`, `cancelled_at`, `retry_count`, `max_retry`, snapshot tiền, `payment_gateway`, **`gmo_member_id` / `gmo_card_seq` / `gmo_last_order_id` — bộ tham chiếu GMO on-file dùng khi billing runner charge gói này** (cùng vai trò “thông tin thanh toán GMO” cho gói, không nhất thiết trùng với mọi gói khác của cùng khách). | `dtb_subscription.id` | Đối tượng lần này | `Customize\Entity\Subscription` | Đã có |
| 6 | E-006 | Sản phẩm trong Subscription (Subscription Item) | Transaction | Snapshot dòng hàng: `product_id`, `product_class_id`, tên/mã/giá/thuế/số lượng snapshot, `is_combo`, `combo_code` (hiện Activator set `is_combo = false`). | `dtb_subscription_item.id` | Đối tượng lần này | `Customize\Entity\SubscriptionItem` | Đã có |
| 7 | E-007 | Lịch sử billing Subscription (Subscription Order) | Transaction | Mỗi lần chạy billing: `subscription_id`, `order_id` (nullable đến khi tạo Order), **`billing_cycle_key` (unique — idempotent)**, `billing_scheduled_at`, `billing_executed_at`, `billing_status` (`pending` / `success` / `failed`), trường GMO (`gmo_order_id`, `gmo_access_id`, `gmo_access_pass`, `gmo_tran_id`, `gmo_approve`, `gmo_status`), `error_code`, `error_message`. | `dtb_subscription_order.id` | Đối tượng lần này | `Customize\Entity\SubscriptionOrder` | Đã có |
| 8 | E-008 | Event log Subscription | Log | `event_type` + `payload` (JSON text) + `create_date`. **Trong code hiện tại** có ghi ít nhất: `activated` (khi tạo subscription), `renewal_success`, `renewal_failure` (billing). Chuỗi event khác (paused, resumed, …) **có thể** ghi thêm trong tương lai — không ràng buộc enum trong DB. | `dtb_subscription_event_log.id` | Đối tượng lần này | `Customize\Entity\SubscriptionEventLog` | Một phần |
| 9 | E-009 | Custom Field sản phẩm (Product Custom Field) | Master | Tag triệu chứng / Chatbot theo RQ nghiệp vụ. | (theo RQ: `dtb_product_custom_field`) | Đối tượng lần này | **Không có** bảng hay entity này trong repo hiện tại. | Chưa |
| 10 | E-010 | Biên lai PDF (Invoice) | Output | PDF hóa đơn / chứng từ — có thể plugin hoặc dịch vụ ngoài; **không** là bảng Customize subscription. | Theo `order_id` / plugin | Đối tượng lần này | Khái niệm output; triển khai tùy cấu hình shop. | Khái niệm |

### **Hạng mục đã lược bỏ / điều chỉnh so với bản v1.2**

| Nội dung cũ (v1.2) | Lý do |
| --- | --- |
| E-001: “Có cờ `subscription_enabled` trên Product” | Sai với source: cờ chỉ tồn tại **form checkout** (unmapped) + session, không persist trên `Product`. |
| E-003: “Customer liên kết GMO Member ID” (ngầm trên customer) | Trong Customize, GMO on-file **đang** gắn **Subscription** (+ log `dtb_subscription_order`); **không** có cột GMO trên `dtb_customer`. Đây là lựa chọn triển khai (theo gói), không phủ nhận mô hình **1 khách = 1 GMO** lưu trên Customer nếu sau này chốt nghiệp vụ. |
| E-004: “filter đơn lẻ/định kỳ” trên Order | Không có field filter riêng trên Order trong Customize; phân biệt qua bảng subscription / subscription_order. |
| E-008: Liệt kê đầy đủ `created`, `billed`, `paused`, … như đã cố định trong hệ thống | Chỉ khớp một phần với **event_type** thực tế đang ghi trong code; cột là chuỗi tự do 64 ký tự. |
| E-009: “Bảng mở rộng mới `dtb_product_custom_field`” | **Không** tồn tại trong migration / entity repo này. |
