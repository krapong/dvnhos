# Database

## Core tables

- `roles`
- `users`
- `wards`
- `rooms`
- `beds`
- `patients_cache`
- `admissions`
- `patient_allergies`
- `diet_types`
- `diet_templates`
- `diet_template_items`
- `meal_order_batches`
- `meal_orders`
- `meal_order_items`
- `meal_order_changes`
- `audit_logs`
- `sync_logs`
- `telegram_logs`

## Notes

- `patients_cache` stores HN, AN, name, location, BMI, NPO, diet order, and the latest sync timestamp.
- `meal_orders` is one row per patient per service date.
- `meal_order_items` stores breakfast/lunch/dinner selections separately.
- `meal_order_batches` groups orders by ward and service date for easier kitchen planning.
- `sync_logs` and `audit_logs` are mandatory for production traceability.

## Suggested indexes

- `patients_cache.hn`
- `patients_cache.an`
- `meal_orders.meal_date`
- `meal_orders.ward_id`
- `meal_order_items.meal_order_id`
- `sync_logs.status`

## Data retention

- Audit logs: keep long-term
- Sync logs: keep at least 90 days
- Telegram logs: keep at least 30 days
