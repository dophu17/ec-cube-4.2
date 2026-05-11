EC-CUBE Checkout Screen Spec (Updated)

> Lean spec version for AI coding (kept sections 3 and 7 as requested).

対象: EC-CUBE checkout page / shopping flow
目的: Notion update-ready specification with EC-CUBE API/table mapping, GMO/PayPay payment flow, and custom table proposals.

1. 概要 / Overview

Mục đích

Hiển thị thông tin khách hàng & địa chỉ giao hàng (read-only)

Cho phép chọn phương thức thanh toán

Nhập thông tin thẻ nếu chọn credit card

Xác nhận thông tin đơn hàng trước khi chuyển sang bước Confirm

Vai trò trong hệ thống
Cart -> Checkout -> Confirm -> Complete

User

Guest

Logged-in customer

2. スコープ / Scope

In Scope

Progress step checkout

Hiển thị customer info (read-only)

Hiển thị shipping address (read-only)

Chọn payment method

Nhập credit card (GMO token)

Hiển thị order summary

CTA chuyển sang confirm

Trust & security badges

Out of Scope

Edit trực tiếp customer info (chỉ redirect)

Edit address inline

Order completion

Subscription management detail

3. 画面 / 機能構成
Component
Description
Progress Step
1 カート -> 2 ご注文手続き -> 3 確認 -> 4 完了
Customer Info
Thông tin khách hàng
Shipping Address
Địa chỉ giao hàng
Payment Method
Chọn phương thức thanh toán
Credit Card Form
Form nhập thẻ
Order Summary
Tóm tắt đơn hàng
CTA Button
注文内容を確認する
Trust & Security
SSL / DigiCert / TRUSTe

4. パラメータ / Parameters
name
type
required
default
description
cart_key
string
yes
-
Cart session key
customer_id
int
no
null
Logged-in customer id
order_id
int
no
null
Temporary order id if already created
shipping_id
int
no
default
Shipping address id
payment_method
string
yes
credit_card
credit_card / paypay / bank_transfer
payment_id
int
yes
-
EC-CUBE payment id
gmo_token
string
no
null
GMO card token, credit card only
paypay_token
string
no
null
PayPay token/order reference if needed
use_point
int
no
0
Point usage, if project supports point
csrf_token
string
yes
-
CSRF token for checkout form
redirect_url
string
no
/shopping/confirm
Next destination

5. データソース / Data Source

5.1 EC-CUBE Standard Tables
Table
description
usage
dtb_customer
Customer info
Member customer name/email/phone
dtb_customer_address
Customer saved addresses
Address book if member changes shipping
dtb_cart
Cart session
Cart data before checkout
dtb_cart_item
Cart items
Items selected for order
dtb_product
Product master
Product name/status
dtb_product_class
Product class/SKU
Price, stock, sale limit
dtb_product_stock
Product stock
Stock validation
dtb_order
Order
Pre-order/order data during shopping flow
dtb_order_item
Order items
Order line items
dtb_shipping
Shipping
Shipping address, shipping date, delivery info
dtb_payment
Payment method
Available payment methods
dtb_delivery
Delivery method
Delivery/shipping method
dtb_delivery_fee
Delivery fee
Shipping fee calculation by prefecture/delivery
mtb_pref
Prefecture master
Address prefecture
mtb_order_status
Order status
Order status lifecycle
mtb_order_item_type
Order item type
Product/shipping/discount/tax line types
dtb_base_info
Shop base config
Tax/shipping/free shipping config if used

5.2 Plugin / Existing Tables To Check
Table / Plugin
note
GMO payment plugin tables
Use existing GMO plugin entity/table for transaction id, access id/pass, token result, payment status if plugin provides it.
PayPay payment plugin tables
Use existing PayPay plugin entity/table for payment session/order reference if plugin provides it.
Point plugin/table
If project supports point usage, use existing point plugin or EC-CUBE point customization.
Subscription plugin tables
If checkout supports subscription orders, use plugin-specific subscription order/config tables, not a new core modification.

5.3 Custom Tables - đề xuất nếu chưa có

dtb_checkout_payment_token
column
type
required
note
id
int
yes
PK
order_id
int
yes
FK -> dtb_order.id
payment_method
varchar
yes
credit_card / paypay
provider
varchar
yes
gmo / paypay
token_reference
varchar
yes
Token/reference only. Do not store raw card data.
transaction_id
varchar
no
Provider transaction id
status
varchar
yes
created / tokenized / failed / expired
error_code
varchar
no
Provider error code
error_message
text
no
Sanitized provider error
expired_at
datetime
no
Token expiration
create_date
datetime
yes
EC-CUBE convention
update_date
datetime
yes
EC-CUBE convention

Note: chỉ tạo nếu payment plugin không có table lưu transaction/token reference. Không lưu card_number / security_code.

dtb_checkout_selected_item
column
type
required
note
id
int
yes
PK
cart_key
varchar
yes
Guest/member cart key
cart_item_id
int
yes
FK -> dtb_cart_item.id
is_selected
boolean
yes
Selected from cart multi-select
purchase_type
varchar
yes
normal / subscription
create_date
datetime
yes
EC-CUBE convention
update_date
datetime
yes
EC-CUBE convention

Note: chỉ cần nếu Cart có multi-select và selection cần persist qua Checkout. Nếu chỉ lưu session/frontend state thì không cần table.

dtb_checkout_audit_log
column
type
required
note
id
int
yes
PK
order_id
int
no
FK -> dtb_order.id
cart_key
varchar
no
Cart key
customer_id
int
no
FK -> dtb_customer.id
event
varchar
yes
open_checkout / select_payment / token_fail
metadata
json/text
no
Sanitized metadata
ip_address
varchar
no
Client IP
user_agent
text
no
Browser info
create_date
datetime
yes
EC-CUBE convention

Note: optional. Có thể dùng app log/analytics thay vì DB table.

5.4 API / Routes
API / Route
method
auth
description
/shopping
GET
No/Yes
Display checkout page
/shopping
POST
No/Yes
Submit checkout data, select payment, validate
/shopping/confirm
POST
No/Yes
Move to confirm page after validation/tokenization
/shopping/checkout
POST
No/Yes
Complete order from confirm step
/shopping/customer
GET/POST
No/Yes
Guest customer info input/edit route if required
/shopping/shipping
GET/POST
No/Yes
Shipping address change route if required
/shopping/payment-methods
GET
No/Yes
Return available payment methods if async
/payment/gmo/token
POST
No/Yes
Create GMO card token. Prefer provider JS/token endpoint when possible.
/payment/paypay/start
POST
No/Yes
Create PayPay payment session
/payment/paypay/callback
GET/POST
No
PayPay callback/return
/cart
GET
No
Back to cart

5.5 Sample Response - /shopping/payment-methods
{
"methods": [
{
"payment_id": 1,
"code": "credit_card",
"label": "クレジットカード",
"requires_token": true
},
{
"payment_id": 2,
"code": "paypay",
"label": "PayPay",
"requires_redirect": true
},
{
"payment_id": 3,
"code": "bank_transfer",
"label": "銀行振込",
"requires_token": false
}
]
}

6. 画面状態 / Screen States

6.1 Initial State

Cart tồn tại và có item hợp lệ

Step 2 active

Customer info read-only

Shipping address read-only

Payment default: credit card

Order summary hiển thị subtotal / shipping / discount / total / point

6.2 Loading State

Load checkout data

Tokenize card

Recalculate order summary

Disable CTA while processing

6.3 Empty State

Cart empty -> redirect /cart

Selected item empty -> redirect /cart or show warning

6.4 Error States
Case
Condition
UI
Retry / Navigation
Payment Error
GMO token fail
Show payment error
Stay checkout
Validation Error
Missing card/payment info
Inline error
Fix input
Cart Empty
No item
Redirect cart
/cart
Stock mismatch
Stock changed
Show item warning
Back cart / reload
Price changed
Price recalculated
Show updated summary
Confirm again
Session expired
cart_key/session invalid
Session error
Reload / cart
Payment method unavailable
Payment disabled/admin setting changed
Show error
Select another

7. UIレイアウト / Layout

7.1 Layout Structure
Checkout Screen
├─ Progress Step
├─ Content
│ ├─ Customer Info + Address
│ ├─ Payment Method
│ │ ├─ Credit Card Form
│ │ ├─ PayPay Option
│ │ └─ Bank Transfer Option
│ └─ Order Summary
└─ CTA

7.2 Initial Layout (ANNOTATION)
No
Component
Description
Action
1
Progress Step
Step indicator
Static
2
Customer Info
Name, email, phone
Read-only
3
Shipping Address
Address info
Read-only
4
Change Button
変更する
Navigate
5
Payment Method
Radio select
Switch
6
Credit Card Form
Card input
Visible if selected
7
Order Summary
Price breakdown
Auto recalculated
8
Trust & Security
SSL / secure payment badges
Static
9
CTA
Confirm order
Validate + submit

7.3 Responsive
Device
Layout
Desktop
3 columns
Tablet
2 columns or stacked payment/summary
Mobile
1 column
CTA
Sticky bottom on mobile

8. UIコンポーネント詳細

Customer Info
element
type
description
name
text
山田 太郎
email
text
sample@email.com
phone
text
090-xxxx

Read-only. Change action navigates to customer edit/input route.

Payment Method
element
type
description
credit_card
radio
GMO credit card tokenization
paypay
radio
PayPay redirect flow
bank_transfer
radio
Offline transfer

Credit Card Form
field
type
note
card_number
text
Tokenize only. Do not store.
expiry
text
MM/YY
card_name
text
Card holder
security_code
password/text
Tokenize only. Do not store.

Order Summary
item
example
note
subtotal
¥13,940
Selected items subtotal
discount
-¥796
Campaign/subscription/point discount
shipping
無料
Delivery fee
payment_fee
¥0
Payment method fee if any
tax
内税
EC-CUBE tax calculation
total
¥13,144
Final payment total
point
131pt
Earned point if enabled

9. ビジネスロジック / Business Logic
default payment = credit_card

on load checkout:
validate cart exists
validate selected items
validate product status / stock / price
initialize temporary order if required

if credit_card:
require card input
call GMO token API
save token reference only
do not store raw card info

if paypay:
create PayPay session at confirm/complete timing according to plugin
redirect when payment execution starts

if bank_transfer:
no online tokenization
show bank transfer note if required

if payment changed:
recalculate payment fee and total

if shipping address changed:
recalculate delivery fee and total

on CTA click:
validate csrf_token
validate payment
validate stock and price again
go confirm screen

if cart empty or selected item empty:
redirect /cart

if price/stock changed:
show warning and require user confirmation again

10. 権限 / Authorization
Item
Value
Login required
No
Guest checkout
Yes
Member checkout
Yes
Credit card tokenization
No login required, but valid session/cart required
Saved card usage
Login required if supported

11. エッジケース / Edge Cases

Spam click CTA

GMO token fail

Cart empty

Selected item empty

Payment not selected

Session expired

Stock changed during checkout

Price changed during checkout

Payment method disabled by admin while user is on screen

PayPay redirect canceled

Browser back after tokenization

Mobile sticky CTA overlaps card form

12. テスト観点 / Test Cases

Happy Path

Credit card success -> confirm

PayPay select -> confirm / payment redirect according to plugin

Bank transfer -> confirm

Guest checkout success

Member checkout success

Error

Card invalid

GMO token fail

No payment selected

CSRF invalid

Stock mismatch

Session expired

Edge

Double click CTA

Network delay

Browser back

Mobile sticky CTA

Price changed after cart

13. EC-CUBE Implementation Notes

Ưu tiên dùng ShoppingController / PurchaseFlow / OrderHelper / CartService theo convention EC-CUBE.

Không sửa trực tiếp core table. Custom table phải tạo qua migration/plugin hoặc entity extension.

Payment logic nên đi qua payment plugin/service, không implement trực tiếp trong Twig.

Card data không được lưu vào DB hoặc log. Chỉ lưu token/reference nếu cần.

Stock/price/payment validation phải chạy lại trước khi vào Confirm và trước khi Complete.

Guest checkout cần giữ cart_key/session ổn định.

Nếu có subscription checkout, tách rule sang subscription plugin/service riêng.
Generated at: 2026-05-10 17:07:41
