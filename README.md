# Advanced Data Usage Plugin for PHPNuxBill

A complete PPPoE & Hotspot session tracking and bandwidth monitoring plugin — real-time, lightweight, and reboot-safe.

## Features

- **Hybrid tracking engine** — RouterOS API, on-router scripts/scheduler, and server-side cron working together
- **Real-time session dashboard** — live online/offline status fetched directly via API for instant network diagnostics
- **Live Traffic Monitor** — real-time upload/download graph per user with Min/Avg/Max stats
- **Router Monitor overview** — see all routers' online/offline status and connected user counts at a glance
- **Works with offline routers** — view last known session data even when a router is unreachable
- **Session history** — up to 100 recent sessions per user, with start/end time, duration, and disconnect reason
- **Daily usage history** — up to 30 days of aggregated usage per user
- **Multi-router support** — fully isolated tracking, filtering, and reconciliation per router
- **Auto reconciliation** — safely closes stale/crashed sessions without manual cleanup

## Installation

1. Copy `dataUsage.php` to `system/plugin/`
2. Copy the `data_usage` folder to `system/plugin/ui/`
3. Log into the admin dashboard and follow the setup instructions on the **Settings** page

## Support

If this plugin saved you time, consider buying me a meal 🙂

[![Buy me a Coffee](https://img.shields.io/badge/Support-Buy%20me%20a%20Coffee-ff69b4?style=for-the-badge&logo=kofi&logoColor=white)](https://wa.me/@systik_ofc?text=I%20wanna%20buy%20you%20a%20meal)
