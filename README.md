# Advanced Data Usage Plugin for PHPNuxBill

[![StandWithPalestine](https://raw.githubusercontent.com/Safouene1/support-palestine-banner/master/StandWithPalestine.svg)](https://github.com/Safouene1/support-palestine-banner/blob/master/Markdown-pages/Support.md)

[![Platform](https://img.shields.io/badge/platform-PHPNuxBill-3B82F6?style=flat-square)](#)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)](#)
[![MikroTik](https://img.shields.io/badge/MikroTik-RouterOS-293239?style=flat-square)](#)
[![LICENSE](https://img.shields.io/badge/LICENSE-MIT-FF6655?style=flat-square)](#)

A real-time, reboot-safe session tracking and bandwidth monitoring plugin for **MikroTik PPPoE and Hotspot networks**.

Automatically handles:

- Hybrid telemetry sync combining RouterOS API, on-router scheduler scripts, and server cron
- Live upload and download traffic graphing with minimum, average, and peak metrics
- Centralized multi-router connectivity checks and active client counts
- Stored offline session visibility when routers lose connectivity
- Automatic reconciliation to purge and close orphaned or crashed sessions
- Historical logging for up to 100 recent connections and 30 days of aggregated user data

## Install

Copy the plugin files into your PHPNuxBill directories:

```bash
# Plugin logic
cp dataUsage.php system/plugin/

# UI templates and assets
cp -r data_usage system/plugin/ui/
```

## Setup

1. Log into your PHPNuxBill admin dashboard.
2. Navigate to **Plugin Manager** and enable **Advanced Data Usage**.
3. Go to the plugin's **Settings** page to finalize router script deployment and cron configuration.

## Configuration

| Feature | Limit / Specification |
|---:|---|
| Session Retention | Up to 100 entries per subscriber |
| Usage History | Up to 30 days aggregated data |
| Engine Type | Hybrid (API + On-Router Scripts + Cron) |
| Target Networks | PPPoE & Hotspot |

## Support/Donate

If you found this project helpful for your business or personal use, consider supporting the development.

[![Buy me a Coffee](https://img.shields.io/badge/Support-Mahmud-ff69b4?style=for-the-badge&logo=kofi&logoColor=white)](https://linktr.ee/systik)