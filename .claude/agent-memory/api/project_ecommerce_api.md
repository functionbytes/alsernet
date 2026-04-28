---
name: Ecommerce API conventions and endpoint map
description: Ecommerce module API structure, controllers, auth guards, session use, and key implementation notes
type: project
---

## Route prefix and naming
- All routes: `prefix('v1/ecommerce')`, `name('api.ecommerce.')`
- Base URL: `api/v1/ecommerce/...`

## Auth guard
- Customers use `auth:sanctum` with the `Customer` model (`ecommerce_customers` table, `HasApiTokens`)
- Retrieve authenticated customer with `$request->user('sanctum')`

## Controllers (Api/)
- `ProductApiController` — product listing, show, categories, brands
- `CartApiController` — guest+auth cart (inline validation, no Form Requests)
- `ReviewApiController` — public index, auth store
- `OrderApiController` — auth CRUD
- `CustomerApiController` — auth profile
- `WishlistApiController` — auth CRUD
- `AddressApiController` — auth CRUD
- `CouponApiController` — apply (POST), remove (DELETE); uses `Discount::calculateDiscount()` and `isExpired()`
- `CompareApiController` — session-based, max 4 products; session key `ecommerce_compare_ids`
- `ProductFilterApiController` — price range + brands + categories with product count
- `TaxApiController` — calculates tax from `ecommerce_taxes` where `status='active'`, ordered by `priority`
- `DigitalDownloadController` — auth:sanctum; verifies `order.customer_id`, `product_type='digital'`, `payment_status='paid'`; increments `times_downloaded`; serves via `Storage::download()` or returns URL
- `CountryApiController` — hardcoded LATAM + Spain list, no auth

## Key model notes
- `Discount`: has `calculateDiscount(float)` and `isExpired()` methods; enum `DiscountType` (fixed/percentage/free_shipping)
- `Tax`: `status` is varchar (use `'active'`); `percentage` is float; ordered by `priority`
- `ProductFile`: `file` column is storage path; `url` is external URL fallback
- `OrderItem`: `times_downloaded` integer, increment with `->increment()`
- `Order`: `payment_status` is varchar (check for `'paid'`)

## Throttle pattern
- Public endpoints with side effects (coupons, compare, filters, taxes): `middleware('throttle:60,1')` inside the outer prefix group
- Countries: no throttle (read-only static data)
- Auth endpoints: no extra throttle (sanctum handles it)
