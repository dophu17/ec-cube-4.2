# GMO Direct Payment (Customize)

This customization adds a direct GMO payment method class:

- `Customize\Service\Payment\Method\GmoDirectCreditCard`

## 1) Configure environment variables

Set these in `.env` or `.env.local`.

```dotenv
GMO_API_BASE_URL=https://pt01.mul-pay.jp/payment
GMO_MOCK_MODE=1
GMO_SHOP_ID=your_shop_id
GMO_SHOP_PASS=your_shop_pass
GMO_SITE_ID=your_site_id
GMO_SITE_PASS=your_site_pass
GMO_JOB_CD=CAPTURE
GMO_TEST_CARD_NO=4111111111111111
GMO_TEST_CARD_EXPIRE=2912
GMO_TEST_CARD_SECURITY_CODE=123
```

`GMO_MOCK_MODE=1` lets you test checkout flow on localhost without calling GMO.
Set `GMO_MOCK_MODE=0` to call GMO test API with your test card values.

## 2) Set payment method class in DB

Create/update a payment row and set `method_class`:

```sql
UPDATE dtb_payment
SET method_class = 'Customize\\Service\\Payment\\Method\\GmoDirectCreditCard'
WHERE method = 'GMO Direct';
```

If no row exists, create a payment method named `GMO Direct` in admin first.

## 3) Clear cache

```bash
bin/console cache:clear --no-warmup
```

## Notes

- This implementation is for localhost integration testing.
- It uses server-side test card values from env. Do not use this approach in production.
- For production, implement tokenization/3DS flow and never pass raw card data through your server.
