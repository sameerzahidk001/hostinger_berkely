<?php

return [
    'accounts_url' => env('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com'),
    'meeting_url' => env('ZOHO_MEETING_URL', 'https://meeting.zoho.com'),
    'workdrive_url' => env('ZOHO_WORKDRIVE_URL', 'https://www.zohoapis.com/workdrive/api/v1'),
    'workdrive_upload_url' => env('ZOHO_WORKDRIVE_UPLOAD_URL', 'https://www.zohoapis.com/workdrive/api/v1/upload'),
    'calendar_url' => env('ZOHO_CALENDAR_URL', 'https://calendar.zoho.com/api/v1'),
    'client_id' => env('ZOHO_CLIENT_ID'),
    'client_secret' => env('ZOHO_CLIENT_SECRET'),
    'refresh_token' => env('ZOHO_REFRESH_TOKEN'),
    'org_id' => env('ZOHO_ORG_ID'),
    'presenter_zuid' => env('ZOHO_PRESENTER_ZUID'),
    'workdrive_folder_id' => env('ZOHO_WORKDRIVE_FOLDER_ID'),
    'calendar_uid' => env('ZOHO_CALENDAR_UID'),
    'account_email' => env('ZOHO_ACCOUNT_EMAIL', 'bdm@berkeleyme.com'),
    'timezone' => env('ZOHO_TIMEZONE', 'Asia/Dubai'),
];
