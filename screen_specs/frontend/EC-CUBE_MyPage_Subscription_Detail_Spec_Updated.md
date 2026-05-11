EC-CUBE My Page Subscription Detail Spec (Updated)

> Lean spec version for AI coding (kept sections 3 and 7 as requested).

1. 概要 / Overview

Mục đích:
Màn hình hiển thị chi tiết một subscription cụ thể.

User có thể:
- Xem thông tin subscription
- Xem danh sách sản phẩm trong subscription
- Thay đổi chu kỳ giao hàng
- Thay đổi ngày giao hàng
- Thay đổi phương thức thanh toán
- Thêm sản phẩm vào subscription
- Xoá sản phẩm khỏi subscription
- Điều chỉnh số lượng sản phẩm
- Huỷ subscription

Vai trò trong hệ thống:
My Page → Subscription Management → Subscription Detail → Edit / Cancel / Product Update

User:
- Logged-in customer
- Guest: No
- Admin: Out of scope

2. スコープ / Scope

✅ In Scope
- Subscription summary
- Delivery/payment info
- Product list
- Quantity adjustment
- Remove product
- Add product CTA
- Total summary
- Change cycle
- Change delivery date
- Change payment method
- Cancel subscription CTA
- Responsive desktop/mobile
- Ownership check
- CSRF validation for write actions
- Payment/address warning display

❌ Out of Scope
- Checkout flow
- Actual payment execution
- Admin operation
- Product recommendation engine
- Subscription creation flow
- Coupon management
- Payment method registration detail

3. 画面 / 機能構成

Subscription Detail Screen
├─ Header
├─ Breadcrumb
├─ Side Menu
├─ Subscription Summary
├─ Management Action Buttons
│ ├─ Change Delivery Cycle
│ ├─ Change Delivery Date
│ ├─ Change Payment Method
│ └─ Cancel Subscription
├─ Subscription Product List
│ ├─ Product Item
│ ├─ Quantity Controller
│ └─ Remove Product
├─ Add Product CTA
├─ Price Summary
├─ Notice Block
└─ Footer

4. パラメータ / Parameters

| name | type | required | default | description |
|---|---|---|---|---|
| subscription_id | integer | yes | - | Subscription ID |
| customer_id | integer | yes | - | Logged-in customer ID |
| product_id | integer | no | null | Product target |
| product_class_id | integer | no | null | SKU/product class target |
| subscription_item_id | integer | no | null | Subscription item target |
| quantity | integer | no | 1 | Product quantity |
| delivery_cycle_type | string | no | month | day / week / month |
| delivery_cycle_value | integer | no | 1 | Cycle value |
| next_delivery_date | date | no | null | Next delivery date |
| payment_method_id | integer | no | null | Customer payment method |
| csrf_token | string | yes | - | CSRF token for write actions |
| return_url | string | no | /mypage/subscriptions | Back URL |

5. データソース / Data Source

## 5.1 EC-CUBE Standard Tables

| Table | Description | Usage |
|---|---|---|
| dtb_customer | Customer master | Ownership check |
| dtb_customer_address | Customer address | Address source/reference |
| dtb_order | Order master | Last/next generated recurring order reference |
| dtb_order_item | Order item | Snapshot/order item relation |
| dtb_shipping | Shipping | Shipping info snapshot/reference |
| dtb_payment | Payment method | Payment display |
| dtb_delivery | Delivery method | Delivery/cycle reference if used |
| dtb_product | Product master | Product name/status/image reference |
| dtb_product_class | Product SKU/class | Price, stock, product code |
| dtb_product_stock | Product stock | Quantity validation |
| dtb_product_image | Product image | Product thumbnail |
| mtb_pref | Prefecture master | Shipping address display |
| mtb_order_status | Order status | Related generated order status |

Note:
- EC-CUBE core thường không có subscription table chuẩn.
- Nếu project dùng subscription plugin, ưu tiên dùng entity/table của plugin.
- Không sửa trực tiếp core table.

## 5.2 Existing Plugin Tables To Check

| Table / Plugin | Note |
|---|---|
| Subscription plugin tables | Ưu tiên dùng nếu đã có: subscription contract, subscription items, cycle, status |
| GMO payment plugin tables | Card/payment reference display |
| PayPay payment plugin tables | PayPay payment reference display |
| Customer payment method tables | Đồng nhất với Payment Methods spec |
| Shipping/delivery plugin tables | Nếu có schedule/delivery rule riêng |
| Subscription batch tables/log | Nếu batch recurring order đã có log riêng |

## 5.3 Custom Tables - đề xuất nếu chưa có

### dtb_customer_subscription

Dùng đồng nhất với Subscription Management spec.

| column | type | required | note |
|---|---|---|---|
| id | int | yes | PK |
| customer_id | int | yes | FK → dtb_customer.id |
| status | varchar | yes | active / paused / cancelled / payment_failed |
| delivery_cycle_type | varchar | yes | day / week / month |
| delivery_cycle_value | int | yes | e.g. 30 |
| next_delivery_date | date | no | Next delivery date |
| next_payment_date | date | no | Next payment date |
| shipping_id | int | no | FK/snapshot reference |
| payment_method_id | int | no | FK to customer payment method/plugin |
| last_order_id | int | no | Last generated order |
| cancel_reason | text | no | Cancel reason if needed |
| cancelled_at | datetime | no | Cancel timestamp |
| create_date | datetime | yes | EC-CUBE convention |
| update_date | datetime | yes | EC-CUBE convention |

### dtb_customer_subscription_item

Dùng cho danh sách sản phẩm trong subscription.

| column | type | required | note |
|---|---|---|---|
| id | int | yes | PK |
| subscription_id | int | yes | FK → dtb_customer_subscription.id |
| product_id | int | yes | FK → dtb_product.id |
| product_class_id | int | yes | FK → dtb_product_class.id |
| product_name | varchar | yes | Snapshot name |
| product_code | varchar | no | Snapshot code |
| unit_price | int | yes | Snapshot/current subscription unit price |
| quantity | int | yes | Quantity |
| discount_rate | decimal | no | Subscription discount |
| status | varchar | yes | active / removed |
| sort_no | int | no | Display order |
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
| postal_code | varchar | yes | Postal code |
| pref_id | int | yes | FK → mtb_pref.id |
| addr01 | varchar | yes | Address 1 |
| addr02 | varchar | no | Address 2 |
| phone_number | varchar | yes | Phone |
| create_date | datetime | yes | EC-CUBE convention |
| update_date | datetime | yes | EC-CUBE convention |

### dtb_customer_subscription_payment

Đồng nhất với Payment Methods spec. Chỉ tạo nếu subscription/payment plugin chưa có mapping.

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
| event | varchar | yes | cycle_changed / date_changed / product_added / product_removed / quantity_changed / cancelled |
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
| /mypage/subscriptions/{id} | GET | Yes | Subscription detail |
| /mypage/subscriptions/{id}/cycle | POST/PUT | Yes | Change cycle |
| /mypage/subscriptions/{id}/delivery-date | POST/PUT | Yes | Change delivery date |
| /mypage/subscriptions/{id}/payment | GET/POST/PUT | Yes | Change payment method |
| /mypage/subscriptions/{id}/shipping | GET/POST/PUT | Yes | Change shipping address |
| /mypage/subscriptions/{id}/products | POST | Yes | Add product |
| /mypage/subscriptions/{id}/products/{item_id} | DELETE/POST | Yes | Remove product |
| /mypage/subscriptions/{id}/products/{item_id}/quantity | POST/PUT | Yes | Change quantity |
| /mypage/subscriptions/{id}/pause | POST | Yes | Pause subscription if supported |
| /mypage/subscriptions/{id}/resume | POST | Yes | Resume subscription if supported |
| /mypage/subscriptions/{id}/cancel | POST | Yes | Cancel subscription |
| /products/subscription | GET | No | Add/new subscription product list |
| /mypage/payment | GET | Yes | Payment methods screen |
| /login?redirect_url=/mypage/subscriptions/{id} | GET | No | Redirect when session expired |

Note:
- Để đồng nhất với Subscription Management, dùng route plural `/mypage/subscriptions/{id}`.
- Nếu project đang dùng `/mypage/subscription/{id}`, tạo alias route.
- Tất cả API detail/update phải check ownership: subscription.customer_id == current_customer.id.

6. 画面状態 / Screen States

6.1 Initial State
Condition:
- Login success
- API success
- Subscription tồn tại
- Ownership OK

UI:
- Subscription summary
- Product list
- Total summary
- Action buttons
- Warning badges nếu payment/address/product có vấn đề

6.2 Loading State
- Skeleton summary
- Skeleton product rows
- Disable quantity buttons
- Disable action buttons
- Loading overlay for modal submit

6.3 Empty State
Condition:
- Subscription không có sản phẩm

UI:
ご利用中の商品がありません。

CTA:
商品を追加する

6.4 Error States

| Case | Condition | UI | Action |
|---|---|---|---|
| API Error | 500/timeout | 定期購入情報を取得できませんでした。 | Retry |
| Unauthorized | 401/session expired | Redirect login | /login?redirect_url=/mypage/subscriptions/{id} |
| Subscription Not Found | id invalid/deleted | 対象の定期購入が見つかりません。 | Back list |
| Ownership Error | Other customer's subscription | 404/access denied | No detail |
| Payment expired | Card/payment invalid | Warning badge | Go /mypage/payment |
| Product out of stock | Quantity invalid | Disable increase / warning | Adjust quantity |
| Cancelled subscription | status cancelled | Disable edit actions | View only |
| Change deadline passed | Before next delivery cutoff | Disable date/cycle/product edits | Show notice |

7. UIレイアウト / Layout

7.1 画面イメージ / Layout Image
- Desktop: sidebar + detail content
- Mobile: single column, touch optimized quantity controls

7.2 初期状態のレイアウト / Initial Layout

| No. | Component | Description | Action |
|---|---|---|---|
| 1 | Breadcrumb | Navigation hierarchy | Navigate |
| 2 | Side Menu | My Page menu | Navigate |
| 3 | Page Title | 定期購入詳細 | Static |
| 4 | Subscription Summary | Subscription info | Display |
| 5 | Status Badge | 継続中 / 一時停止中 / 解約済み | Status |
| 6 | Warning Badge | Payment/address/product warning | Navigate/fix |
| 7 | Action Buttons | Subscription actions | Open modal/action |
| 8 | Product List | Products in subscription | Display |
| 9 | Quantity Controller | +/- quantity | Update quantity |
| 10 | Remove Button | 削除 | Remove product |
| 11 | Add Product CTA | 商品を追加する | Navigate/modal |
| 12 | Total Summary | Price summary | Display |
| 13 | Notice Block | Subscription guide | Static |
| 14 | FAQ Button | FAQ | Navigate |
| 15 | Footer | Footer | Navigation |

7.3 レイアウト構造 / Layout Structure

Screen
├─ Header
├─ Breadcrumb
├─ Main Container
│ ├─ Side Menu
│ └─ Content
│ ├─ Summary Section
│ ├─ Action Button Section
│ ├─ Product List
│ ├─ Add Product CTA
│ ├─ Total Summary
│ └─ Notice Block
└─ Footer

7.4 Responsive / Safe Area

| Item | Value |
|---|---|
| Responsive | Yes |
| Desktop | Two-column |
| Mobile | Single-column |
| Safe area | Yes |
| Scroll | Yes |
| Quantity controls | Touch optimized |
| Modal mobile | Bottom sheet / full width |

8. UIコンポーネント詳細 / Component Detail

## Component: Subscription Summary

| element | description | type |
|---|---|---|
| subscription_id | Subscription number | text |
| status_badge | 継続中 / 一時停止中 / 解約済み | badge |
| delivery_cycle | お届けサイクル | text |
| next_delivery_date | 次回お届け予定日 | date |
| next_payment_date | 次回お支払い予定日 | date |
| order_price | 注文金額 | currency |
| shipping_address | お届け先 | text |
| payment_method | 支払い方法 | text |
| payment_warning | Payment issue | badge |
| address_warning | Address issue | badge |

## Component: Action Buttons

| element | description | type |
|---|---|---|
| change_cycle | お届けサイクルの変更 | button |
| change_delivery_date | お届け日の変更 | button |
| change_payment_method | お支払い方法の変更 | button |
| change_shipping_address | お届け先の変更 | button |
| pause_subscription | 一時停止する | button optional |
| resume_subscription | 再開する | button optional |
| cancel_subscription | 定期購入を解約する | destructive button |

## Component: Product List

| element | description | type |
|---|---|---|
| product_image | Product thumbnail | image |
| product_name | Product name | text |
| product_code | Product code | text |
| unit_price | Unit price | currency |
| quantity_control | +/- quantity | stepper |
| subtotal | Product subtotal | currency |
| remove_button | 削除 | button |

## Component: Total Summary

| element | description | type |
|---|---|---|
| subtotal | 小計 | currency |
| discount | 定期割引 | currency |
| shipping_fee | 送料 | currency |
| total | 合計（税込） | currency |

9. ビジネスロジック / Business Logic

Quantity Control:
Click +
↓
Validate stock / sale limit / subscription edit deadline
↓
Increase quantity
↓
Recalculate subtotal & total

Click -
↓
Decrease quantity
↓
Minimum quantity = 1

Remove Product:
Click 削除
↓
Open confirm dialog
↓
Validate at least one active item rule
↓
Remove product from subscription

If remove sản phẩm cuối cùng:
- Không tự động huỷ subscription
- Show warning: subscription has no products
- CTA add product or cancel subscription

Add Product:
Open add product flow/modal or navigate /products/subscription

Change Delivery Cycle:
Allowed values should come from config/plugin.
Default examples: 30日 / 60日 / 90日.

Change Delivery Date:
if selected date < change_deadline:
reject

Change Payment:
Navigate/select from /mypage/payment or payment modal.
If current payment expired:
show warning

Cancel Subscription:
Click 定期購入を解約する
↓
Open cancel confirm modal
↓
Validate cancellation deadline
↓
Confirm
↓
Status -> cancelled
↓
Stop future billing/order generation
↓
Write subscription history

Price Calculation:
subtotal = Σ(product unit_price * quantity)
discount = Σ(subscription discount)
total = subtotal - discount + shipping_fee

Edit Restriction:
if subscription status == cancelled:
disable all edit actions

if deadline passed for next delivery:
disable cycle/date/product changes

10. 権限 / Authorization

| item | value |
|---|---|
| Login required | Yes |
| Ownership validation | Required |
| Guest access | No |
| Admin access | No |
| CSRF required | Yes for write actions |

Security rule:
subscription.customer_id must equal login customer_id

11. エッジケース / Edge Cases

| Case | Handling |
|---|---|
| Quantity spam click | Debounce/update lock |
| Product deleted | Snapshot display |
| Product out of stock | Disable increase |
| Product sale limit exceeded | Show validation |
| Expired payment method | Warning + link /mypage/payment |
| Payment method deleted | Warning + require update |
| Shipping address invalid | Warning |
| Shipping fee change | Recalculate total |
| API timeout | Retry UI |
| Remove last product | Show warning, do not auto cancel |
| Cancelled subscription | Disable editing |
| Change deadline passed | Disable edit actions |
| Subscription belongs to another customer | 404/access denied |
| Multiple tabs updating same subscription | Reload latest state |
| Mobile small width | Responsive layout |

12. テスト観点 / Test Cases

Happy Path
| ID | Case | Expected |
|---|---|---|
| TC-001 | Open detail | Data displayed |
| TC-002 | Increase quantity | Total updated |
| TC-003 | Decrease quantity | Total updated |
| TC-004 | Remove product | Product removed |
| TC-005 | Add product | Product added |
| TC-006 | Change cycle | Updated successfully |
| TC-007 | Change delivery date | Updated |
| TC-008 | Change payment method | Updated |
| TC-009 | Cancel subscription | Cancelled |
| TC-010 | Back list | List state preserved |

Error
| ID | Case | Expected |
|---|---|---|
| TC-101 | API fail | Error UI |
| TC-102 | Unauthorized | Redirect login |
| TC-103 | Other customer's subscription | 404/access denied |
| TC-104 | Out-of-stock product | Validation error |
| TC-105 | Invalid quantity | Reject update |
| TC-106 | CSRF invalid | Error/reload |
| TC-107 | Deadline passed | Edit blocked |

Edge
| ID | Case | Expected |
|---|---|---|
| TC-201 | Very long product name | Wrap correctly |
| TC-202 | Multiple products | Scroll correctly |
| TC-203 | Double click quantity | Single update |
| TC-204 | Product removed from catalog | Snapshot shown |
| TC-205 | Expired payment card | Warning shown |
| TC-206 | Remove last product | Warning shown |
| TC-207 | Cancelled subscription | Edit disabled |

13. EC-CUBE 実装メモ

Controller:
- MypageSubscriptionController

Methods:
- detail($id)
- updateCycle($id)
- updateDeliveryDate($id)
- updatePayment($id)
- updateShipping($id)
- addProduct($id)
- removeProduct($id, $itemId)
- updateQuantity($id, $itemId)
- pause($id) optional
- resume($id) optional
- cancel($id)

Twig:
- app/template/default/Mypage/subscription_detail.twig

Entity:
- CustomerSubscription
- CustomerSubscriptionItem
- CustomerSubscriptionShipping
- CustomerSubscriptionPayment
- CustomerSubscriptionHistory

Repository:
- CustomerSubscriptionRepository
- CustomerSubscriptionItemRepository

Route Example:
@Route("/mypage/subscriptions/{id}", name="mypage_subscription_detail")
@Route("/mypage/subscriptions/{id}/products", name="mypage_subscription_products")
@Route("/mypage/subscriptions/{id}/cancel", name="mypage_subscription_cancel")

Implementation Notes:
- Ưu tiên dùng subscription plugin hiện có nếu project đã cài.
- Không sửa core table trực tiếp.
- Custom table phải tạo bằng migration/plugin/entity extension.
- Đồng nhất route với Subscription Management: `/mypage/subscriptions`.
- Nếu project hiện dùng `/mypage/subscription/{id}` thì tạo alias.
- Detail/update phải check ownership theo current customer.
- Tất cả write actions cần CSRF validation.
- Payment method nên liên kết với `/mypage/payment`.
- Subscription item status/purchase_type nên đồng nhất với Order History: normal / subscription.
- Batch tạo order định kỳ nên log vào subscription history/batch log.

Generated at: 2026-05-10 17:33:04
