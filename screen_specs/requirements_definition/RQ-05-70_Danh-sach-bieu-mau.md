# RQ-05-70_Danh-sach-bieu-mau

ユーザー: Nguyen Kien
作成日時: 8 tháng 5, 2026 9:51
最終更新日時: 11 tháng 5, 2026 (v1.3 — đối chiếu `app/Customize/`, `src/Eccube/`; thêm cột Trạng thái vs source)

# **Danh sách biểu mẫu (帳票一覧)**

> **Dự án**: Website EC thực phẩm chức năng — PRJ-SABURI-001 **Phiên bản**: v1.3
> 

## Đối chiếu source

### EC-CUBE core

- `src/Eccube/Entity/OrderPdf.php` + `src/Eccube/Repository/OrderPdfRepository.php` — core có chức năng **admin xuất PDF đơn hàng** (phía quản trị, không phải MyPage khách hàng).

### Customize

- **Không** tồn tại bất kỳ code nào liên quan đến biểu mẫu PDF trong `app/Customize/`: không có controller, service, template, hoặc entity cho việc sinh biên lai / invoice PDF.
- **Không** có chức năng tải PDF từ MyPage (MyPage subscription controller cũng chưa có — xem RQ-05-61).
- **Không** có code Invoice Seido (mã T-13, phân tách thuế suất 8%/10%).

---

| No | ID biểu mẫu | Tên biểu mẫu | Mục đích sử dụng | Người sử dụng | Cơ chế xuất | Định dạng | F-ID | Phạm vi | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | FRM-001 | Biên lai thanh toán (領収書) | Chứng từ thanh toán cho khách hàng. Tuân thủ Invoice Seido (Mã T-13, phân tách thuế suất 8%/10%, tên doanh nghiệp, ngày, tổng tiền). | Member (UT-02) | Sau mỗi đơn hàng thanh toán thành công (đơn lẻ + đơn định kỳ tự động) | PDF (tải từ My Page) | F-019 | Phase 2 | **Chưa triển khai.** Không có controller/service/template PDF trong Customize. EC-CUBE core có `OrderPdf` nhưng chỉ dành cho admin, không phải MyPage. Cần phát triển: PDF generator (Web-to-PDF hoặc thư viện), MyPage route, Invoice Seido compliance. | Chưa |

---

### **Hạng mục đã lược bỏ / điều chỉnh so với bản v1.2**

| Nội dung cũ (v1.2) | Lý do |
| --- | --- |
| FRM-001 phân loại "Đối tượng lần này" | Chuyển sang **Phase 2** — không có code nào trong repo. |
| "Sinh tự động từ Web-to-PDF" | Chức năng này chưa tồn tại. |
| "Phase 1 chỉ có 1 biểu mẫu" | Thực tế Phase 1 (code hiện tại) **không có biểu mẫu Customize nào**. |
