-- Run once in the KRF database, before installing the PHP files.
ALTER TABLE krf_provisional_order_followup MODIFY next_followup_date DATETIME NULL DEFAULT NULL;
ALTER TABLE krf_provisional_sales_order ADD COLUMN followup_review_pending TINYINT(1) NOT NULL DEFAULT 0;
-- Preserve existing Not Interested cases awaiting review.
UPDATE krf_provisional_sales_order p JOIN krf_provisional_order_followup f
 ON f.id=(SELECT MAX(h.id) FROM krf_provisional_order_followup h
 WHERE h.projectid=p.projectid AND h.psono=CONVERT(p.psono USING utf8mb4) COLLATE utf8mb4_general_ci)
 SET p.followup_review_pending=1 WHERE p.status='Draft' AND f.reason='Not Interested';
-- Clear only each order's latest Not Interested schedule; preserve older history.
UPDATE krf_provisional_order_followup f
 JOIN (SELECT projectid,psono,MAX(id) AS latest_id FROM krf_provisional_order_followup GROUP BY projectid,psono) latest
 ON latest.latest_id=f.id
 JOIN krf_provisional_sales_order p
 ON f.projectid=p.projectid AND f.psono=CONVERT(p.psono USING utf8mb4) COLLATE utf8mb4_general_ci
 SET f.next_followup_date=NULL WHERE p.status='Draft' AND f.reason='Not Interested';
