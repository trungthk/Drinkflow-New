<?php

return [
    'audit_logs_days' => (int) env('RETENTION_AUDIT_LOGS_DAYS', 30),
    'read_admin_notifications_days' => (int) env('RETENTION_READ_ADMIN_NOTIFICATIONS_DAYS', 30),
    'read_user_notifications_days' => (int) env('RETENTION_READ_USER_NOTIFICATIONS_DAYS', 30),
    'crawler_previews_days' => (int) env('RETENTION_CRAWLER_PREVIEWS_DAYS', 2),
    'log_files_days' => (int) env('RETENTION_LOG_FILES_DAYS', 14),
];
