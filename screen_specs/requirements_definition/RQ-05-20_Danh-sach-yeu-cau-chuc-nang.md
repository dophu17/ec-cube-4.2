# RQ-05-20_Danh-sach-yeu-cau-chuc-nang

ユーザー: Nguyen Kien
作成日時: 8 tháng 5, 2026 9:49
最終更新日時: 9 tháng 5, 2026 (v1.4 — mục **Bốn khối Customize** + ghi chú đối chiếu file; cột Trạng thái vs source giữ nguyên)

# **Danh sách yêu cầu chức năng (機能要件一覧)**

> **Dự án**: Website EC thực phẩm chức năng — PRJ-SABURI-001 **Phiên bản**: v1.4
> 

## Bốn khối Customize hiện có (Subscription · GMO Direct · Admin subscription · Màn khách Admin)

Khi đọc cột **Ghi chú** / **Trạng thái vs source**, phần **tùy biến theo subscription** trong repo tập trung ở bốn khối sau (tất cả dưới `app/Customize/` trừ template admin override nằm trong `app/template/`):

| Khối | Thành phần chính (đường dẫn gốc repo) | Liên quan trực tiếp tới các F-ID trong bảng |
| --- | --- | --- |
| **1. Nhánh Subscription** | `Entity/Subscription*.php`, `Repository/*Subscription*`, `Service/Subscription/*` (Activator, Scheduler, BillingRunner, RenewalOrderFactory, CancellationService, AdminService, MailNotifier, CycleParser), `Form/Extension/ShoppingOrderTypeExtension.php`, `EventSubscriber/Subscription/SubscriptionShoppingCompleteSubscriber.php`, `Command/SubscriptionRunCommand.php`, `Command/SubscriptionCancelCommand.php`; tham số `SUBSCRIPTION_*` trong `Resource/config/services.yaml` — chi tiết nghiệp vụ: `README_SUBSCRIPTION_GMO.md`. | F-002 (phần checkout định kỳ / chu kỳ), F-004, F-007, F-013, F-014; F-009 / F-012 / F-023 ghi **Phase 2** trong README. |
| **2. GMO Direct** | `Service/Gmo/GmoApiClient.php`, `Service/Payment/Method/GmoDirectCreditCard.php`, `README_GMO_DIRECT.md`. | F-005, F-006, F-013 (thu phí gia hạn qua GMO). |
| **3. Admin subscription** | `Controller/Admin/SubscriptionController.php`, `app/template/admin/Subscription/*.twig`, khai báo menu `app/config/eccube/packages/eccube_nav.yaml` (`admin_subscription_*`). | Không có mã F riêng; hỗ trợ vận hành **F-021** (đơn gia hạn), **F-022** (xem/lọc subscription theo khách). |
| **4. Tích hợp màn khách (Admin)** | `EventSubscriber/Admin/CustomerSubscriptionViewSubscriber.php`, `app/template/admin/Customer/index.twig`, `edit.twig`, nhãn `admin.customer.subscription_*` trong `Resource/locale/messages.ja.yaml` / `messages.en.yaml`. | **F-022** (card subscription + link sang danh sách admin đã lọc `customer_id`). |

**Điểm còn lệch với README subscription (chưa có trong Customize storefront):** không có controller/route **My Page** `/mypage/subscriptions` — vì vậy **F-008**, **F-010**, **F-011** (Member) vẫn ghi **Chưa** ở cột trạng thái; admin vẫn có hủy / charge (F-011 chỉ đạt một phần nếu hiểu là “hủy subscription” nói chung).

---

## Đối chiếu source (mục đích cột **Trạng thái vs source**)

- **Đã có**: Hành vi hoặc màn hình tương ứng có trong **core EC-CUBE** hoặc **`app/Customize`** (bốn khối ở trên + phần còn lại của core) theo nội dung cột yêu cầu ở mức chấp nhận được.
- **Một phần**: Có nền tảng (core / Customize) nhưng **chưa khớp đủ** mô tả RQ (ví dụ: chu kỳ 30/60/90 vs weekly/monthly; Combini/PayPay vs thẻ GMO; My Page subscription chỉ ghi trong README, chưa có route storefront trong Customize).
- **Chưa**: Không thấy triển khai tương ứng trong Customize; phần storefront/core không đủ để coi là đạt RQ.
- **Phase 2**: Khớp với phạm vi **Phase 2** trong `README_SUBSCRIPTION_GMO.md` (hoặc tính năng chưa lên kế hoạch trong repo).

**Ghi chú role:** Member trong RQ tương ứng kỹ thuật **`ROLE_USER`** (`Customer`) — xem `RQ-05-10_Nguoi-dung-va-role.md`.

---

## **Đại phân loại: Quản lý sản phẩm (商品管理)**

| No | ID chức năng | Tên chức năng | Chủ thể thực thi | Điều kiện thực thi được | Tổng quan yêu cầu chức năng | ID yêu cầu | Mức độ ưu tiên | Phân loại phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | F-001 | Hiển thị danh sách sản phẩm | Guest / Member | Không có | Người dùng xem danh sách sản phẩm với bộ lọc theo danh mục triệu chứng (Giấc ngủ, Tập trung, Phục hồi v.v.) và tìm kiếm theo keyword. Hỗ trợ phân trang. Trường hợp không có sản phẩm, hiển thị thông báo trống. | RQ-01-01 | Bắt buộc | Đối tượng lần này | EC-CUBE Custom Category + Search | Một phần |
| 2 | F-002 | Hiển thị chi tiết sản phẩm | Guest / Member | Sản phẩm tồn tại và đang công khai | Hiển thị thông tin chi tiết sản phẩm. Có UI chuyển đổi Mua lẻ / Mua định kỳ. Khi chọn Định kỳ: hiển thị giá ưu đãi, cho phép chọn chu kỳ giao (30/60/90 ngày). | RQ-01-02 | Bắt buộc | Đối tượng lần này | Khối 1: checkout định kỳ qua `ShoppingOrderTypeExtension` + Twig checkout (chu kỳ: test / weekly / monthly theo `SubscriptionCycleParser`, không phải 30/60/90 cố định). | Một phần |
| 3 | F-003 | Quản lý sản phẩm (CRUD) | Admin | Đã đăng nhập Admin | Admin tạo/sửa/xóa sản phẩm. Thiết lập giá định kỳ riêng, tỷ lệ chiết khấu, Custom Field (tag triệu chứng cho Chatbot). | RQ-01-03 | Bắt buộc | Đối tượng lần này | Core admin sản phẩm; bốn khối Customize **không** thêm CRUD tag Chatbot / triệu chứng. | Một phần |

## **Đại phân loại: Giỏ hàng · Thanh toán (カート・決済)**

| No | ID chức năng | Tên chức năng | Chủ thể thực thi | Điều kiện thực thi được | Tổng quan yêu cầu chức năng | ID yêu cầu | Mức độ ưu tiên | Phân loại phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 4 | F-004 | Thêm vào giỏ hàng | Member | Đã đăng nhập | Member thêm sản phẩm vào giỏ (đơn lẻ hoặc định kỳ). Loại mua hàng (đơn lẻ/định kỳ) và chu kỳ giao được lưu cùng. | RQ-01-04 | Bắt buộc | Đối tượng lần này | Khối 1: mở rộng form đặt hàng / session payload subscription (kết hợp core giỏ EC-CUBE). | Một phần |
| 5 | F-005 | Checkout · Thanh toán | Member | Giỏ hàng có ≥1 sản phẩm | Luồng Checkout: xác nhận địa chỉ → chọn phương thức thanh toán.Đơn lẻ: Hỗ trợ linh hoạt Thẻ tín dụng, Combini, PayPay v.v. qua GMO.Đơn Định kỳ: Chỉ hỗ trợ Thẻ tín dụng (Lưu Token).Trường hợp định kỳ: hiển thị rõ tổng giá gói, điều kiện hủy (Tokushouhou). | RQ-01-05 | Bắt buộc | Đối tượng lần này | Khối 2: **GMO Direct** trong Customize; không thấy Combini/PayPay trong bốn khối — phần còn lại là core/plugin thanh toán shop. | Một phần |
| 6 | F-006 | Lưu thông tin thẻ tín dụng | Member | Thanh toán thành công lần đầu | Thông tin thẻ được tokenize qua GMO-PG (không lưu trên server). Token được sử dụng cho thanh toán định kỳ tự động. | RQ-01-06 | Bắt buộc | Đối tượng lần này | Khối 2 + entity subscription (`gmo_member_id` / `gmo_card_seq` snapshot); luồng đầy đủ PCI xem cấu hình GMO thật. | Một phần |

## **Đại phân loại: Đăng ký Định kỳ — Subscription (定期購入)**

| No | ID chức năng | Tên chức năng | Chủ thể thực thi | Điều kiện thực thi được | Tổng quan yêu cầu chức năng | ID yêu cầu | Mức độ ưu tiên | Phân loại phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 7 | F-007 | Đăng ký gói Định kỳ | Member | Checkout thành công với sản phẩm định kỳ | Hệ thống tạo Subscription với status=active, ghi nhận ngày giao tiếp theo và chu kỳ giao. | RQ-01-07 | Bắt buộc | Đối tượng lần này | Khối 1: `SubscriptionActivator` + `SubscriptionShoppingCompleteSubscriber` sau thanh toán; `next_billing_at` / `next_fulfillment_at` theo `SubscriptionScheduler`. | Đã có |
| 8 | F-008 | Xem danh sách Subscription | Member | Đã đăng nhập, có ≥1 Subscription | Member xem danh sách các gói đăng ký định kỳ của mình trên My Page. Hiển thị: tên SP, chu kỳ, ngày giao tiếp theo, trạng thái. | RQ-01-08 | Bắt buộc | Đối tượng lần này | Bốn khối **không** có route storefront My Page; chỉ xem subscription qua **Khối 3/4 (Admin)**. | Chưa |
| 9 | F-009 | Bỏ qua kỳ giao (Skip) | Member | Subscription status=active, ≥3 ngày trước ngày giao | Member bỏ qua 1 kỳ giao hàng (1 Click). Hệ thống cập nhật ngày giao tiếp theo và ghi log skip. | RQ-01-09 | Bắt buộc | Đối tượng lần này | UI 1 Click | Phase 2 |
| 10 | F-010 | Tạm dừng Subscription | Member | Subscription status=active | Member tạm dừng đăng ký (status=paused). Cron-job bỏ qua Subscription paused. Có thể kích hoạt lại bất kỳ lúc nào. | RQ-01-10 | Bắt buộc | Đối tượng lần này | Trạng `paused` có trong entity; **Khối 1** không expose pause/resume cho Member (cron bỏ qua paused khi query `active`). | Chưa |
| 11 | F-011 | Hủy đăng ký Subscription | Member | Subscription status=active hoặc paused | Member hủy (status=cancelled). UI hủy phải truy cập được trong tối đa 2 click từ My Page (Tokushouhou). Biểu mẫu hủy thu thập lý do. | RQ-01-11 | Bắt buộc | Đối tượng lần này | Member: **Chưa**. Admin: **Khối 3** `SubscriptionAdminService::cancelSubscription` (+ form hủy trên `detail.twig`). | Chưa |
| 12 | F-012 | Thay đổi chu kỳ / ngày giao | Member | Subscription status=active | Member thay đổi chu kỳ giao (30/60/90 ngày) hoặc ngày giao cụ thể. Hệ thống cập nhật next_delivery_date. | RQ-01-12 | Bắt buộc | Đối tượng lần này | — | Phase 2 |
| 13 | F-013 | Cron-job tự động thu phí | Hệ thống | Batch đêm (02:00 JST), Subscription status=active, next_delivery_date = hôm nay | Cron-job quét toàn bộ Subscription đến hạn → Tự động tạo đơn hàng → Gọi GMO-PG API thu phí → Cập nhật next_delivery_date. **Bắt buộc idempotent** (tránh double-charge). | RQ-01-13 | Bắt buộc | Đối tượng lần này | Khối 1: `subscription:run` + `SubscriptionBillingRunner`; idempotent `billing_cycle_key` (README); giờ chạy do **cron hệ thống** chứ không cố định 02:00 JST trong code. Khối 2: charge GMO trong runner. | Một phần |
| 14 | F-014 | Dunning Process | Hệ thống | Thanh toán Subscription thất bại | Tự động retry thanh toán (tối đa 3 lần, cách nhau 2 ngày). Sau mỗi retry thất bại: gửi email nhắc cập nhật thẻ. Sau 3 lần thất bại: tự động tạm dừng Subscription. | RQ-01-14 | Bắt buộc | Đối tượng lần này | Khối 1: `SUBSCRIPTION_RETRY_DAYS` (mặc định 1,3,7) + `SubscriptionMailNotifier`; sau max retry → `past_due` (README), **không** auto `paused` như RQ gốc. | Một phần |

## **Đại phân loại: AI Chatbot (AIチャットボット)**

| No | ID chức năng | Tên chức năng | Chủ thể thực thi | Điều kiện thực thi được | Tổng quan yêu cầu chức năng | ID yêu cầu | Mức độ ưu tiên | Phân loại phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 15 | F-015 | Chatbot tư vấn sản phẩm | Guest / Member | Không có | Người dùng nhập triệu chứng → Hệ thống đối chiếu Custom Field sản phẩm → Gọi Gemini API phân tích → Trả về đề xuất sản phẩm. Prompt bắt buộc chèn disclaimer Luật Dược cơ. | RQ-01-15 | Bắt buộc | Đối tượng lần này | Tuân thủ Yakkihou | Chưa |

## **Đại phân loại: Xác thực · Tài khoản (認証・アカウント)**

| No | ID chức năng | Tên chức năng | Chủ thể thực thi | Điều kiện thực thi được | Tổng quan yêu cầu chức năng | ID yêu cầu | Mức độ ưu tiên | Phân loại phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 16 | F-016 | Đăng ký tài khoản | Guest | Không có | Guest đăng ký tài khoản bằng Email + Password hoặc SNS (Google/LINE). Xác thực email. Sau đăng ký → gán Role Buyer. | RQ-01-16 | Bắt buộc | Đối tượng lần này | — | Một phần |
| 17 | F-017 | Đăng nhập | Guest | Tài khoản đã đăng ký | Đăng nhập bằng Email/Password hoặc Google/LINE OAuth. Session timeout: 30 phút. | RQ-01-17 | Bắt buộc | Đối tượng lần này | — | Một phần |
| 18 | F-018 | Đăng nhập SNS (Google/LINE) | Guest | Không có | Đăng nhập/Đăng ký bằng Google OAuth 2.0 hoặc LINE Login. Tài khoản mới tự động tạo, liên kết SNS ID. | RQ-01-18 | Bắt buộc | Đối tượng lần này | Phase 1 | Chưa |
| 25 | F-025 | Quên mật khẩu / Reset password | Guest | Tài khoản đã đăng ký | Nhập email → hệ thống gửi link reset (token hết hạn 30 phút) → user đặt mật khẩu mới. | RQ-01-16 | Bắt buộc | Đối tượng lần này | EC-CUBE chuẩn | Đã có |
| 26 | F-026 | Đăng xuất (Logout) | Member | Đã đăng nhập | Hủy session, chuyển hướng về trang chủ. | RQ-01-17 | Bắt buộc | Đối tượng lần này | EC-CUBE chuẩn | Đã có |
| 27 | F-027 | Chỉnh sửa Profile | Member | Đã đăng nhập | Thay đổi tên, email, địa chỉ, số điện thoại, mật khẩu trên My Page. Xác thực lại email khi thay đổi email. | RQ-01-16 | Bắt buộc | Đối tượng lần này | EC-CUBE chuẩn + mở rộng | Đã có |

## **Đại phân loại: Chứng từ · Biên lai (帳票)**

| No | ID chức năng | Tên chức năng | Chủ thể thực thi | Điều kiện thực thi được | Tổng quan yêu cầu chức năng | ID yêu cầu | Mức độ ưu tiên | Phân loại phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 19 | F-019 | Tải PDF biên lai | Member | Đơn hàng đã thanh toán thành công | Member tải PDF biên lai từ My Page. PDF chứa: Mã T-13 chữ số, phân tách thuế suất (8%/10%), tên doanh nghiệp, ngày, tổng tiền. | RQ-01-19 | Bắt buộc | Đối tượng lần này | Tuân thủ Invoice Seido | Một phần |

## **Đại phân loại: Đa ngôn ngữ (多言語)**

| No | ID chức năng | Tên chức năng | Chủ thể thực thi | Điều kiện thực thi được | Tổng quan yêu cầu chức năng | ID yêu cầu | Mức độ ưu tiên | Phân loại phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 20 | F-020 | Chuyển đổi ngôn ngữ | Guest / Member | Không có | Chuyển đổi giao diện giữa Tiếng Nhật và Tiếng Anh. Toàn bộ 12 màn hình Frontend hỗ trợ i18n. | RQ-01-20 | Bắt buộc | Đối tượng lần này | — | Một phần |

## **Đại phân loại: Nội dung tĩnh (CMS)**

| No | ID chức năng | Tên chức năng | Chủ thể thực thi | Điều kiện thực thi được | Tổng quan yêu cầu chức năng | ID yêu cầu | Mức độ ưu tiên | Phân loại phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 21 | F-024 | Trang thông tin tĩnh | Guest / Member | Không có | Hiển thị các trang nội dung tĩnh cấu hình từ CMS (Tokushouhou, Privacy Policy, About Us, FAQ). Cần thiết để duyệt cổng thanh toán GMO. | RQ-01-24 | Bắt buộc | Đối tượng lần này | Dùng CMS chuẩn EC-CUBE | Đã có |

## **Đại phân loại: Quản trị · Báo cáo (管理・レポート)**

| No | ID chức năng | Tên chức năng | Chủ thể thực thi | Điều kiện thực thi được | Tổng quan yêu cầu chức năng | ID yêu cầu | Mức độ ưu tiên | Phân loại phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 22 | F-021 | Quản lý đơn hàng | Admin | Đã đăng nhập Admin | Xem danh sách đơn hàng (đơn lẻ + định kỳ). Cập nhật trạng thái, nhập mã tracking vận chuyển. Khi cập nhật shipped → gửi email thông báo vận chuyển cho user (NTF-011). Hiển thị số kỳ liên tục. | RQ-01-21 | Bắt buộc | Đối tượng lần này | Core admin đơn; đơn **gia hạn** do Khối 1 tạo (Order chuẩn). Cột “số kỳ liên tục” **không** có trong Customize. | Một phần |
| 23 | F-022 | Quản lý khách hàng | Admin | Đã đăng nhập Admin | Xem danh sách khách hàng, lịch sử mua, nhãn CRM. Quản lý người hủy định kỳ, thống kê lý do hủy. | RQ-01-22 | Bắt buộc | Đối tượng lần này | **Khối 4** + repo: cột đếm subscription, card trên edit, deep-link **Khối 3** `?customer_id=`. CRM / lý do hủy: **không** trong bốn khối. | Một phần |
| 24 | F-023 | Dashboard thống kê | Admin | Đã đăng nhập Admin | Biểu đồ: Churn Rate, doanh thu theo thời gian, tỷ lệ duy trì Subscription, số lượng Subscription active. | RQ-01-23 | Quan trọng | Đối tượng lần này | Logic tùy chỉnh | Phase 2 |

## **Đại phân loại: My Page (マイページ)**

| No | ID chức năng | Tên chức năng | Chủ thể thực thi | Điều kiện thực thi được | Tổng quan yêu cầu chức năng | ID yêu cầu | Mức độ ưu tiên | Phân loại phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 28 | F-028 | Xem lịch sử đơn hàng | Member | Đã đăng nhập, có ≥1 đơn hàng | Xem danh sách và chi tiết đơn hàng đã mua (bao gồm đơn lẻ và đơn định kỳ tự động). Hiển thị: mã đơn, ngày, danh sách SP, tổng tiền, trạng thái, mã tracking. | RQ-01-08 | Bắt buộc | Đối tượng lần này | Core My Page; đơn gia hạn là Order thường — Customize **không** gắn nhãn “subscription renewal” riêng trên UI My Page. | Đã có |
