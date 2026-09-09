# Project-SULO

**Smart Utility for Local Organic Waste** — A cloud-based, multi-unit biogas digester monitoring system.

## Overview

SULO monitors multiple biogas digesters in real-time using ESP32-connected sensors. All data is stored in the cloud via Supabase (PostgreSQL). An admin dashboard is accessible from anywhere with internet — **no Raspberry Pi needed**.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Cloud (Supabase)                         │
│                                                                 │
│  ┌──────────────┐   ┌──────────────┐   ┌──────────────────┐   │
│  │  PostgreSQL   │   │   Auth       │   │  Realtime        │   │
│  │  Database     │   │   Service    │   │  Subscriptions   │   │
│  └──────────────┘   └──────────────┘   └──────────────────┘   │
│           ↑                  ↑                    ↑              │
│           └──────────────────┴────────────────────┘              │
│                              ↑                                   │
│                    ┌─────────┴─────────┐                        │
│                    │  Admin Dashboard   │                        │
│                    │  (Web App)         │                        │
│                    └─────────┬─────────┘                        │
└──────────────────────────────┼──────────────────────────────────┘
                               │ Internet
              ┌────────────────┼────────────────┐
              │                │                │
     ┌────────┴────────┐ ┌────┴──────┐ ┌───────┴───────┐
     │  ESP32 Unit A   │ │ ESP32 Unit B│ │ ESP32 Unit C  │
     │  (Karenderya)   │ │ (Kitchen)  │ │ (Bakery)      │
     └─────────────────┘ └───────────┘ └───────────────┘
```

## How It Works

1. **Power On** → ESP32 initializes all sensors, connects to local WiFi, connects to Laravel API
2. **Poll Sensors** → Every 10 seconds: temperature, pH, gas, pressure, flow rate
3. **Evaluate Thresholds** → Compare readings against safe ranges
4. **Update Display** → OLED shows current values + status (Normal/Warning/Critical)
5. **Send Data** → HTTP POST JSON to local Laravel API over WiFi
6. **Fail-Safe** → Critical overpressure → solenoid valve opens automatically
7. **IR Remote** → Navigate display screens, silence alerts
8. **Dashboard** → Open `http://192.168.4.1:8000` on any LAN device for live data, charts, alerts
9. **Alerts** → Both LCD and dashboard reflect threshold breaches immediately
10. **Repeat** → Steps 2–9 loop continuously

## Directory Structure

```
Project-SULO/
├── firmware/              # ESP32 Arduino/PlatformIO code
│   ├── ESP32.ino          # Main sketch
│   ├── config.h           # Configuration (WiFi, pins, thresholds)
│   ├── sensors.cpp/h      # Sensor reading + threshold evaluation
│   ├── display.cpp/h      # OLED multi-screen display
│   ├── wifi.cpp/h         # WiFi connection + HTTP POST
│   ├── failsafe.cpp/h     # Solenoid valve fail-safe logic
│   └── platformio.ini     # PlatformIO project config
│
├── backend/               # Laravel API (runs on Raspberry Pi)
│   ├── app/Http/Controllers/
│   ├── app/Models/
│   ├── routes/
│   ├── resources/views/   # Dashboard Blade template
│   ├── database/migrations/
│   └── composer.json
│
├── scripts/
│   └── setup-rpi.sh       # One-command Raspberry Pi setup
│
└── documentation/
    └── SYSTEM_ARCHITECTURE.MD
```

## Quick Start

### 1. Set Up Supabase (Cloud Database)

1. Go to https://supabase.com and create a free account
2. Create a new project
3. Go to **SQL Editor** and run the contents of `database/supabase-schema.sql`
4. Copy your **Project URL** and **anon key** from Settings → API

### 2. ESP32 Firmware

1. Install [PlatformIO](https://platformio.org/) in VS Code
2. Open `firmware/` folder
3. Edit `config.h`:
   - Set `WIFI_SSID` and `WIFI_PASSWORD`
   - Set `CLOUD_MODE = true`
   - Set `SUPABASE_URL` and `SUPABASE_ANON_KEY`
   - Set `SUPABASE_UNIT_ID` (get from Table Editor → units)
4. Flash to ESP32 via USB

### 3. Admin Dashboard

1. Edit `admin/index.html` with your Supabase credentials
2. Open in browser or deploy to Vercel/Netlify
3. Login with your Supabase auth credentials
4. View live data from all your digester units!

**No Raspberry Pi needed!**

## Hardware Components

| Component | Model | Purpose |
|-----------|-------|---------|
| Microcontroller | ESP32 DevKit | Edge processor, WiFi, sensor I/O |
| Temperature | DS18B20 | Digester temperature |
| pH | pH Sensor Kit | Acid-alkaline balance |
| Gas | MQ-4 / MQ-135 | Methane detection |
| Pressure | BMP280 | Internal pressure |
| Flow | YF-S401 | Gas output flow rate |
| Display | SSD1306 OLED 128×64 | On-site status display |
| IR Remote | VS1838B + remote | Screen navigation |
| Relay | 5V 1-channel | Solenoid valve control |
| Solenoid Valve | 12V NC | Overpressure relief |
| ~~Server~~ | ~~Raspberry Pi~~ | ~~No longer needed (cloud DB)~~ |

## Safe Thresholds

| Parameter | Safe Range | Warning | Critical | Action |
|-----------|-----------|---------|----------|--------|
| Temperature | 25–55 °C | ±5 °C of edge | Outside range | Alert |
| pH | 6.0–8.5 | ±0.3 of edge | Outside range | Alert |
| Gas Level | 0–5000 ppm | >4500 ppm | >5000 ppm | Alert |
| Pressure | 0–180 kPa | 180–200 kPa | >200 kPa | **Open valve** |

## Delimitation Note

> The system operates exclusively on a local WiFi network. Dashboard access requires being physically connected to the same LAN (e.g., inside the karenderya). Off-site or remote access is not available unless VPN or port-forwarding is configured. This is a deliberate design choice to eliminate internet dependency and reduce operational costs.

## Tech Stack

- **Firmware:** Arduino/PlatformIO (C++), ESP32
- **Database:** Supabase (PostgreSQL, cloud-hosted)
- **Auth:** Supabase Auth (built-in)
- **Realtime:** Supabase Realtime (live updates)
- **Dashboard:** Vanilla JS + Chart.js (static HTML, no build step)
- **Hosting:** Vercel/Netlify (free tier) or local

## License

MIT License — see [LICENSE](LICENSE)
