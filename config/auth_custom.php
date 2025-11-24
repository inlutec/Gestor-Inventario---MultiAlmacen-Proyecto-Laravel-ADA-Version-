<?php

return [
    'max_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
    'lockout_time' => env('LOGIN_LOCKOUT_TIME', 15),
    'session_duration' => env('SESSION_DURATION', 480),
    'version' => env('APP_VERSION', '3.0.0'),
];
