DROP VIEW IF EXISTS ka_latest_scheduled_task_log;

CREATE VIEW ka_latest_scheduled_task_log AS
SELECT *
FROM (SELECT scheduled_task_id,
             start_time,
             end_time,
             status,
             log_output,
             ROW_NUMBER() over (PARTITION BY scheduled_task_id ORDER BY id DESC) position
      FROM ka_scheduled_task_log) A
WHERE position = 1;