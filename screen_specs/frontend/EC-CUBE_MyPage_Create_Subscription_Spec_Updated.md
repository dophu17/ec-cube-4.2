EC-CUBE My Page Create Subscription Spec (Updated)

> Lean spec version for AI coding (kept sections 3 and 7 as requested).

1. 概要 / Overview

Mục đích:
Màn hình cho phép customer tạo mới một 定期購入 (subscription course).

User có thể:
- Chọn sản phẩm muốn đăng ký recurring
- Thiết lập chu kỳ giao hàng
- Thiết lập ngày giao hàng
- Chọn phương thức thanh toán
- Xác nhận thông tin trước khi tạo subscription

Vai trò trong hệ thống:
My Page → Subscription Management → Create New Subscription → Subscription Confirmation → Subscription Complete

User:
- Logged-in customer
- Guest: No
- Admin: Out of scope

2. スコープ / Scope

✅ In Scope
- Step wizard UI
- Product selection
- Selected product summary
- Delivery cycle setup
- Delivery date setup
- Payment method selection
- Confirmation step
- Create subscription action
- Responsive desktop/mobile
- Ownership/login check
- CSRF validation
- Product stock/payment validation

❌ Out of Scope
- Guest subscription
- One-time purchase checkout
- Coupon campaign
- Admin recurring setup
- Auto recommendation engine
- Payment method registration detail
- Actual recurring batch execution

3. 画面 / 機能構成

Create Subscription Screen
├─ Header
├─ Breadcrumb
├─ Side Menu
├─ Step Wizard
│ ├─ Step 1 Product Selection
│ ├─ Step 2 Delivery Setup
│ ├─ Step 3 Payment Setup
│ └─ Step 4 Confirmation
├─ Product List
├─ Selected Product Panel
├─ Delivery Setup Form
├─ Payment Method Form
├─ Confirmation Summary
├─ Add Product CTA
├─ Next / Previous Button
├─ Notice Block
└─ Footer

4. パラメータ / Parameters

| name | type | required | default | description |
|---|---|---|---|---|
| product_ids | array | no | [] | Selected products |
| product_class_ids | array | no | [] | Selected product classes/SKUs |
| quantities | object/array | no | {} | Quantity by product_class_id |
| delivery_cycle_type | string | no | month | day / week / month |
| delivery_cycle_value | integer | no | 1 | Cycle value |
| delivery_date | date | no | null | Preferred delivery date |
| payment_method_id | integer | no | null | Selected customer payment method |
| shipping_address_id | integer | no | default | Selected customer address |
| step | integer | no | 1 | Wizard step |
| csrf_token | string | yes | - | CSRF token |
| return_url | string | no | /mypage/subscriptions | Back URL |

5. データソース / Data Source

## 5.1 EC-CUBE Standard Tables

| Table | Description | Usage |
|---|---|---|
| dtb_customer | Customer master | Login/ownership/customer data |
| dtb_customer_address | Customer address | Select shipping address |
| dtb_product | Product master | Subscription product list |
| dtb_product_class | Product SKU/class | Price, stock, product code |
| dtb_product_stock | Product stock | Stock validation |
| dtb_product_image | Product image | Product thumbnail |
| dtb_category | Category | Subscription product category/filter |
| dtb_product_category | Product-category mapping | Filter subscription products |
| dtb_payment | Payment method | Payment method label/settings |
| dtb_delivery | Delivery method | Delivery/cycle relation if used |
| dtb_shipping | Shipping | Generated order/shipping snapshot after create |
| dtb_order | Order | First/generated order if create flow generates order |
| dtb_order_item | Order items | First/generated order items |
| mtb_pref | Prefecture master | Shipping address display |
| mtb_order_status | Order status | Generated order status |

Note:
- EC-CUBE core thường không có subscription table chuẩn.
- Nếu project dùng subscription plugin, ưu tiên dùng entity/table của plugin.
- Không sửa trực tiếp core table.

## 5.2 Existing Plugin Tables To Check

| Table / Plugin | Note |
|---|---|
| Subscription plugin tables | Ưu tiên dùng nếu đã có: subscription contract, item, cycle, status |
| GMO payment plugin tables | Saved card/payment reference |
| PayPay payment plugin tables | PayPay linked account/payment reference |
| Customer payment method tables | Đồng nhất với Payment Methods spec |
| Shipping/delivery plugin tables | Nếu có delivery cycle/schedule riêng |
| Subscription batch tables/log | Nếu plugin có batch recurring order log |

## 5.3 Custom Tables - đề xuất nếu chưa có

### dtb_customer_subscription

Dùng đồng nhất với Subscription Management/Detail spec.

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
| first_order_id | int | no | First generated order |
| last_order_id | int | no | Last generated order |
| create_date | datetime | yes | EC-CUBE convention |
| update_date | datetime | yes | EC-CUBE convention |

### dtb_customer_subscription_item

Dùng cho selected products trong subscription.

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

Đồng nhất với Payment Methods/Subscription Detail spec.

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

Audit/history cho create/change subscription.

| column | type | required | note |
|---|---|---|---|
| id | int | yes | PK |
| subscription_id | int | yes | FK → dtb_customer_subscription.id |
| customer_id | int | yes | FK → dtb_customer.id |
| event | varchar | yes | created / product_added / payment_set / shipping_set |
| before_data | json/text | no | Usually null on create |
| after_data | json/text | no | Sanitized created values |
| create_date | datetime | yes | EC-CUBE convention |

### dtb_subscription_product_config

Dùng nếu cần đánh dấu/config sản phẩm nào được bán theo subscription.

| column | type | required | note |
|---|---|---|---|
| id | int | yes | PK |
| product_id | int | yes | FK → dtb_product.id |
| product_class_id | int | no | FK → dtb_product_class.id |
| subscription_enabled | boolean | yes | Can be selected |
| allowed_cycle_values | varchar/json | no | 30/60/90 or JSON |
| discount_rate | decimal | no | Default discount |
| min_quantity | int | no | Minimum quantity |
| max_quantity | int | no | Maximum quantity |
| sort_no | int | no | Display order |
| create_date | datetime | yes | EC-CUBE convention |
| update_date | datetime | yes | EC-CUBE convention |

## 5.4 API / Routes

| API / Route | Method | Auth | Description |
|---|---|---|---|
| /mypage/subscriptions/create | GET | Yes | Create subscription wizard |
| /products/subscription | GET | No | Subscription product listing |
| /mypage/subscriptions/create/products | GET | Yes | Product list for wizard, optional async |
| /mypage/payment | GET | Yes | Payment methods screen |
| /mypage/payment/cards | GET | Yes | Saved credit cards |
| /mypage/subscriptions/create/confirm | POST | Yes | Confirm subscription input |
| /mypage/subscriptions | POST | Yes | Create subscription |
| /mypage/subscriptions/create/complete | GET | Yes | Create subscription complete page |
| /mypage/subscriptions | GET | Yes | Back to subscription list |
| /login?redirect_url=/mypage/subscriptions/create | GET | No | Redirect when session expired |

Note:
- Để đồng nhất với các tài liệu Subscription, dùng route plural `/mypage/subscriptions/create`.
- Nếu project đang dùng `/mypage/subscription/create`, tạo alias route.
- Tất cả write APIs cần CSRF validation.
- Create action phải validate login customer, product stock/status, payment method ownership, shipping address ownership.

## 5.5 Sample Response - Subscription Products

{
"products": [
{
"id": 11,
"product_class_id": 101,
"name": "おやすみサプリ（30日分）",
"price": 3980,
"stock_status": "in_stock",
"thumbnail": "/img/sample.jpg",
"subscription_enabled": true,
"allowed_cycles": [30, 60, 90],
"discount_rate": 10
}
]
}

6. 画面状態 / Screen States

6.1 Initial State
Condition:
- Login success
- Product API success
- Payment/address data loaded

UI:
- Step 1 active
- Product list
- Empty selected panel
- Disabled next button
- Side menu
- Notice block

6.2 Loading State
- Skeleton product rows
- Disable step actions
- Disable next button
- Loading payment/address options
- Loading overlay during final create

6.3 Empty State
Condition:
- Không có product recurring

UI:
現在申し込み可能な定期商品はありません。

6.4 Error States

| Case | Condition | UI | Action |
|---|---|---|---|
| API Error | Product/payment API fail | 商品情報を取得できませんでした。 | Retry |
| Unauthorized | 401/session expired | Redirect login | /login?redirect_url=/mypage/subscriptions/create |
| Product Out Of Stock | stock unavailable | 在庫切れ | Disable selection |
| Product Disabled | product not public | Hide or disabled | Reload |
| Payment Method Missing | no valid payment | Payment warning | Go /mypage/payment |
| Shipping Address Missing | no address | Address warning | Go address management |
| CSRF Invalid | token invalid | Session error | Reload |
| Double Submit | repeated create | Button disabled | Prevent duplicate |

7. UIレイアウト / Layout

7.1 画面イメージ / Layout Image
- Desktop: side menu + wizard content
- Mobile: single column, sticky wizard optional

7.2 初期状態のレイアウト / Initial Layout

| No. | Component | Description | Action |
|---|---|---|---|
| 1 | Breadcrumb | Current hierarchy | Navigation |
| 2 | Side Menu | My Page menu | Navigate |
| 3 | Page Title | 新しい定期コースを作成 | Static |
| 4 | Step Wizard | Step progress | Display |
| 5 | Product List | Available products | Select |
| 6 | Product Checkbox | Product selection | Toggle |
| 7 | Stock Status | 在庫あり / 在庫切れ | Display |
| 8 | Selected Product Panel | Selected products | Summary |
| 9 | Delivery Setup | Cycle/date form | Input |
| 10 | Payment Setup | Payment method form | Select |
| 11 | Confirmation Summary | Final review | Display |
| 12 | Add Product CTA | 他の商品を追加する | Expand/list |
| 13 | Next Button | 次へ進む | Next step |
| 14 | Previous Button | 戻る | Previous step |
| 15 | Notice Block | Subscription guide | Static |
| 16 | Footer | Footer | Navigation |

7.3 レイアウト構造 / Layout Structure

Screen
├─ Header
├─ Breadcrumb
├─ Main Container
│ ├─ Side Menu
│ └─ Content
│ ├─ Step Wizard
│ ├─ Product Selection Area
│ │ ├─ Product List
│ │ └─ Selected Product Panel
│ ├─ Delivery Setup Area
│ ├─ Payment Setup Area
│ ├─ Confirmation Area
│ ├─ CTA Area
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
| Sticky step wizard | Optional |
| CTA Mobile | Full width / sticky bottom optional |

8. UIコンポーネント詳細 / Component Detail

## Component: Step Wizard

| element | description | type |
|---|---|---|
| step_1 | 商品を選ぶ | step |
| step_2 | お届けサイクル・日付の設定 | step |
| step_3 | お支払い方法の設定 | step |
| step_4 | 内容確認 | step |

## Component: Product List

| element | description | type |
|---|---|---|
| product_image | Product thumbnail | image |
| product_name | Product name | text |
| product_price | Price | currency |
| discount_badge | 定期割引 | badge |
| stock_status | 在庫あり / 在庫切れ | badge/text |
| select_checkbox | Product select | checkbox |
| quantity | Quantity | stepper |

## Component: Selected Product Panel

| element | description | type |
|---|---|---|
| selected_products | Selected items | list |
| selected_total | Current subtotal | currency |
| empty_icon | Cart illustration | image |
| empty_message | No product selected | text |

## Component: Delivery Setup

| element | description | type |
|---|---|---|
| delivery_cycle | 30日 / 60日 / 90日 | radio/select |
| delivery_date | Preferred delivery date | date |
| next_payment_date | Calculated payment date | readonly text |

## Component: Payment Setup

| element | description | type |
|---|---|---|
| payment_method | Saved payment method | radio/select |
| add_payment_link | 決済方法を追加する | link/button |
| payment_warning | Expired/missing payment | warning |

## Component: Action Area

| element | description | type |
|---|---|---|
| add_product_button | 他の商品を追加する | secondary button |
| previous_button | 戻る | secondary button |
| next_button | 次へ進む | primary button |
| create_button | 定期コースを作成する | primary button |

9. ビジネスロジック / Business Logic

Product Selection:
Checkbox ON
↓
Validate product subscription_enabled and stock
↓
Add product to selected panel
↓
Enable next button

Product Deselection:
Checkbox OFF
↓
Remove product from selected panel
if selected_products is empty:
Disable next button

Quantity:
Minimum quantity = config min_quantity or 1
Maximum quantity = stock / max_quantity / sale_limit

Step Navigation:
Step 1 complete
↓
Go Step 2
↓
Validate delivery setup
↓
Go Step 3
↓
Validate payment method ownership/status
↓
Go Step 4
↓
Confirm and create subscription

Product Availability:
| status | behavior |
|---|---|
| 在庫あり | selectable |
| 在庫切れ | disabled |
| Product disabled | hide or disabled |
| subscription_enabled false | hide |

Delivery Setup:
Allowed cycles should come from config/plugin.
If delivery_date is before allowed start date:
reject
If delivery_date conflicts with cutoff/deadline:
reject

Payment Setup:
if no valid payment method:
show warning and link /mypage/payment
if selected payment belongs to another customer:
reject
if payment expired/invalid:
reject

Create Subscription:
Confirm subscription
↓
Validate csrf_token
↓
Validate customer/payment/address/products again
↓
Create subscription record
↓
Create subscription items
↓
Create subscription shipping/payment records
↓
Optionally create first order
↓
Write subscription history
↓
Send notification mail if required
↓
Redirect complete page

Duplicate Prevention:
if create request is already processing:
disable submit
use transaction/lock where possible

10. 権限 / Authorization

| item | value |
|---|---|
| Login required | Yes |
| Guest access | No |
| Customer only | Yes |
| Admin access | No |
| CSRF required | Yes for write actions |
| Ownership validation | Payment/address must belong to login customer |

11. エッジケース / Edge Cases

| Case | Handling |
|---|---|
| No product selected | Disable next |
| Product out of stock | Disable select |
| Product deleted during flow | Validation error |
| Product disabled during flow | Validation error |
| Quantity > stock | Validation error |
| Payment method unavailable | Prevent next |
| Payment method deleted during flow | Validation error |
| Shipping address deleted during flow | Validation error |
| API timeout | Retry |
| Double click next | Debounce |
| Double click create | Prevent duplicate |
| Large product list | Scroll/pagination |
| Mobile small width | Vertical collapse |
| Browser back after complete | Do not duplicate subscription |

12. テスト観点 / Test Cases

Happy Path
| ID | Case | Expected |
|---|---|---|
| TC-001 | Open page | Product list displayed |
| TC-002 | Select product | Product added |
| TC-003 | Change quantity | Selected summary updated |
| TC-004 | Click next | Move next step |
| TC-005 | Set delivery cycle/date | Validation OK |
| TC-006 | Select payment method | Validation OK |
| TC-007 | Complete all steps | Subscription created |
| TC-008 | Mobile responsive | Layout OK |

Error
| ID | Case | Expected |
|---|---|---|
| TC-101 | API fail | Error UI |
| TC-102 | Unauthorized | Redirect login |
| TC-103 | No stock | Cannot select |
| TC-104 | No product selected | Next disabled |
| TC-105 | Invalid delivery date | Error message |
| TC-106 | Invalid payment | Cannot proceed |
| TC-107 | CSRF invalid | Error/reload |

Edge
| ID | Case | Expected |
|---|---|---|
| TC-201 | Double click next | Single transition |
| TC-202 | Double click create | Single subscription |
| TC-203 | Product removed during flow | Validation error |
| TC-204 | Large product list | Scroll correctly |
| TC-205 | Mobile small screen | Responsive layout |
| TC-206 | Browser back after complete | No duplicate create |

13. EC-CUBE 実装メモ

Controller:
- MypageSubscriptionController

Methods:
- create()
- createProducts()
- confirmCreate()
- completeCreate()
- store()

Twig:
- app/template/default/Mypage/subscription_create.twig
- app/template/default/Mypage/subscription_create_confirm.twig
- app/template/default/Mypage/subscription_create_complete.twig

Entity:
- CustomerSubscription
- CustomerSubscriptionItem
- CustomerSubscriptionShipping
- CustomerSubscriptionPayment
- CustomerSubscriptionHistory
- SubscriptionProductConfig

FormType:
- SubscriptionProductSelectType
- SubscriptionDeliverySettingType
- SubscriptionPaymentType
- SubscriptionConfirmType

Route Example:
@Route("/mypage/subscriptions/create", name="mypage_subscription_create")
@Route("/mypage/subscriptions/create/confirm", name="mypage_subscription_create_confirm")
@Route("/mypage/subscriptions/create/complete", name="mypage_subscription_create_complete")
@Route("/mypage/subscriptions", name="mypage_subscriptions_store", methods={"POST"})

Implementation Notes:
- Ưu tiên dùng subscription plugin hiện có nếu project đã cài.
- Không sửa core table trực tiếp.
- Custom table phải tạo bằng migration/plugin/entity extension.
- Đồng nhất route với Subscription Management/Detail: `/mypage/subscriptions`.
- Nếu project hiện dùng `/mypage/subscription/create` thì tạo alias.
- Tất cả write actions cần CSRF validation.
- Payment method phải thuộc current customer.
- Shipping address phải thuộc current customer.
- Product/status/stock phải validate lại ở confirm/create.
- Create subscription nên chạy trong transaction để tránh tạo thiếu item/payment/shipping.

Generated at: 2026-05-10 17:36:19
