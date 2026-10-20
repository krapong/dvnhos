# Test Plan

## MVP scenarios

1. Ward dashboard loads successfully
2. Patient cache displays HN, AN, bed, BMI, allergy, and NPO
3. Bulk apply template to multiple beds
4. Copy orders from previous day
5. Create STAT order
6. Advance order status through workflow
7. Reject order with reason
8. Trigger manual HOSxP sync
9. Send Telegram test notification

## Negative scenarios

1. NPO patient should not be auto-filled with normal diet
2. Invalid diet template ID should fail validation
3. Unknown role should be blocked by middleware
4. HOSxP sync failure should not break dashboard cache
5. Telegram disabled should still create a log entry

## Suggested automated tests

- Feature test for dashboard JSON
- Feature test for meal order bulk apply
- Feature test for STAT order
- Feature test for sync endpoint
- Unit test for summary aggregation
