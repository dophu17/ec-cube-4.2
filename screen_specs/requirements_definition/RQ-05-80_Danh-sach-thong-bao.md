# RQ-05-80_Danh-sach-thong-bao

ユーザー: Nguyen Kien
作成日時: 8 tháng 5, 2026 9:51
最終更新日時: 11 tháng 5, 2026 (v1.3 — đối chiếu `src/Eccube/Service/MailService.php`, `app/Customize/Service/Subscription/SubscriptionMailNotifier.php`; sửa mô tả sai; thêm cột Trạng thái vs source)

# **Danh sách thông báo (通知一覧)**

> **Dự án**: Website EC thực phẩm chức năng — PRJ-SABURI-001 **Phiên bản**: v1.3
> 

## Đối chiếu source

### EC-CUBE core (`src/Eccube/Service/MailService.php`)

| Method | Mục đích |
| --- | --- |
| `sendCustomerConfirmMail()` | Email xác thực đăng ký (link activate) |
| `sendCustomerCompleteMail()` | Email hoàn tất đăng ký |
| `sendOrderMail()` | Email xác nhận đơn hàng cho khách |
| `sendAdminOrderMail()` | Email thông báo đơn hàng cho admin |
| `sendShippingNotifyMail()` | Email thông báo vận chuyển |
| `sendPasswordResetNotificationMail()` | Email reset mật khẩu |
| `sendContactMail()` | Email từ form liên hệ |
| `sendCustomerWithdrawMail()` | Email xác nhận rút tài khoản |

### Customize (`app/Customize/Service/Subscription/SubscriptionMailNotifier.php`)

| Method | Mục đích |
| --- | --- |
| `notifyRenewalSuccess()` | Email gia hạn subscription thành công (subject: `[Balocco] Gia hạn subscription thành công`). Nội dung: subscription ID, mã đơn mới, số tiền, ngày fulfillment dự kiến. |
| `notifyRenewalFailed()` | Email gia hạn thất bại. Có 2 variant: (1) retry chưa hết → "Hệ thống sẽ tự động thử lại 1/3/7 ngày", (2) `finalFailure=true` → "Subscription tạm dừng do vượt max retry". |

**Không** tồn tại trong repo: email xác nhận đăng ký subscription lần đầu (NTF-003), email nhắc trước charge (NTF-004), email xác nhận skip (NTF-009), email xác nhận hủy (NTF-010), email phân biệt lần 1/2/3 retry.

---

## **Email thông báo**

| No | ID thông báo | Tên thông báo | Nội dung | Trigger | Đối tượng | Phương tiện | F-ID | RQ-ID | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | NTF-001 | Xác nhận đăng ký tài khoản | Link xác thực email, thông tin tài khoản | Đăng ký tài khoản | Member (UT-02) | Email | F-016 | RQ-01-16 | EC-CUBE core `sendCustomerConfirmMail()` + `sendCustomerCompleteMail()` | EC-CUBE core |
| 2 | NTF-002 | Xác nhận đơn hàng | Mã đơn, danh sách sản phẩm, tổng tiền, ngày giao dự kiến | Checkout thành công (đơn lẻ) | Member (UT-02) | Email | F-005 | RQ-01-05 | EC-CUBE core `sendOrderMail()` | EC-CUBE core |
| 3 | NTF-003 | Xác nhận đăng ký định kỳ | Tên SP, chu kỳ, giá, ngày charge tiếp theo, **cách hủy** (Tokushouhou) | Checkout thành công (sản phẩm định kỳ) | Member (UT-02) | Email | F-007 | RQ-01-07 | **Chưa triển khai.** Checkout subscription dùng `sendOrderMail()` core chung, **không** có template riêng ghi hướng dẫn hủy / Tokushouhou. | Chưa |
| 4 | NTF-004 | Nhắc trước giao hàng | Thông báo charge sắp diễn ra (3 ngày trước). Link tạm dừng/skip. | BAT-003 (3 ngày trước next_billing_at) | Member (UT-02) | Email | — | RQ-01-08 | **Chưa triển khai.** BAT-003 (`subscription:notify-upcoming`) không tồn tại. | Chưa |
| 5 | NTF-005 | Xác nhận thu phí định kỳ | Mã đơn tự động, số tiền, danh sách SP, ngày charge tiếp theo | `subscription:run` charge thành công | Member (UT-02) | Email | F-013 | RQ-01-13 | `SubscriptionMailNotifier::notifyRenewalSuccess()`. Nội dung: subscription ID, mã đơn, số tiền, ngày fulfillment. **Thiếu** so với spec: danh sách SP chi tiết, ngày charge tiếp theo (chỉ ghi fulfillment). | Một phần |
| 6 | NTF-006 | Thanh toán thất bại (retry chưa hết) | Thông báo thanh toán thất bại. Hướng dẫn cập nhật thẻ. | `subscription:run` charge thất bại, `finalFailure=false` | Member (UT-02) | Email | F-014 | RQ-01-14 | `SubscriptionMailNotifier::notifyRenewalFailed(_, _, false)`. Nội dung: lý do lỗi + "hệ thống sẽ tự động thử lại 1/3/7 ngày". **Không** phân biệt email lần 1/2/3 — cùng 1 template cho mọi retry. **Thiếu** link MyPage cập nhật thẻ (MyPage chưa có). | Một phần |
| 7 | NTF-007 | Thanh toán thất bại (lần 2 — nhắc lại) | Nhắc lại. Cảnh báo subscription sẽ bị tạm dừng. | BAT-002 retry lần 2 | Member (UT-02) | Email | F-014 | RQ-01-14 | **Không tách riêng.** Code dùng chung NTF-006 cho mọi retry chưa hết — không có email riêng lần 2. | Khái niệm (gộp NTF-006) |
| 8 | NTF-008 | Subscription tạm dừng do thanh toán thất bại | Thông báo gói bị tạm dừng (past_due). Hướng dẫn cập nhật thẻ. | `subscription:run` vượt max_retry | Member (UT-02) | Email | F-014 | RQ-01-14 | `SubscriptionMailNotifier::notifyRenewalFailed(_, _, true)`. Subject: `[Balocco] Subscription tạm dừng do thanh toán thất bại`. **Lưu ý**: code set `past_due` chứ không `cancelled`. **Thiếu** link kích hoạt lại / cập nhật thẻ (MyPage chưa có). | Một phần |
| 9 | NTF-009 | Xác nhận Skip kỳ giao | Xác nhận đã bỏ qua kỳ giao. Ngày charge mới. | Member bấm Skip | Member (UT-02) | Email | F-009 | RQ-01-09 | **Chưa triển khai.** Chức năng Skip chưa có. | Chưa (Phase 2) |
| 10 | NTF-010 | Xác nhận hủy đăng ký | Xác nhận hủy. Gói không tự động charge nữa. | Member / Admin hủy Subscription | Member (UT-02) | Email | F-011 | RQ-01-11 | **Chưa triển khai.** `AdminService::cancelSubscription()` và `CancellationService::cancel()` không gửi email. | Chưa |
| 11 | NTF-011 | Thông báo vận chuyển | Đơn hàng đã giao cho hãng vận chuyển. Mã tracking, tên hãng, link tra cứu. | Admin cập nhật trạng thái shipped | Member (UT-02) | Email | F-021 | RQ-01-21 | EC-CUBE core `sendShippingNotifyMail()` | EC-CUBE core |

---

### **Hạng mục đã lược bỏ / điều chỉnh so với bản v1.2**

| Nội dung cũ (v1.2) | Lý do |
| --- | --- |
| NTF-003: "Bắt buộc ghi hướng dẫn hủy" — phân loại "Đối tượng lần này" | **Chưa triển khai** — không có template subscription riêng; checkout dùng `sendOrderMail()` core. |
| NTF-004: email nhắc 3 ngày trước — trigger "BAT-003" | **Chưa** — BAT-003 (`subscription:notify-upcoming`) không tồn tại. |
| NTF-006 / NTF-007 / NTF-008 tách 3 email riêng cho retry lần 1, 2, 3 | Code chỉ có **2 variant**: retry chưa hết (chung) và final failure. Không phân biệt lần 1 vs lần 2. |
| NTF-006/007: "Link MyPage cập nhật thẻ" | MyPage subscription controller chưa tồn tại. |
| NTF-008: "hủy" | Code set `past_due`, **không** tự `cancelled`. |
| NTF-009: skip — phân loại "Phase 2" | Đúng — vẫn Phase 2. |
| NTF-010: email xác nhận hủy — phân loại "Đối tượng lần này" | **Chưa triển khai** — hủy subscription không gửi email. |
