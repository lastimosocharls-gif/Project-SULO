# Project-SULO

**Smart Utility for Local Organic Waste** — A fully local, offline-capable biogas digester monitoring system.

## Overview

SULO monitors a mini biogas digester in real-time using ESP32-connected sensors. All data is stored locally on a Raspberry Pi via PostgreSQL. A web dashboard is accessible on the local WiFi network — **no internet required**.

## Architecture

```
┌────────────────────────────────────────────────────────────┐
│                    Local WiFi Network (LAN)                │
│                     No Internet Needed                     │
│                                                            │
│  ┌──────────┐   HTTP POST   ┌──────────────────────────┐  │
│  │  ESP32   │ ───────────→  │  Raspberry Pi / Local PC  │  │
│  │  (Edge)  │               │                            │  │
│  └──────────┘               │  Laravel API               │  │
│      ↑                      │  PostgreSQL (local)        │  │
│  Sensors                    │  Web Dashboard             │  │
│  Display                    └──────────────────────────┘  │
│  Solenoid Valve                      ↑                     │
│  IR Remote                    Any LAN device               │
│                              (phone/laptop/PC)             │
└────────────────────────────────────────────────────────────┘
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

### 1. Raspberry Pi Setup (Server)

```bash
# Clone to Pi
scp -r Project-SULO/ pi@raspberrypi:/home/pi/

# SSH into Pi
ssh pi@raspberrypi

# Run setup script (installs PostgreSQL, PHP, Nginx, Laravel)
cd Project-SULO
sudo chmod +x scripts/setup-rpi.sh
sudo ./scripts/setup-rpi.sh
```

Dashboard will be available at: **http://192.168.4.1:8000**

### 2. ESP32 Firmware

1. Install [PlatformIO](https://platformio.org/) in VS Code
2. Open `firmware/` folder
3. Edit `config.h`:
   - Set `WIFI_SSID` and `WIFI_PASSWORD` to match your local WiFi
   - Set `SERVER_HOST` to the Raspberry Pi's IP address
4. Flash to ESP32 via USB

### 3. Local Network

- Connect the Raspberry Pi to a WiFi router (or use its built-in WiFi as an AP)
- Connect the ESP32 to the same network
- The ESP32 will automatically start sending data to the Pi
- Open the dashboard on any device connected to the same network

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
| Server | Raspberry Pi 4/5 | Laravel + PostgreSQL host |

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
- **Backend:** Laravel 11 (PHP), REST API
- **Database:** PostgreSQL (local, self-hosted)
- **Dashboard:** Blade + Chart.js (vanilla JS, no build step)
- **Network:** Local WiFi only (no cloud, no internet)

## License

MIT License — see [LICENSE](LICENSE)
