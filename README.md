# Advanced Data Usage Plugin for PHPNuxBill

[![StandWithPalestine](https://raw.githubusercontent.com/Safouene1/support-palestine-banner/master/StandWithPalestine.svg)](https://github.com/Safouene1/support-palestine-banner/blob/master/Markdown-pages/Support.md)

[![Platform](https://img.shields.io/badge/platform-PHPNuxBill-3B82F6?style=flat-square)](#)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](#)
[![MikroTik](https://img.shields.io/badge/MikroTik-RouterOS-293239?style=flat-square)](#)
[![LICENSE](https://img.shields.io/badge/LICENSE-MIT-FF6655?style=flat-square)](#)

Real-time session tracking and bandwidth monitoring for **MikroTik PPPoE and Hotspot** in PHPNuxBill.

## Features

- **Real-time Session & Traffic Monitoring**: Live upload/download speeds and active user status via RouterOS API.
- **Hybrid Sync Engine**: Combines PPPoE/Hotspot profile scripts (`on-up`/`on-down`), on-router scheduler poller, and manual/cron sync.
- **Multi-Router Dashboard**: Reachability status, active user counts, and per-router filtering.
- **Session History & Daily Usage**: Tracks active/closed sessions, daily upload/download totals, and per-user usage history.
- **Admin Controls**: One-click user disconnect, CSV usage export, configurable log retention, and optional webhook token security.

## Install

### Option 1: Marketplace / Plugin Manager (Recommended)
1. Open **Marketplace → Plugins** (or **Plugin Manager**) in PHPNuxBill Admin.
2. Install **Data Usage** from the store, or upload the `dataUsage` `.zip` / GitHub URL (`https://github.com/mhmd3010/datausage`).
3. Activate the plugin.

### Option 2: Manual
Copy the `dataUsage` folder into `system/plugin/`:

```bash
cp -r dataUsage /path/to/phpnuxbill/system/plugin/
```

## Setup

1. Open **Data Usage** from the admin sidebar.
2. Go to **Router Setup / Settings** to deploy the PPPoE/Hotspot profile scripts and poller to your MikroTik routers.
3. *(Optional)* Set `data_usage_webhook_token` in `tbl_appconfig` to require a token on incoming webhook requests.

## Specifications

| Feature | Details |
| :--- | :--- |
| Target Networks | MikroTik PPPoE & Hotspot |
| Engine | Hybrid (RouterOS API + Profile Scripts + Poller/Cron) |
| Export | CSV report by date range & router |
| Log Retention | Configurable (0–365 days) |

## Support

Need help or want to support development?

[![Telegram](https://img.shields.io/badge/Telegram-Mahmud-26A5E4?style=for-the-badge&logo=telegram&logoColor=white)](https://t.me/mhmd_3010)
