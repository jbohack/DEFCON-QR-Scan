<?php
ini_set('display_errors', 0);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Content-Security-Policy: default-src \'self\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:;');

$config = require '../config.php';
if (!$config['enableMySQLLogging']) {
    die("MySQL logging is disabled.");
}


$conn = new mysqli(
    $config['mysql']['host'],
    $config['mysql']['user'],
    $config['mysql']['pass'],
    $config['mysql']['db']
);

if ($conn->connect_error) {
    die('Database connection failed');
}

$conn->set_charset('utf8mb4');

$table = $config['mysql']['table'];
$allowedPerPage = [5, 10, 25, 50, 100];


$requested = isset($_GET['per_page']) ? filter_var($_GET['per_page'], FILTER_VALIDATE_INT, [
    'options' => [
        'default'   => 5,
        'min_range' => 1,
        'max_range' => 100
    ]
]) : 5;
$perPage = in_array($requested, $allowedPerPage, true) ? $requested : 5;


$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    'options' => [
        'default'   => 1,
        'min_range' => 1
    ]
]);
$offset = ($page - 1) * $perPage;
$search = isset($_GET['search']) ? trim(htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8')) : '';
$search = preg_replace('/[^\p{L}\p{N}\s\-@._]/u', '', $search);

$check = $conn->query("SHOW TABLES LIKE '$table'");
if ($check->num_rows === 0) {
    echo "<p style='color:red; font-weight:bold;'>Error: Table <code>$table</code> not found.</p>";
    exit;
}

$where = '';
$bindTypes = '';
$bindValues = [];

if ($search !== '') {
    $where = "WHERE ip_address LIKE ?
        OR device_type LIKE ?
        OR operating_system LIKE ?
        OR browser_version LIKE ?
        OR gpu LIKE ?
        OR screen_resolution LIKE ?
        OR platform LIKE ?
        OR referrer LIKE ?
        OR city LIKE ?
        OR regionName LIKE ?
        OR country LIKE ?";

    $bindTypes = str_repeat('s', 11);
    $bindValues = array_fill(0, 11, "%$search%");
}

$countQuery = "SELECT COUNT(*) FROM `$table` $where";
$countStmt = $conn->prepare($countQuery);
if ($where) $countStmt->bind_param($bindTypes, ...$bindValues);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_row()[0];
$totalPages = max(1, ceil($totalRows / $perPage));

$dataQuery = "SELECT * FROM `$table` $where ORDER BY timestamp DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($dataQuery);
if ($where) {
    $bindTypes .= 'ii';
    $bindValues[] = $perPage;
    $bindValues[] = $offset;
    $stmt->bind_param($bindTypes, ...$bindValues);
} else {
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>QR Logs</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="60">
    <meta name="description" content="Real-time QR scan telemetry and device information">
    <meta name="robots" content="noindex, nofollow">
    
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <meta name="theme-color" content="#1a1a1a">
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scan Logs</title>
    <style>
        :root {
            --bg-dark: #1a1a1a;
            --bg-light: #2d2d2d;
            --bg-darker: #151515;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --accent: #ff69b4;
            --accent-dark: #ff1493;
            --hover: rgba(255, 105, 180, 0.1);
            --hover-dark: rgba(255, 105, 180, 0.05);
            --separator: #333333;
            --border: #333333;
            --shadow: rgba(0, 0, 0, 0.1);
            --card-radius: 12px;
            --focus-outline: 1px solid rgba(255, 105, 180, 0.5);
            --focus-shadow: 0 0 0 2px rgba(255, 105, 180, 0.1);
        }

        /* Subtle Hover States */
        .scan-card-item:hover {
            background: var(--hover-dark);
            border-color: var(--hover);
        }

        .scan-card-item:focus-within {
            outline: var(--focus-outline);
            outline-offset: 1px;
            box-shadow: var(--focus-shadow);
        }

        .device-info-item:hover {
            background: var(--hover);
            border-color: var(--hover);
        }

        .device-info-item:focus-within {
            background: var(--hover);
            border-color: var(--hover);
        }

        /* Pink and Grey Color Scheme */
        :root {
            --pink-light: #ffd7e9;
            --pink-medium: #ff69b4;
            --pink-dark: #ff1493;
            --grey-light: #333333;
            --grey-medium: #2d2d2d;
            --grey-dark: #1a1a1a;
        }

        /* Base Colors */
        body {
            background-color: var(--bg-darker);
            color: var(--text-primary);
        }

        .scan-card {
            background: var(--bg-dark);
            border: 1px solid var(--grey-light);
        }

        .scan-card-item {
            background: var(--bg-light);
            border: 1px solid var(--grey-light);
        }

        .device-info-item {
            background: var(--bg-dark);
            border: 1px solid var(--grey-light);
        }

        .device-label {
            color: var(--pink-medium);
        }

        .device-value {
            color: var(--text-primary);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg-darker);
            color: var(--text-primary);
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        h1 {
            text-align: center;
            margin: 20px 0;
            color: var(--text-primary);
            font-size: 2.5em;
            letter-spacing: -1px;
            background: var(--bg-dark);
            padding: 24px;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: var(--card-gap);
        }

        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            min-height: calc(100vh - 80px);
        }

        .footer {
            width: 100%;
            text-align: center;
            padding: 20px 0;
            border-top: 1px solid var(--border);
        }

        .footer-content {
            display: inline-block;
            background: var(--bg-dark);
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 4px 6px var(--shadow);
            text-align: center;
            font-size: 13px;
            line-height: 1.6;
            color: var(--text-secondary);
            width: 600px;
            max-width: 90%;
        }

        .footer-content p {
            margin: 8px 0;
        }

        .footer-content a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .footer-content a:hover {
            text-decoration: underline;
        }

        .search-container {
            margin-bottom: 32px;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 16px;
            background: var(--bg-dark);
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-top: 24px;
        }

        .search-group {
            display: flex;
            flex-direction: column;
        }

        .search-button {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--hover);
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            align-self: flex-end;
            width: 120px;
            margin-top: 8px;
        }

        .search-button:hover {
            background: var(--accent);
            border-color: var(--accent);
        }

        @media (max-width: 768px) {
            .search-button {
                width: 100%;
                padding: 10px 20px;
                font-size: 0.95rem;
                margin-top: 0;
            }
        }

        .container {
        }

        .scan-cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .scan-card {
            background: var(--bg-dark);
            border-radius: 12px;
            box-shadow: 0 2px 4px var(--shadow);
            padding: 12px;
            transition: transform 0.2s ease;
        }

        .scan-card:hover {
            transform: translateY(-1px);
        }

        .scan-card-content {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .scan-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }

        .scan-card-timestamp {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .scan-card-timestamp-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .scan-card-timestamp-value {
            display: flex;
            gap: 4px;
            align-items: center;
            font-size: 0.95rem;
            color: var(--text-primary);
        }

        .timestamp-date {
            font-weight: 500;
        }

        .timestamp-time {
            color: var(--text-secondary);
            font-size: 0.9em;
        }

        .scan-card-item {
            background: var(--bg-darker);
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 6px;
            position: relative;
        }

        .device-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            padding: 8px;
        }

.location-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.9em;
}

.location-details span {
    color: var(--text-secondary);
}

.location-details .city {
    font-weight: 500;
    color: var(--text-primary);
}

.location-details .region {
    font-size: 0.9em;
}

.location-details .country {
    font-size: 0.9em;
}

.scan-card-label {
    color: var(--text-secondary);
    font-weight: 500;
    font-size: 0.9em;
}
            outline: var(--focus-outline);
            outline-offset: 2px;
            box-shadow: var(--focus-shadow);
        }

        .scan-card-item:hover {
            background: var(--hover);
            border-color: var(--accent);
        }

            margin: 0;
            font-weight: 600;
        }

        .scan-card-timestamp {
            color: var(--text-secondary);
            font-size: 0.9em;
            font-weight: 500;
        }

        .scan-card-item:before {
            content: '';
            display: block;
            width: 4px;
            height: 100%;
            background: var(--accent);
            border-radius: 2px;
            margin-right: 12px;
        }

        .ip-timestamp-container {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .timestamp {
            color: var(--text-secondary);
            font-size: 0.9em;
            font-weight: 500;
            white-space: nowrap;
        }

        .location-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 0.9em;
        }

        .location-details span {
            color: var(--text-secondary);
        }

        .location-details .city {
            font-weight: 500;
            color: var(--text-primary);
        }

        .location-details .region {
            font-size: 0.9em;
        }

        .location-details .country {
            font-size: 0.9em;
        }

        .scan-card-label {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.9em;
        }

        .scan-card-value {
            color: var(--text-primary);
            word-break: break-word;
            font-size: 0.95em;
            line-height: 1.4;
        }

        .scan-card-value a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .scan-card-value a:hover {
            color: #388e3c;
            text-decoration: underline;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: var(--card-gap);
            padding: 24px 0;
            background: var(--bg-dark);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }

        .pagination a, .pagination span {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 8px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-primary);
            background: var(--bg-darker);
            transition: all 0.2s ease;
            min-width: 40px;
            text-align: center;
            border: 1px solid var(--separator);
            font-weight: 500;
        }

        .pagination a:hover {
            background-color: var(--hover);
            color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .pagination .current {
            background-color: var(--accent);
            color: white;
            font-weight: 600;
            border: 1px solid var(--accent);
        }

        .pagination .ellipsis {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: default;
            user-select: none;
        }

        .pagination .ellipsis:hover {
            background: transparent;
            transform: none;
            box-shadow: none;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: var(--text-secondary);
            font-size: 1.2em;
            background: var(--bg-darker);
            border-radius: 12px;
            box-shadow: 0 4px 6px var(--shadow);
        }

        @media screen and (max-width: 768px) {
            .container {
                padding: 0 10px;
            }

            .responsive-table {
                display: block;
                overflow-x: auto;
            }

            .responsive-table th,
            .responsive-table td {
                min-width: 140px;
                font-size: 13px;
            }

            .responsive-table tr {
                border-top: 2px solid var(--separator);
            }

            .pagination {
                padding: 15px 0;
                flex-wrap: wrap;
                gap: 8px;
            }

            .pagination a, .pagination span {
                padding: 10px 16px;
                margin: 0 2px;
                min-width: 36px;
                font-size: 14px;
            }

            .pagination .ellipsis {
                padding: 10px 8px;
                min-width: auto;
            }
        }

        @media screen and (max-width: 480px) {

            h1 {
                font-size: 2em;
                margin: 15px 0;
            }

            .responsive-table {
                display: block;
                margin: 20px 0;
            }

            .responsive-table th,
            .responsive-table td {
                display: block;
                text-align: left;
                border: none;
                padding: 12px 15px;
                font-size: 14px;
            }

            .responsive-table th {
                display: none;
            }

            .responsive-table td:before {
                content: attr(data-label);
                font-weight: 500;
                display: inline-block;
                width: 100px;
                margin-right: 10px;
                color: var(--text-secondary);
            }

            .responsive-table tr {
                border-top: 2px solid var(--separator);
                margin-bottom: 20px;
                padding: 15px;
                background: var(--bg-dark);
                border-radius: 8px;
            }

            .responsive-table tr:first-child {
                border-top: none;
            }

            .pagination {
                padding: 20px 0;
                margin: 20px 0;
                flex-wrap: wrap;
                gap: 6px;
                justify-content: center;
            }

            .pagination a, .pagination span {
                min-width: 32px;
                padding: 8px 12px;
                margin: 0 2px;
                font-size: 13px;
            }

            .pagination .ellipsis {
                padding: 8px 6px;
                min-width: auto;
                font-size: 13px;
            }

            .search-form {
                margin-bottom: 25px;
            }

            .footer-container {
                width: 100%;
                padding: 20px 0;
                border-top: 1px solid var(--border);
                text-align: center;
            }

            .footer {
                display: block;
                width: 100%;
                text-align: center;
                padding: 20px 0;
                border-top: 1px solid var(--border);
            }

            .footer-content {
                display: inline-block;
                background: var(--bg-dark);
                padding: 16px;
                border-radius: 12px;
                box-shadow: 0 4px 6px var(--shadow);
                text-align: center;
                font-size: 13px;
                line-height: 1.6;
                color: var(--text-secondary);
                width: 600px;
                max-width: 90%;
            }

            @media screen and (max-width: 768px) {
                .footer {
                    padding: 12px;
                    font-size: 12px;
                    width: 90%;
                    display: block;
                }
            }

            .footer a {
                color: var(--accent);
                text-decoration: none;
                font-weight: 500;
            }

            .footer a:hover {
                text-decoration: underline;
            }

            @media screen and (max-width: 768px) {
                .footer {
                    padding: 12px;
                    font-size: 12px;
                    width: 95%;
                }
            }

            .footer a {
                color: var(--accent);
                text-decoration: none;
                font-weight: 500;
            }

            .footer a:hover {
                text-decoration: underline;
            }

            @media screen and (max-width: 768px) {
                .footer {
                    padding: 16px;
                    font-size: 12px;
                }
            }
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="container">
            <h1>QR Scan Logs</h1>
            <div class="search-container">
                <form method="get" class="search-form" id="searchForm">
                    <div class="search-group">
                        <label>Results per page</label>
                        <select name="per_page" onchange="this.form.submit()">
                        <?php foreach ($allowedPerPage as $opt): ?>
                            <option value="<?= $opt ?>" <?= $opt == $perPage ? 'selected' : '' ?>><?= $opt ?> entries</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by IP, city, OS, GPU..." maxlength="255">
                </div>
                <button type="submit" class="search-button">Search</button>
            </form>
        </div>

        <?php if ($totalRows > 0): ?>
            <div class="scan-cards">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="scan-card">
                        <div class="scan-card-content">
                            <div class="scan-card-header">
                                <div class="scan-card-timestamp">
                                    <span class="scan-card-timestamp-label">Scan Time</span>
                                    <span class="scan-card-timestamp-value">
                                        <span class="timestamp-date"><?= date('M j, Y', strtotime($row['timestamp'])) ?></span>
                                        <span class="timestamp-time"><?= date('H:i:s', strtotime($row['timestamp'])) ?></span>
                                    </span>
                                </div>
                            </div>
                            <div class="scan-card-item">
                                <span class="scan-card-label">IP Address</span>
                                <span class="scan-card-value">
                                    <a href="https://search.censys.io/hosts/<?= urlencode($row['ip_address']) ?>" target="_blank" rel="noopener noreferrer">
                                        <?= htmlspecialchars($row['ip_address']) ?>
                                    </a>
                                </span>
                            </div>
                            <div class="scan-card-item">
                                <span class="scan-card-label">Device Info</span>
                                <span class="scan-card-value">
                                    <div class="device-info">
                                        <div class="device-info-item">
                                            <span class="device-label">Type</span>
                                            <span class="device-value"><?= htmlspecialchars($row['device_type']) ?></span>
                                        </div>
                                        <div class="device-info-item">
                                            <span class="device-label">OS</span>
                                            <span class="device-value"><?= htmlspecialchars($row['operating_system']) ?></span>
                                        </div>
                                        <div class="device-info-item">
                                            <span class="device-label">Browser</span>
                                            <span class="device-value"><?= htmlspecialchars($row['browser_version']) ?></span>
                                        </div>
                                        <div class="device-info-item">
                                            <span class="device-label">GPU</span>
                                            <span class="device-value"><?= htmlspecialchars($row['gpu']) ?></span>
                                        </div>
                                        <div class="device-info-item">
                                            <span class="device-label">Resolution</span>
                                            <span class="device-value"><?= htmlspecialchars($row['screen_resolution']) ?></span>
                                        </div>
                                        <div class="device-info-item">
                                            <span class="device-label">Platform</span>
                                            <span class="device-value"><?= htmlspecialchars($row['platform']) ?></span>
                                        </div>
                                    </div>
                                </span>
                            </div>
                            <div class="scan-card-item">
                                <span class="scan-card-label">Location</span>
                                <span class="scan-card-value">
                                    <span class="location-details">
                                        <span class="city"><?= htmlspecialchars($row['city']) ?></span>
                                        <span class="region"><?= htmlspecialchars($row['regionName']) ?></span>
                                        <span class="country"><?= htmlspecialchars($row['country']) ?></span>
                                    </span>
                                </span>
                            </div>
                            <div class="scan-card-item">
                                <span class="scan-card-label">Referrer</span>
                                <span class="scan-card-value"><?= htmlspecialchars($row['referrer']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= max(1, $page - 1) ?>&per_page=<?= htmlspecialchars($perPage, ENT_QUOTES, 'UTF-8') ?>&search=<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">← Prev</a>
                <?php endif; ?>
                
                <?php
                $range = 2;
                $start = max(1, $page - $range);
                $end = min($totalPages, $page + $range);
                
                if ($start > 1): ?>
                    <a href="?page=1&per_page=<?= htmlspecialchars($perPage, ENT_QUOTES, 'UTF-8') ?>&search=<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">1</a>
                    <?php if ($start > 2): ?>
                        <span class="ellipsis">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($p = $start; $p <= $end; $p++): ?>
                    <?php if ($p == $page): ?>
                        <span class="current"><?= $p ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $p ?>&per_page=<?= htmlspecialchars($perPage, ENT_QUOTES, 'UTF-8') ?>&search=<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <span class="ellipsis">...</span>
                    <?php endif; ?>
                    <a href="?page=<?= $totalPages ?>&per_page=<?= htmlspecialchars($perPage, ENT_QUOTES, 'UTF-8') ?>&search=<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"><?= $totalPages ?></a>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= min($totalPages, $page + 1) ?>&per_page=<?= htmlspecialchars($perPage, ENT_QUOTES, 'UTF-8') ?>&search=<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">Next →</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-data">No scan data found.</div>
        <?php endif; ?>
    </div>
</div>

<div class="footer">
    <div class="footer-content">
        <p>This page is publicly accessible and displays real-time telemetry from QR scans.</p>
        <p>IP addresses, device info, and referrers are shown intentionally for research and transparency.</p>
        <p>Made with 💖 by <a href="https://github.com/jbohack" target="_blank">jbohack</a>.</p>
    </div>
</div>
</body>
</html>