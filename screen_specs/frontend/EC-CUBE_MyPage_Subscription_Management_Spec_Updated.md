EC-CUBE My Page Subscription Management Spec (Updated)

> Lean spec version for AI coding (kept sections 3 and 7 as requested).

1. 概要 / Overview

Mục đích:
Màn hình cho phép customer quản lý toàn bộ các gói 定期購入 (subscription / recurring purchase).

User có thể:
- Xem danh sách subscription đang sử dụng
- Xem subscription đã huỷ
- Kiểm tra chu kỳ giao hàng
- Kiểm tra ngày giao hàng tiếp theo
- Kiểm tra ngày thanh toán tiếp theo
- Kiểm tra giá trị đơn hàng
- Kiểm tra địa chỉ nhận hàng
- Kiểm tra phương thức thanh toán
- Xem chi tiết subscription
- Đăng ký thêm subscription mới

Vai trò trong hệ thống:
Login → My Page → Subscription Management → Subscription Detail

User:
- Logged-in customer
- Guest: No
- Admin: Out of scope

2. スコープ / Scope

✅ In Scope
- Subscription list
- Active / cancelled tab
- Subscription card
- Detail button
- Create new subscription CTA
- Responsive desktop/mobile
- FAQ block
- Subscription info notice block
- Ownership check
- Payment/address warning display

❌ Out of Scope
- Checkout flow
- Payment retry
- Admin management
- Product recommendation engine
- Coupon apply flow
- Subscription edit modal nội bộ
- Payment method edit detail

3. 画面 / 機能構成

Subscription Management Screen
├─ Header
├─ My Page Side Menu
├─ Subscription Tab Navigation
│ ├─ Active Subscription Tab
│ └─ Cancelled Subscription Tab
├─ Subscription List
│ └─ Subscription Card
├─ New Subscription CTA
├─ Subscription Notice Block
├─ FAQ Button
└─ Footer

4. パラメータ / Parameters

| name | type | required | default | description |
|---|---|---|---|---|
| tab | string | no | active | active / cancelled / paused |
| page | integer | no | 1 | Pagination |
| limit | integer | no | 10 | Items per page |
| status | string | no | active | Subscription status |
| customer_id | integer | yes | - | Logged-in customer id |
| subscription_id | integer | no | - | Subscription id for detail |
| sort | string | no | next_delivery_asc | Sort condition |

5. データソース / Data Source

## 5.1 EC-CUBE Standard Tables

| Table | Description | Usage |
|---|---|---|
| dtb_customer | Customer master | Ownership check |
| dtb_customer_address | Customer address | Shipping address reference |
| dtb_order | Order master | Base order / generated recurring orders |
| dtb_order_item | Order items | Subscription product snapshot |
| dtb_shipping | Shipping | Shipping address / delivery info |
| dtb_payment | Payment method | Payment label |
| dtb_delivery | Delivery method | Delivery method/cycle display if applicable |
| dtb_product | Product master | Product name/image/status reference |
| dtb_product_class | Product class/SKU | Price/stock/product code |
| dtb_product_image | Product image | Subscription card image |
| mtb_order_status | Order status | Related order status display |
| mtb_pref | Prefecture master | Address display |

Note:
- EC-CUBE core thường không có subscription table chuẩn.
- Nếu project dùng subscription plugin, ưu tiên dùng entity/table của plugin.
- Không sửa trực tiếp core table.

## 5.2 Existing Plugin Tables To Check

| Table / Plugin | Note |
|---|---|
| Subscription plugin tables | Ưu tiên dùng nếu đã có: subscription contract, recurring order, cycle, status |
| GMO payment plugin tables | Payment method/card reference display |
| PayPay payment plugin tables | PayPay linked account/payment display |
| Shipping/delivery plugin tables | Nếu có delivery schedule riêng |
| Coupon/point plugin tables | Nếu subscription có coupon/point riêng |

## 5.3 Custom Tables - đề xuất nếu chưa có

### dtb_customer_subscription

Core custom table cho subscription nếu project chưa có plugin.

| column | type | required | note |
|---|---|---|---|
| id | int | yes | PK |
| customer_id | int | yes | FK → dtb_customer.id |
| status | varchar | yes | active / paused / cancelled / payment_failed |
| delivery_cycle_type | varchar | yes | day / week / month |
| delivery_cycle_value | int | yes | e.g. 30 |
| next_delivery_date | date | no | Next delivery date |
| next_payment_date | date | no | Next payment date |
| discount_rate | decimal | no | Subscription discount |
| shipping_id | int | no | FK → dtb_shipping or custom address snapshot |
| payment_method_id | int | no | FK → customer payment method / plugin |
| first_order_id | int | no | First generated order id |
| last_order_id | int | no | Last generated order id |
| create_date | datetime | yes | EC-CUBE convention |
| update_date | datetime | yes | EC-CUBE convention |

### dtb_customer_subscription_shipping

Dùng nếu cần snapshot địa chỉ nhận hàng riêng cho subscription.

| column | type | required | note |
|---|---|---|---|
| id | int | yes | PK |
| subscription_id | int | yes | FK → dtb_customer_subscription.id |
| name01 | varchar | yes | Last name |
| name02 | varchar | yes | First name |
| kana01 | varchar | no | Last kana |
| kana02 | varchar | no | First kana |
| postal_code | varchar | yes | Postal code |
| pref_id | int | yes | FK → mtb_pref.id |
| addr01 | varchar | yes | Address 1 |
| addr02 | varchar | no | Address 2 |
| phone_number | varchar | yes | Phone |
| create_date | datetime | yes | EC-CUBE convention |
| update_date | datetime | yes | EC-CUBE convention |

### dtb_customer_subscription_payment

Đồng nhất với Payment Methods spec. Chỉ tạo nếu subscription plugin chưa có mapping payment method.

| column | type | required | note |
|---|---|---|---|
| id | int | yes | PK |
| subscription_id | int | yes | FK → dtb_customer_subscription.id |
| customer_id | int | yes | FK → dtb_customer.id |
| customer_payment_method_id | int | no | FK → dtb_customer_payment_method.id |
| payment_method | varchar | yes | credit_card / paypay / bank_transfer |
| provider | varchar | no | gmo / paypay |
| provider_payment_id | varchar | no | GMO card_seq / PayPay ref |
| status | varchar | yes | active / expired / invalid |
| create_date | datetime | yes | EC-CUBE convention |
| update_date | datetime | yes | EC-CUBE convention |

### dtb_customer_subscription_history

Audit/history cho thay đổi subscription.

| column | type | required | note |
|---|---|---|---|
| id | int | yes | PK |
| subscription_id | int | yes | FK → dtb_customer_subscription.id |
| customer_id | int | yes | FK → dtb_customer.id |
| event | varchar | yes | created / paused / resumed / cancelled / payment_failed |
| before_data | json/text | no | Sanitized previous value |
| after_data | json/text | no | Sanitized new value |
| create_date | datetime | yes | EC-CUBE convention |

### dtb_subscription_batch_log

Optional. Dùng nếu batch tạo order/thanh toán định kỳ cần audit.

| column | type | required | note |
|---|---|---|---|
| id | int | yes | PK |
| subscription_id | int | yes | FK → dtb_customer_subscription.id |
| batch_type | varchar | yes | payment / order_create / delivery_update |
| status | varchar | yes | success / failed / skipped |
| message | text | no | Sanitized log |
| processed_at | datetime | yes | Processed time |
| create_date | datetime | yes | EC-CUBE convention |

## 5.4 API / Routes

| API / Route | Method | Auth | Description |
|---|---|---|---|
| /mypage/subscriptions | GET | Yes | Subscription list |
| /mypage/subscriptions/{id} | GET | Yes | Subscription detail |
| /mypage/subscriptions/{id}/pause | POST | Yes | Pause subscription if supported |
| /mypage/subscriptions/{id}/resume | POST | Yes | Resume subscription if supported |
| /mypage/subscriptions/{id}/cancel | POST | Yes | Cancel subscription if supported |
| /mypage/subscriptions/{id}/payment | GET/POST | Yes | Change payment method if supported |
| /mypage/subscriptions/{id}/shipping | GET/POST | Yes | Change shipping address if supported |
| /products/subscription | GET | No | Subscription product listing |
| /login?redirect_url=/mypage/subscriptions | GET | No | Redirect when session expired |

Note:
- Để đồng nhất tài liệu My Page, dùng route plural `/mypage/subscriptions`.
- Nếu project hiện dùng `/mypage/subscription`, có thể tạo alias route.
- Tất cả detail/update API phải check ownership: subscription.customer_id == current_customer.id.

6. 画面状態 / Screen States

6.1 Initial State
Condition:
- Login success
- API success
- Có subscription

UI:
- Active tab selected
- Subscription card list
- Detail button
- New subscription button
- Notice block

6.2 Loading State
- Skeleton loading card
- Disable tab switching
- Disable detail button
- Loading state for pagination

6.3 Empty State
Condition:
- Không có subscription ở tab hiện tại

UI:
現在ご利用中の定期コースはありません。

CTA:
新しい定期コースを申し込む

6.4 Error States

| Case | Condition | UI | Action |
|---|---|---|---|
| API Error | API 500/timeout | 定期購入情報を取得できませんでした。 | Retry |
| Unauthorized | 401/session expired | Redirect login | /login?redirect_url=/mypage/subscriptions |
| Ownership Error | Access other customer's subscription | 404/access denied | No detail |
| Payment expired | Card/payment invalid | Warning badge | Navigate payment methods |
| Product deleted | Product unavailable | Show snapshot info | Detail allowed |
| Address removed | Address invalid | Warning badge | Navigate shipping edit |

7. UIレイアウト / Layout

7.1 画面イメージ / Layout Image
- Desktop: My Page sidebar + subscription list
- Mobile: vertical layout, menu collapsed

7.2 初期状態のレイアウト / Initial Layout

| No. | Component | Description | Action |
|---|---|---|---|
| 1 | Header | Site global header | Navigation |
| 2 | Side Menu | My Page navigation | Navigate |
| 3 | Page Title | 定期購入管理 | Static |
| 4 | Tab Navigation | 利用中 / 解約済み | Switch list |
| 5 | Subscription Card | Subscription summary | Display info |
| 6 | Product Image | Product thumbnail | Static |
| 7 | Status Badge | 継続中 | Status display |
| 8 | Payment Warning | Card expired/payment invalid | Navigate payment |
| 9 | Address Warning | Address missing/invalid | Navigate shipping |
| 10 | Detail Button | 詳細を見る | Open detail |
| 11 | New Subscription CTA | 新しい定期コースを申し込む | Navigate |
| 12 | Notice Block | Subscription guide | Static |
| 13 | FAQ Button | よくある質問を見る | Navigate |
| 14 | Footer | Site footer | Navigation |

7.3 レイアウト構造 / Layout Structure

Screen
├─ Header
├─ Main Container
│ ├─ Side Navigation
│ └─ Content Area
│ ├─ Title
│ ├─ Tabs
│ ├─ Subscription List
│ │ └─ Subscription Card
│ ├─ Pagination
│ ├─ CTA Button
│ └─ Notice Block
└─ Footer

7.4 Responsive / Safe Area

| Item | Value |
|---|---|
| Responsive | Yes |
| Desktop | Sidebar layout |
| Mobile | Vertical layout |
| Scroll | Yes |
| Safe area | Yes |
| Mobile menu collapse | Yes |

8. UIコンポーネント詳細 / Component Detail

## Component: Side Menu

| element | description | type |
|---|---|---|
| 会員情報 | Member info | menu |
| お届け先情報 | Shipping address | menu |
| 注文履歴 | Order history | menu |
| 定期購入管理 | Current page | active menu |
| クーポン一覧 | Coupon list | menu |
| ポイント履歴 | Point history | menu |
| 決済方法 | Payment method | menu |
| ログアウト | Logout | menu |

## Component: Subscription Tabs

| element | description | type |
|---|---|---|
| 利用中の定期コース | Active subscriptions | tab |
| 一時停止中の定期コース | Paused subscriptions | tab optional |
| 解約済みの定期コース | Cancelled subscriptions | tab |

## Component: Subscription Card

| element | description | type |
|---|---|---|
| product_image | Product image | image |
| product_name | Product name | text |
| status_badge | 継続中 / 一時停止中 / 解約済み | badge |
| delivery_cycle | お届けサイクル | text |
| next_delivery_date | 次回お届け予定日 | date |
| next_payment_date | 次回お支払い予定日 | date |
| order_price | 注文金額 | currency |
| quantity | Quantity | text |
| shipping_address | お届け先 | text |
| payment_method | 支払い方法 | text |
| payment_warning | Payment warning | badge |
| address_warning | Address warning | badge |
| detail_button | 詳細を見る | button |

## Component: Notice Block

| element | description | type |
|---|---|---|
| title | 定期購入について | title |
| guide_list | Guide texts | text |
| faq_button | FAQ button | button |

9. ビジネスロジック / Business Logic

Tab Switching:
| Tab | Data |
|---|---|
| 利用中 | active subscriptions |
| 一時停止中 | paused subscriptions |
| 解約済み | cancelled subscriptions |

Status Display:
| Status | Label |
|---|---|
| active | 継続中 |
| paused | 一時停止中 |
| cancelled | 解約済み |
| payment_failed | 決済エラー |
| pending_cancel | 解約予定 |

Detail Button:
Click 詳細を見る
↓
Open subscription detail screen

New Subscription CTA:
Navigate to subscription product listing page /products/subscription

Ownership:
customer only sees own subscriptions

if user not logged in:
redirect /login?redirect_url=/mypage/subscriptions

if subscription belongs to another customer:
return 404 or access denied

if product deleted:
show subscription snapshot info

if payment method expired:
show warning and link /mypage/payment

if shipping address invalid:
show warning and link shipping edit screen

if next_payment_date < today and status active:
show payment attention badge

if active subscription count == 0:
show empty state in active tab

10. 権限 / Authorization

| item | value |
|---|---|
| Login required | Yes |
| Guest access | No |
| Customer role | Yes |
| Admin role | No |
| Ownership check | Required |
| CSRF required | Yes for update actions |

11. エッジケース / Edge Cases

| Case | Handling |
|---|---|
| No active subscription | Show empty state |
| No cancelled subscription | Show empty state for cancelled tab |
| Product deleted | Show snapshot info |
| Address removed | Show warning |
| Payment method expired | Warning badge |
| Payment method deleted | Warning badge + link payment methods |
| API timeout | Retry UI |
| Multiple subscriptions | Scroll list + pagination |
| Very long address | Multi-line wrap |
| Long product name | Wrap/truncate |
| Missing image | Placeholder image |
| Mobile small width | Responsive collapse |
| Batch date calculation missing | Show fallback '-' |
| Subscription belongs to another customer | 404/access denied |

12. テスト観点 / Test Cases

Happy Path
| ID | Case | Expected |
|---|---|---|
| TC-001 | Open page | Show subscription list |
| TC-002 | Switch tab | Correct list displayed |
| TC-003 | Click detail | Open detail screen |
| TC-004 | Click CTA | Navigate /products/subscription |
| TC-005 | Responsive mobile | Layout correct |
| TC-006 | Payment warning click | Navigate payment methods |

Error
| ID | Case | Expected |
|---|---|---|
| TC-101 | API 500 | Error UI |
| TC-102 | Unauthorized | Redirect login |
| TC-103 | No subscription | Empty state |
| TC-104 | Other customer's subscription | 404/access denied |

Edge
| ID | Case | Expected |
|---|---|---|
| TC-201 | Long product name | Wrap properly |
| TC-202 | Many subscriptions | Scroll/pagination properly |
| TC-203 | Missing image | Placeholder image |
| TC-204 | Expired payment card | Warning display |
| TC-205 | Product deleted | Snapshot info displayed |
| TC-206 | Address invalid | Warning display |

13. EC-CUBE 実装メモ

Controller:
- MypageSubscriptionController

Methods:
- index()
- detail($id)
- pause($id) optional
- resume($id) optional
- cancel($id) optional
- updatePayment($id) optional
- updateShipping($id) optional

Twig:
- app/template/default/Mypage/subscriptions.twig
- app/template/default/Mypage/subscription_detail.twig

Entity:
- CustomerSubscription
- CustomerSubscriptionItem
- CustomerSubscriptionShipping
- CustomerSubscriptionPayment
- CustomerSubscriptionHistory

Repository:
- CustomerSubscriptionRepository

Route Example:
@Route("/mypage/subscriptions", name="mypage_subscriptions")
@Route("/mypage/subscriptions/{id}", name="mypage_subscription_detail")

Implementation Notes:
- Ưu tiên dùng subscription plugin hiện có nếu project đã cài.
- Không sửa core table trực tiếp.
- Custom table phải tạo bằng migration/plugin/entity extension.
- Đồng nhất route với các tài liệu My Page: dùng plural /mypage/subscriptions.
- Nếu project hiện dùng /mypage/subscription thì tạo alias.
- Detail/update phải check ownership theo current customer.
- Payment method warning nên liên kết với /mypage/payment.
- Subscription order type nên đồng nhất với Order History: normal / subscription.
- Batch tạo order định kỳ nên log vào subscription history/batch log.

Generated at: 2026-05-10 17:30:28
