<?php
$config = require 'config.php';

function getReverseHeaderIp() {
    return filter_var(
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'],
        FILTER_VALIDATE_IP
    ) ?: 'Unknown';
}

function sendToDiscord($webhookUrls, $embed, $config) {
    $json_data = json_encode([
        "username" => $config['webhookUsername'],
        "avatar_url" => $config['webhookAvatar'],
        "embeds" => [$embed]
    ], JSON_UNESCAPED_SLASHES);

    foreach ($webhookUrls as $webhookUrl) {
        if (filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}

$reverseHeaderIp = getReverseHeaderIp();
$timestamp = gmdate("Y-m-d\TH:i:s\Z");
$deviceInfo = json_decode(file_get_contents('php://input'), true);

function safeField($arr, $key) {
    return isset($arr[$key]) ? htmlspecialchars(strip_tags($arr[$key])) : 'N/A';
}

$data = [
    'ip_address'        => $reverseHeaderIp,
    'device_type'       => safeField($deviceInfo, 'deviceType'),
    'operating_system'  => safeField($deviceInfo, 'operatingSystem'),
    'browser_version'   => safeField($deviceInfo, 'browserVersion'),
    'gpu'               => safeField($deviceInfo, 'gpu'),
    'screen_resolution' => safeField($deviceInfo, 'screenResolution'),
    'platform'          => safeField($deviceInfo, 'platform'),
    'referrer'          => safeField($deviceInfo, 'referrer') ?: 'Unknown'
];

$ip_b64 = base64_encode($reverseHeaderIp);

$embed = [
    "title" => "📊 QR-Scan Device Information",
    "color" => hexdec("ffb7c5"),
    "fields" => [
        ["name" => "🌐 IP Address",       "value" => $data['ip_address'], "inline" => true],
        ["name" => "📱 Device Type",      "value" => $data['device_type'], "inline" => true],
        ["name" => "💻 Operating System", "value" => $data['operating_system'], "inline" => true],
        ["name" => "🌐 Browser & Version","value" => $data['browser_version'], "inline" => true],
        ["name" => "🎮 GPU",              "value" => $data['gpu'], "inline" => true],
        ["name" => "📏 Screen Resolution","value" => $data['screen_resolution'], "inline" => true],
        ["name" => "🖥️ Platform",         "value" => $data['platform'], "inline" => true],
        ["name" => "🔗 Referring URL",    "value" => $data['referrer'], "inline" => false],
        ["name" => "🕒 Timestamp",        "value" => $timestamp, "inline" => false],
        ["name" => "🔍 OSINT Lookup",     "value" =>
            "[Censys](https://search.censys.io/hosts/{$reverseHeaderIp})\n" .
            "[Shodan](https://www.shodan.io/host/{$reverseHeaderIp})\n" .
            "[VirusTotal](https://www.virustotal.com/gui/ip-address/{$reverseHeaderIp})\n" .
            "[Pulsedive](https://pulsedive.com/indicator/?ioc={$ip_b64})",
         "inline" => false]
    ],
    "footer" => [
        "text" => "Made with 🩷 by jbohack, inspired by RocketGod",
        "icon_url" => $config['webhookFooter']
    ],
    "thumbnail" => ["url" => $config['webhookThumbnail']]
];

sendToDiscord($config['webhookUrls'], $embed, $config);

if ($config['enableMySQLLogging']) {
    require_once 'db.php';
    insertScanData($conn, $config['mysql']['table'], $data);
}