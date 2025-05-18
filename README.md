# QR Code Tracker with Real-time Discord Alerts & Analytics Dashboard

## About

This project is a server-side solution that provides seamless URL redirection upon scanning a QR code. It not only redirects the user to a specified URL but also issues a Discord webhook notification, capturing and sending the IP address information of the client who scanned the QR code.

## Output Example
![image](https://github.com/user-attachments/assets/1b50e5b6-93ea-41b0-9063-a1b2b5fa3d01)


## Features

- **Server-Side QR Code Redirection**: Redirects users to a specific URL after scanning the QR code.
- **Discord Webhook Integration**: Sends a notification to a Discord channel with the IP address and other metadata of the client who scanned the QR code.
- **IP Address Logging**: Captures the client's IP address for each scan and includes it in the webhook message.
- **Optional MySQL Logging**: Stores scan metadata (IP, OS, device info, etc.) in a MySQL database.
- **Optional Scan Dashboard**: Web-based interface to view and search historical scan data with filtering and pagination (`/scans/`). The dashboard is fully responsive and works on both desktop and mobile devices.

## QR Code
<img src="https://cdn.lullaby.cafe/defcon/qr_code.png" alt="QR Code" width="200"/>

## Technologies Used

- **PHP**
- **HTML**
- **MySQL (Optional)**

## Installation

1. Ensure that **PHP** is installed on your system.

2. Set up a web server, such as **Apache**.

3. Clone the repository:
    ```bash
    git clone https://github.com/jbohack/DEFCON-QR-Scan.git
    cd DEFCON-QR-Scan
    ```

4. Edit the `config.php` file to set your redirect URL, Discord webhook(s), and other options. MySQL logging can be optionally enabled in this file.

5. Place the project files in your web server's root directory (e.g., `/var/www/html` for Apache).

6. Start your web server to run the application.

## Scan Dashboard

When MySQL logging is enabled, access the scan dashboard at `/scans/` (example: [defcon.lullaby.cafe/scans/](https://defcon.lullaby.cafe/scans/)) to:

- View historical scan data in a clean, sortable interface
- Search across all scan metadata (IP, device, location, etc.)
- Filter and paginate through scan results
- View detailed device and location information for each scan
- Fully responsive design that works on both desktop and mobile devices

## Configuration

All settings are centralized in `config.php`:

- **redirectUrl**: The URL to which users should be redirected after scanning the QR code.
- **webhookUrls**: One or more Discord webhook URL(s) where notifications will be sent.
- **webhookUsername**: The display name used in the Discord webhook.
- **webhookAvatar**: The avatar URL used for the webhook sender.
- **webhookThumbnail**: The image shown in the Discord embed (typically the hosted QR code).
- **enableMySQLLogging**: Set to `true` to log scan data to a MySQL database.
- **mysql**: Configure MySQL connection details (host, user, password, database, and table name).

## Usage

1. Deploy the server to your preferred hosting solution.
2. Generate a QR code that points to your server's URL.
3. Distribute the QR code to users.
4. Monitor the Discord channel for webhook notifications that include IP address information of users who scan the QR code.

## Contributing

Contributions are welcome! Please submit issues or pull requests for any improvements or features.

## License

This project is licensed under the [MIT License](LICENSE). Please see the `LICENSE` file for more details.
