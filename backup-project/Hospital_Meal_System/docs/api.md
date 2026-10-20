# API

Base path: `/api/v1`

## Dashboards

- `GET /dashboard/nurse`
- `GET /dashboard/nutrition`
- `GET /dashboard/kitchen`
- `GET /dashboard/admin`

## Patients

- `GET /patients`
- `GET /patients/{hn}`
- `POST /patients/{hn}/transfer`
- `POST /patients/{hn}/discharge`

## Diet templates

- `GET /diet-templates`
- `POST /diet-templates`
- `PUT /diet-templates/{dietTemplate}`

## Meal orders

- `GET /meal-orders`
- `POST /meal-orders/bulk-apply`
- `POST /meal-orders/copy-from-previous-day`
- `POST /meal-orders/stat`
- `POST /meal-orders/{mealOrder}/advance`
- `POST /meal-orders/{mealOrder}/reject`

## Sync and notifications

- `POST /sync/hosxp`
- `POST /notifications/telegram/test`

## Response conventions

- Use JSON only
- Return validation errors with Laravel default structure
- Include `ok` fields on action endpoints
