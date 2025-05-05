<?php $config = require 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR Scan</title>
</head>
<body>
<script>
    const CONFIG = {
        redirectUrl: <?php echo json_encode($config['redirectUrl']); ?>
    };

    function getDeviceInformation() {
        const deviceInfo = {
            deviceType: /Android|iPhone|iPad|iPod|IEMobile/i.test(navigator.userAgent) ? 'Mobile' : 'Desktop',
            operatingSystem: navigator.platform,
            browserVersion: navigator.userAgent,
            gpu: getGPUInfo(),
            screenResolution: `${screen.width}x${screen.height}`,
            platform: navigator.platform,
            referrer: document.referrer || 'Unknown'
        };
        return deviceInfo;
    }

    function getGPUInfo() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (!gl) return 'N/A';
            const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            return debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : 'N/A';
        } catch (e) {
            return 'N/A';
        }
    }

    function sendDeviceInfo() {
        const deviceInfo = getDeviceInformation();

        fetch('info.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(deviceInfo)
        }).finally(() => {
            window.location.href = CONFIG.redirectUrl;
        });
    }

    sendDeviceInfo();
</script>
</body>
</html>