
To fetch overdue payments for a specific zone:
-----------------------------------------

SELECT e.name AS entity_name, mp.amount_due, mp.amount_paid, mp.payment_status
FROM monthly_payments mp
JOIN entities e ON mp.entity_id = e.id
JOIN zone_entities ze ON e.id = ze.entity_id
WHERE ze.zone_id = ? AND mp.payment_status = 'Unpaid' AND mp.billing_month = '2024-11-01';



