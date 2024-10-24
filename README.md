# 🔄 Salesforce Opt-out Automation

A PHP script that automates the process of updating email opt-out status in Salesforce based on data from an external API. This automation helps maintain compliance with user preferences by automatically marking contacts as opted-out from email communications.

## ✨ Features

- Automatic processing of opt-out requests from an external API
- Salesforce integration using Force.com Toolkit for PHP
- Robust logging system with daily rotation
- Environment-based configuration
- Error handling and detailed logging
- Progress tracking to prevent duplicate processing
- Automatic cleanup of old log files

## 📋 Prerequisites

- PHP 7.0 or higher
- Salesforce account with API access
- Force.com Toolkit for PHP
- Write permissions for the logs directory
- Valid Salesforce security token

## 🚀 Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/7LeeGIT/salesforce-optout-automation.git
   cd salesforce-optout-automation
   ```

2. Install Force.com Toolkit for PHP:

   ```bash
   # You'll need to manually download and include the Force.com Toolkit for PHP
   # Place it in the project root directory
   ```

3. Create and configure your environment file:

   ```bash
   cp .env.example .env
   ```

4. Edit the `.env` file with your credentials:
   ```
   SALESFORCE_USERNAME=your_salesforce_username
   SALESFORCE_PASSWORD=your_salesforce_password
   SALESFORCE_SECURITY_TOKEN=your_salesforce_security_token
   API_URL=https://your-api-endpoint.com
   ```

## 📁 Directory Structure

```
├── .env.example
├── .env
├── .gitignore
├── salesforce-optout-automation.php
├── logs/
│   └── .gitkeep
└── Force.com-Toolkit-for-PHP/
```

## 💻 Usage

Run the script manually:

```bash
php salesforce-optout-automation.php
```

Or set up a cron job for automated execution:

```bash
# Example: Run every hour
0 * * * * /usr/bin/php /path/to/salesforce-optout-automation.php
```

## 📝 Logging

Logs are stored in the `logs` directory with daily rotation. Each log file follows the naming convention `app_YYYY-MM-DD.log`. Log files older than 30 days are automatically cleaned up.

## ⚠️ Error Handling

The script includes comprehensive error handling:

- Invalid email validation
- API connection failures
- Salesforce authentication issues
- Contact lookup failures
- Update operation failures

All errors are logged with appropriate severity levels (INFO, WARNING, ERROR).

## 🔒 Security

- Sensitive credentials are stored in the `.env` file
- The `.gitignore` file prevents committing sensitive information
- The script validates email addresses before processing

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 👨‍💻 Author

Created by [7LeeGIT](https://github.com/7LeeGIT)

WHO'S KOR ?
