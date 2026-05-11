# RQ-05-61_Danh-sach-API

ユーザー: Nguyen Kien
作成日時: 8 tháng 5, 2026 9:51
最終更新日時: 11 tháng 5, 2026 (v1.3 — đối chiếu `app/Customize/Service/Gmo/GmoApiClient.php`, `app/Customize/Controller/Admin/SubscriptionController.php`, toàn bộ `app/Customize/`; sửa mô tả sai; thêm cột Trạng thái vs source)

# **Danh sách API (API一覧)**

> **Dự án**: Website EC thực phẩm chức năng — PRJ-SABURI-001 **Phiên bản**: v1.3
> 

## Đối chiếu source

### Lớp GMO thực tế

Repo hiện có **1 class** GMO: `Customize\Service\Gmo\GmoApiClient` — **không** có `GmoPaymentService`. Class này cung cấp:

| Method | Tương ứng GMO API | Xác thực | Ghi chú |
| --- | --- | --- | --- |
| `entryTran()` (private) | `EntryTran.idPass` | ShopID + ShopPass | Tạo transaction |
| `execTran()` (private) | `ExecTran.idPass` | ShopID + ShopPass | Charge bằng test card (hardcode env) |
| `execTranWithToken()` (private) | `ExecTran.idPass` | ShopID + ShopPass | Charge bằng token |
| `payWithCard()` (public) | EntryTran + ExecTran | ShopID + ShopPass | Charge bằng card number trực tiếp |
| `payWithTestCard()` (public) | EntryTran + ExecTran | ShopID + ShopPass | Wrapper cho cron billing (test/mock) |
| `payWithToken()` (public) | EntryTran + ExecTran | ShopID + ShopPass | Wrapper cho token checkout |

**Không** tồn tại trong repo: `SaveMember`, `SaveCard`, `SearchTrade`, `SiteID`/`SitePass`, `GmoPaymentService`, `registerMember()`, `saveCardToken()`, `searchTrade()`, `execTranWithSavedCard()`.

### Controller / Route Customize

Chỉ có `Customize\Controller\Admin\SubscriptionController` với 5 route:

| Route name | HTTP | Path | Method |
| --- | --- | --- | --- |
| `admin_subscription_index` | GET | `/admin/subscription` | `index()` |
| `admin_subscription_index_page` | GET | `/admin/subscription/page/{page_no}` | `index()` |
| `admin_subscription_detail` | GET | `/admin/subscription/{id}` | `detail()` |
| `admin_subscription_retry` | POST | `/admin/subscription/{id}/retry-now` | `retryNow()` |
| `admin_subscription_force_bill` | POST | `/admin/subscription/{id}/force-bill` | `forceBill()` |
| `admin_subscription_cancel` | POST | `/admin/subscription/{id}/cancel` | `cancel()` |

**Không** tồn tại trong repo: MyPage subscription controller, Webhook controller, Chatbot controller, PDF Invoice controller, Dashboard controller, Auth/OAuth controller, CRM label.

---

## **API bên ngoài — GMO Payment (trực tiếp API, không dùng Plugin)**

| No | API ID | Tên API | Tổng quan xử lý | Chủ thể sử dụng | Xác thực | F-ID | Phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | API-EXT-001 | GMO-PG EntryTran | Tạo transaction mới (lấy AccessID + AccessPass) | Backend (`GmoApiClient`) | ShopID + ShopPass | F-005, F-013 | Đối tượng lần này | `GmoApiClient::entryTran()` (private, gọi qua `payWithTestCard` / `payWithToken` / `payWithCard`) | Đã có |
| 2 | API-EXT-002 | GMO-PG ExecTran | Thực hiện charge — có 3 variant: test card, token, card number trực tiếp | Backend (`GmoApiClient`) | ShopID + ShopPass | F-005, F-013 | Đối tượng lần này | `execTran()` (test card), `execTranWithToken()` (token), `payWithCard()` (card number). **Không** có `execTranWithSavedCard` — saved card (card-on-file qua MemberID) chưa triển khai. | Một phần |
| 3 | API-EXT-003 | GMO-PG SaveMember | Đăng ký customer → GMO member_id | — | SiteID + SitePass | F-006 | Đối tượng lần này | **Không** tồn tại trong `GmoApiClient`. Chưa có `SiteID` / `SitePass` trong code. | Chưa |
| 4 | API-EXT-004 | GMO-PG SaveCard | Lưu token thẻ vào member (card-on-file) | — | SiteID + SitePass | F-006 | Đối tượng lần này | **Không** tồn tại. | Chưa |
| 5 | API-EXT-005 | GMO-PG SearchTrade | Tra cứu transaction (verify status) | — | ShopID + ShopPass | F-005 | Đối tượng lần này | **Không** tồn tại. | Chưa |
| 6 | API-EXT-006 | Google Gemini API | Chatbot tư vấn: phân tích triệu chứng → đề xuất sản phẩm | — | API Key | F-015 | Phase 2 | **Không** tồn tại trong repo. Không có `ChatbotService` / `ChatbotController`. | Chưa |
| 7 | API-EXT-007 | Google OAuth 2.0 | Đăng nhập/đăng ký bằng tài khoản Google | — | Client ID + Secret | F-018 | Phase 2 | **Không** tồn tại trong repo. | Chưa |
| 8 | API-EXT-008 | LINE Login API | Đăng nhập/đăng ký bằng tài khoản LINE | — | Channel ID + Secret | F-018 | Phase 2 | **Không** tồn tại trong repo. | Chưa |

## **API nội bộ — MyPage Subscription**

| No | API ID | Tên API | Tổng quan xử lý | Chủ thể | Xác thực | F-ID | Phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 9 | API-INT-001 | GET /mypage/subscriptions | Danh sách subscription của user | Member (UT-02) | Session | F-008 | Phase 2 | **Không** tồn tại. Không có MyPage subscription controller. | Chưa |
| 10 | API-INT-002 | GET /mypage/subscriptions/{id} | Chi tiết subscription | Member (UT-02) | Session | F-008 | Phase 2 | **Không** tồn tại. | Chưa |
| 11 | API-INT-003 | POST /mypage/subscriptions/{id}/pause | Tạm dừng subscription | Member (UT-02) | Session + CSRF | F-010 | Phase 2 | **Không** tồn tại. | Chưa |
| 12 | API-INT-004 | POST /mypage/subscriptions/{id}/resume | Tiếp tục subscription | Member (UT-02) | Session + CSRF | F-010 | Phase 2 | **Không** tồn tại. | Chưa |
| 13 | API-INT-005 | POST /mypage/subscriptions/{id}/cancel | Hủy subscription (Tokushouhou UI) | Member (UT-02) | Session + CSRF | F-012 | Phase 2 | **Không** tồn tại. | Chưa |
| 14 | API-INT-006 | POST /mypage/payment/update-card | Đổi thẻ thanh toán | Member (UT-02) | Session + CSRF | F-006 | Phase 2 | **Không** tồn tại. | Chưa |

## **API nội bộ — Admin Subscription**

| No | API ID | Tên API | Tổng quan xử lý | Chủ thể | Xác thực | F-ID | Phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 15 | API-INT-007 | GET /admin/subscription | Danh sách subscription + filter (status, next billing, retry count, customer_id) | Admin (UT-03) | Admin Session | F-008 | Đối tượng lần này | `SubscriptionController::index()`. Phân trang Knp. | Đã có |
| 16 | API-INT-008 | GET /admin/subscription/{id} | Chi tiết subscription + items + billing history + event log | Admin (UT-03) | Admin Session | F-008 | Đối tượng lần này | `SubscriptionController::detail()` | Đã có |
| 17 | API-INT-009 | POST /admin/subscription/{id}/retry-now | Retry charge ngay lập tức (manual trigger) | Admin (UT-03) | Admin Session + CSRF | F-013 | Đối tượng lần này | `SubscriptionController::retryNow()` → `AdminService::billNow()` | Đã có |
| 18 | API-INT-010 | POST /admin/subscription/{id}/force-bill | Force charge (admin override, bypass schedule) | Admin (UT-03) | Admin Session + CSRF | F-013 | Đối tượng lần này | `SubscriptionController::forceBill()` → `AdminService::billNow()` | Đã có |
| 19 | API-INT-011 | POST /admin/subscription/{id}/cancel | Admin hủy subscription | Admin (UT-03) | Admin Session + CSRF | F-008 | Đối tượng lần này | `SubscriptionController::cancel()` → `AdminService::cancelSubscription()`. **Không** có trường "lý do hủy" (`cancel_reason`) trong code hiện tại. | Đã có (thiếu cancel_reason) |

## **API nội bộ — Webhook**

| No | API ID | Tên API | Tổng quan xử lý | Chủ thể | Xác thực | F-ID | Phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 20 | API-INT-012 | POST /api/gmo/webhook | Nhận kết quả charge từ GMO (ResultReceiveURL) | GMO Server | HMAC + ShopID | F-005, F-013 | Phase 2 | **Không** tồn tại trong repo. Không có webhook controller / HMAC verification. | Chưa |

## **API nội bộ — Khác (EC-CUBE chuẩn/mở rộng)**

| No | API ID | Tên API | Tổng quan xử lý | Chủ thể | Xác thực | F-ID | Phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 21 | API-INT-013 | Product List/Detail | Danh sách/chi tiết sản phẩm (filter theo danh mục) | Guest / Member | Không | F-001, F-002 | Đối tượng lần này | EC-CUBE core standard (không phải Customize) | EC-CUBE core |
| 22 | API-INT-014 | Cart API | Thêm/sửa/xóa giỏ hàng | Member | Session | F-004 | Đối tượng lần này | EC-CUBE core standard | EC-CUBE core |
| 23 | API-INT-015 | Checkout API | Xử lý đặt hàng + thanh toán qua GMO direct API | Member | Session + CSRF | F-005 | Đối tượng lần này | EC-CUBE core + Customize `ShoppingOrderTypeExtension` + `ShoppingCompleteSubscriber` + `GmoApiClient` | Đã có |
| 24 | API-INT-016 | Chatbot API | Nhận triệu chứng → Gemini API → đề xuất sản phẩm | — | — | F-015 | Phase 2 | **Không** tồn tại trong repo. | Chưa |
| 25 | API-INT-017 | PDF Invoice API | Sinh và tải PDF biên lai | — | — | F-019 | Phase 2 | **Không** tồn tại trong repo. | Chưa |
| 26 | API-INT-018 | Auth API | Đăng ký/đăng nhập/logout/SNS login (Google + LINE) | Guest / Member | — | F-016~F-018 | Đối tượng lần này | EC-CUBE core auth. **SNS login (Google + LINE) không tồn tại** trong Customize. | Một phần (EC-CUBE core only, SNS chưa có) |
| 27 | API-INT-019 | Admin Order API | Quản lý đơn hàng (CRUD, tracking, filter) | Admin | Admin Session | F-021 | Đối tượng lần này | EC-CUBE core admin. Không có Customize mở rộng cho order filter đơn lẻ/định kỳ. | EC-CUBE core |
| 28 | API-INT-020 | Admin Customer API | Quản lý khách hàng | Admin | Admin Session | F-022 | Đối tượng lần này | EC-CUBE core + Customize `CustomerSubscriptionViewSubscriber` (inject subscription vào customer edit). **Không** có CRM label (Subscriber/Churner). | Một phần (thiếu CRM label) |
| 29 | API-INT-021 | Admin Dashboard API | Thống kê: Churn Rate, MRR, doanh thu, biểu đồ | — | — | F-023 | Phase 2 | **Không** tồn tại trong repo. | Chưa |

---

### **Hạng mục đã lược bỏ / điều chỉnh so với bản v1.2**

| Nội dung cũ (v1.2) | Lý do |
| --- | --- |
| Class `GmoPaymentService` với methods `entryTran()`, `execTranWithSavedCard()`, `registerMember()`, `saveCardToken()`, `searchTrade()` | Class thực tế là `GmoApiClient`. Không có saved card / SaveMember / SaveCard / SearchTrade. |
| `SiteID + SitePass` cho SaveMember / SaveCard | **Không** tồn tại — code hiện chỉ dùng `ShopID + ShopPass`. |
| Toàn bộ MyPage Subscription API (API-INT-001 → 006) — phân loại "Đối tượng lần này" | Chuyển sang **Phase 2** — không có controller / route nào trong repo. |
| Webhook GMO (API-INT-012) — phân loại "Đối tượng lần này" | Chuyển sang **Phase 2** — không có controller / HMAC verification. |
| Chatbot API (API-EXT-006, API-INT-016) — phân loại "Đối tượng lần này" | Chuyển sang **Phase 2** — không có code liên quan. |
| Google OAuth / LINE Login (API-EXT-007, API-EXT-008) — phân loại "Đối tượng lần này" | Chuyển sang **Phase 2** — không có OAuth / SNS login code. |
| PDF Invoice API (API-INT-017) | Chuyển sang **Phase 2** — không có code. |
| Admin Dashboard API (API-INT-021) — Churn Rate, MRR | Chuyển sang **Phase 2** — không có code. |
| Admin Customer API có CRM label (Subscriber/Churner) | **Không** tồn tại. Customize chỉ inject subscription card, không có CRM label. |
| Admin cancel subscription "với lý do" | Route cancel **có**, nhưng `cancel_reason` field **không** tồn tại trên entity. |
