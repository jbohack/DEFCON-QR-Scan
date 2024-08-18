# QR Code Redirector with Discord Webhook Notification

## About

This project is a server-side solution that provides seamless URL redirection upon scanning a QR code. It not only redirects the user to a specified URL but also issues a Discord webhook notification, capturing and sending the IP address information of the client who scanned the QR code.

## Output Example
![image](https://github.com/user-attachments/assets/d34faa1c-332f-4181-b2e6-b3a5239eaf8d)

## Features

- **Server-Side QR Code Redirection**: Redirects users to a specific URL after scanning the QR code.
- **Discord Webhook Integration**: Sends a notification to a Discord channel with the IP address and other metadata of the client who scanned the QR code.
- **IP Address Logging**: Captures the client's IP address for each scan and includes it in the webhook message.

## QR Code
<img src="https://cdn.lullaby.cafe/defcon/qr_code.png" alt="QR Code" width="200"/>

## Technologies Used

- **PHP**
- **HTML**

## Installation

1. Ensure that **PHP** is installed on your system.

2. Set up a web server, such as **Apache**.

3. Clone the repository:
    ```bash
    git clone https://github.com/jbohack/DEFCON-QR-Scan.git
    cd DEFCON-QR-Scan
    ```

4. Configure your environment variables for the redirect URL, Discord webhook URL, and other settings.

5. Place the project files in your web server's root directory (e.g., `/var/www/html` for Apache).

6. Start your web server to run the application.

## Configuration

- **window.location.href (index.html)**: The URL to which users should be redirected after scanning the QR code.
- **webhookUrls (info.php)**: The URL of the Discord webhook where notifications will be sent (Specify multiple if you wish).
- **thumbnail => URL (info.php)**: The URL of the QR code to include in the Discord Webhook.

## Usage

1. Deploy the server to your preferred hosting solution.
2. Generate a QR code that points to your server's URL.
3. Distribute the QR code to users.
4. Monitor the Discord channel for webhook notifications that include IP address information of users who scan the QR code.

## Contributing

Contributions are welcome! Please submit issues or pull requests for any improvements or features.

## License

This project is licensed under the [MIT License](LICENSE). Please see the `LICENSE` file for more details.
