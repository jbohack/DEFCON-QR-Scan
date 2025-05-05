<?php
return [
    // URL redirection after QR code scan
    "redirectUrl" => "https://lullaby.cafe",

    // Enter your Discord Webhook URL(s) (multiple URLs can be added)
    "webhookUrls" => [
        "https://discord.com/api/webhooks/yyy/abc123",
        "https://discord.com/api/webhooks/yyy/def456"
    ],
    "webhookUsername" => "QR Bot - jbohack",
    "webhookAvatar" => "https://cdn.lullaby.cafe/defcon/defcon.png",
    "webhookFooter" => "https://cdn.lullaby.cafe/defcon/nyan.png",
    "webhookThumbnail" => "https://cdn.lullaby.cafe/defcon/qr_code.png",

    // MySQL Logging (Optional)
    "enableMySQLLogging" => false,  // Set to true to enable logging

    "mysql" => [
        "host" => "localhost",
        "user" => "your_mysql_user",
        "pass" => "your_mysql_password",
        "db"   => "qr_scan_data",
        "table"=> "scans"
    ]
];