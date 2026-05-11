# RQ-05-90_Danh-sach-giao-dien-ngoai-vi

ユーザー: Nguyen Kien
作成日時: 8 tháng 5, 2026 9:51
最終更新日時: 11 tháng 5, 2026 (v1.3 — đối chiếu `app/Customize/Service/Gmo/GmoApiClient.php`, toàn bộ `app/Customize/`; thêm cột Trạng thái vs source)

# **Danh sách giao diện ngoại vi (外部インターフェース一覧)**

> **Dự án**: Website EC thực phẩm chức năng — PRJ-SABURI-001 **Phiên bản**: v1.3
> 

## Đối chiếu source

Repo hiện có **1 class** giao tiếp bên ngoài: `Customize\Service\Gmo\GmoApiClient`.

- Gọi GMO sandbox/production qua HTTPS POST (`EntryTran.idPass`, `ExecTran.idPass`).
- Xác thực: **ShopID + ShopPass** (env `GMO_SHOP_ID`, `GMO_SHOP_PASS`).
- 3 luồng charge public: `payWithTestCard()` (test card hardcode env), `payWithToken()` (card token), `payWithCard()` (card number trực tiếp).
- Có mock mode (`GMO_MOCK_MODE=1` → trả kết quả giả, không gọi GMO thật).

**Không** tồn tại trong repo: `SaveMember`, `SaveCard`, `SearchTrade`, `SiteID`/`SitePass`, Webhook controller, Gemini/Chatbot client, Google OAuth, LINE Login.

---

| No | External IF ID | Hệ thống đích | Hướng | Thời điểm | Phương thức | Tổng quan dữ liệu | F-ID | Phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | EIF-001 | GMO-PG (EntryTran) | Gửi → Nhận | Checkout / Cron billing | API (HTTPS POST) | Tạo transaction: ShopID, ShopPass, OrderID, JobCd, Amount → AccessID, AccessPass | F-005, F-013 | Đối tượng lần này | `GmoApiClient::entryTran()` (private). Gọi qua `payWithTestCard` / `payWithToken` / `payWithCard`. Endpoint: `{apiBaseUrl}/EntryTran.idPass`. | Đã có |
| 2 | EIF-002 | GMO-PG (ExecTran) | Gửi → Nhận | Checkout / Cron billing | API (HTTPS POST) | Thực hiện charge. 3 variant: (1) test card (hardcode env), (2) token, (3) card number trực tiếp → TranID, Approve, Status | F-005, F-013 | Đối tượng lần này | `GmoApiClient::execTran()`, `execTranWithToken()`, `payWithCard()`. **Không** có `execTranWithSavedCard` (card-on-file qua MemberID chưa triển khai). Timeout curl: 30s. **Không** có retry 3 lần khi timeout trong code — 1 lần duy nhất, thất bại → exception. | Một phần (thiếu saved card, retry timeout) |
| 3 | EIF-003 | GMO-PG (SaveMember) | Gửi → Nhận | Đăng ký tài khoản | API (HTTPS POST) | Tạo GMO member: SiteID, CustomerId → MemberID | F-006 | Phase 2 | **Không** tồn tại. Không có `SiteID` / `SitePass` trong code. | Chưa |
| 4 | EIF-004 | GMO-PG (SaveCard) | Gửi → Nhận | Lưu thẻ lần đầu / đổi thẻ | API (HTTPS POST) | Lưu card token: MemberID, Token → CardSeq | F-006 | Phase 2 | **Không** tồn tại. Phụ thuộc EIF-003 (SaveMember). | Chưa |
| 5 | EIF-005 | GMO-PG (SearchTrade) | Gửi → Nhận | Verify transaction | API (HTTPS POST) | Tra cứu: OrderID, ShopID → status, amount | F-005 | Phase 2 | **Không** tồn tại. | Chưa |
| 6 | EIF-006 | GMO-PG (Webhook) | Nhận ← | Sau charge (bất đồng bộ) | Webhook (HTTPS POST callback) | Kết quả charge: OrderID, TranID, Status → cập nhật billing_status | F-005, F-013 | Phase 2 | **Không** tồn tại. Không có webhook controller / HMAC verification. Hiện tại charge đồng bộ (request → response). | Chưa |
| 7 | EIF-007 | Google Gemini API | Gửi → Nhận | User hỏi Chatbot | API (HTTPS POST) | Prompt → Response (đề xuất SP) | F-015 | Phase 2 | **Không** tồn tại. Không có Chatbot service / client. | Chưa |
| 8 | EIF-008 | Google OAuth 2.0 | Gửi → Nhận | Đăng nhập/Đăng ký SNS | OAuth 2.0 (redirect flow) | Authorization code → access_token → user profile | F-018 | Phase 2 | **Không** tồn tại. Không có OAuth controller / config. | Chưa |
| 9 | EIF-009 | LINE Login API | Gửi → Nhận | Đăng nhập/Đăng ký SNS | OAuth 2.0 (redirect flow) | Authorization code → access_token → user profile | F-018 | Phase 2 | **Không** tồn tại. Không có LINE Login controller / config. | Chưa |

---

### **Hạng mục đã lược bỏ / điều chỉnh so với bản v1.2**

| Nội dung cũ (v1.2) | Lý do |
| --- | --- |
| EIF-002: "Token/CardSeq" + "Retry 3 lần khi timeout" | Code không có saved card (CardSeq qua MemberID). Curl gọi 1 lần duy nhất, không retry timeout. |
| EIF-003 ~ 005 (SaveMember, SaveCard, SearchTrade): phân loại "Đối tượng lần này" | Chuyển **Phase 2** — không có code, không có `SiteID`/`SitePass`. |
| EIF-006 (Webhook): phân loại "Đối tượng lần này" | Chuyển **Phase 2** — không có webhook controller. Charge hiện tại là đồng bộ. |
| EIF-007 (Gemini Chatbot): phân loại "Đối tượng lần này" | Chuyển **Phase 2** — không có code. |
| EIF-008, EIF-009 (Google OAuth, LINE Login): phân loại "Đối tượng lần này" | Chuyển **Phase 2** — không có OAuth / SNS login code. |
