-- Patch: add cumulative paid amount tracking for fees
-- Run once against existing SchoolMS databases.

ALTER TABLE fees
    ADD COLUMN paid_amount DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER amount;

UPDATE fees
SET paid_amount = CASE
    WHEN payment_status = 'Paid' THEN amount
    ELSE paid_amount
END;
