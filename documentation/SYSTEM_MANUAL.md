# SULO System Manual
## Smart Utility for Local Organic Waste

---

# Table of Contents

1. Project Overview
2. System Requirements
3. Hardware Components
4. Wiring Guide
5. Software Setup
6. Configuration
7. System Deployment
8. Operating Guide
9. Dashboard Guide
10. IR Remote Control
11. Fail-Safe System
12. Troubleshooting
13. Maintenance Schedule
14. Safety Guidelines
15. Technical Reference
16. API Reference

---

# 1. Project Overview

SULO (Smart Utility for Local Organic Waste) is a fully local, offline-capable biogas digester monitoring system designed for small-scale karenderya operations. It monitors digester conditions in real-time using ESP32-connected sensors and provides an on-site web dashboard.

## Key Features

- Real-time monitoring of temperature, pH, gas level, pressure, and flow rate
- Automatic fail-safe: solenoid valve opens on overpressure to prevent accidents
- No internet required: operates entirely on a local WiFi network
- On-site display: OLED screen shows live readings without needing a phone
- Remote control: navigate display screens with a handheld IR remote
- Local dashboard: web-based monitoring from any device on the same network
- Alert system: visual and logged alerts when thresholds are breached

## Architecture

```
ESP32 (Edge Device)          Raspberry Pi (Local Server)
+------------------+        +--------------------------+
| Sensors          |        | Laravel API              |
| OLED Display     | -----> | PostgreSQL Database      |
| IR Remote        | WiFi   | Web Dashboard            |
| Solenoid Valve   |        +--------------------------+
+------------------+
```

---

# 2. System Requirements

## Hardware

| Component | Specification | Qty |
|-----------|--------------|-----|
| Microcontroller | ESP32 DevKit V1 | 1 |
| Temperature Sensor | DS18B20 (waterproof) | 1 |
| pH Sensor | Analog pH Sensor Kit (BNC) | 1 |
| Gas Sensor | MQ-4 or MQ-135 | 1 |
| Pressure Sensor | BMP280 (I2C) | 1 |
| Flow Sensor | YF-S401 (half-inch) | 1 |
| Display | SSD1306 OLED 128x64 (I2C) | 1 |
| IR Receiver | VS1838B | 1 |
| IR Remote | Any NEC protocol remote | 1 |
| Relay Module | 5V 1-channel | 1 |
| Solenoid Valve | 12V normally-closed | 1 |
| Server | Raspberry Pi 4/5 (4GB+) | 1 |
| WiFi Router | Any (creates local network) | 1 |

## Software

| Software | Version | Purpose |
|----------|---------|---------|
| PlatformIO | Latest | ESP32 firmware development |
| PHP | 8.2+ | Backend server |
| PostgreSQL | 14+ | Database |
| Nginx | Latest | Web server |
| Composer | Latest | PHP dependency manager |

---

# 3. Hardware Components

## 3.1 ESP32 Microcontroller

The ESP32 is the brain of the system. It reads all sensors, evaluates thresholds, controls the display, communicates with the local server, and actuates the fail-safe valve.

- Dual-core 240 MHz processor
- Built-in WiFi and Bluetooth
- 34 GPIO pins
- 12-bit ADC (analog-to-digital converter)
- Operating voltage: 3.3V (accepts 5V via USB)

## 3.2 Sensors

### DS18B20 Temperature Sensor
- Type: Digital (OneWire protocol)
- Range: -55C to +125C
- Accuracy: +/- 0.5C
- Purpose: Monitor digester temperature
- Note: Use waterproof version for submersion

### pH Sensor
- Type: Analog (BNC connector)
- Range: 0-14 pH
- Accuracy: +/- 0.1 pH
- Purpose: Monitor acid-alkaline balance
- Note: Calibrate with buffer solutions (pH 4.0 and pH 7.0)

### MQ-4 / MQ-135 Gas Sensor
- Type: Analog
- Range: 0-10000 ppm
- Purpose: Detect methane/biogas concentration
- Warm-up time: 24-48 hours first use, 2 minutes subsequent

### BMP280 Pressure Sensor
- Type: Digital (I2C)
- Range: 300-1100 hPa
- Purpose: Monitor digester internal pressure
- Fail-safe trigger: Activates solenoid valve at >200 kPa

### YF-S401 Flow Sensor
- Type: Pulse output (interrupt-driven)
- Range: 0.3-6 L/min
- Purpose: Measure gas output flow rate
- Note: Install in vertical position for accuracy

## 3.3 SSD1306 OLED Display
- Resolution: 128x64 pixels
- Interface: I2C (4 pins: VCC, GND, SDA, SCL)
- Purpose: Display live sensor readings and status on-site

## 3.4 Solenoid Valve
- Type: 12V normally-closed
- Purpose: Vent excess gas when overpressure is detected
- Control: Via relay module connected to ESP32 GPIO

## 3.5 Raspberry Pi (Server)
- Model: Raspberry Pi 4 (4GB) or Pi 5 recommended
- OS: Raspberry Pi OS (Debian-based)
- Purpose: Hosts Laravel API, PostgreSQL database, and web dashboard

---

# 4. Wiring Guide

## 4.1 Pin Assignments

| Component | ESP32 Pin | Type |
|-----------|-----------|------|
| DS18B20 (Temperature) | GPIO 4 | Digital (OneWire) |
| pH Sensor | GPIO 34 | Analog Input |
| Gas Sensor | GPIO 35 | Analog Input |
| BMP280 SDA (Pressure) | GPIO 21 | I2C Data |
| BMP280 SCL (Pressure) | GPIO 22 | I2C Clock |
| Flow Sensor | GPIO 25 | Digital (Interrupt) |
| OLED SDA | GPIO 21 | I2C Data (shared) |
| OLED SCL | GPIO 22 | I2C Clock (shared) |
| IR Receiver | GPIO 15 | Digital Input |
| Relay (Solenoid) | GPIO 26 | Digital Output |

## 4.2 I2C Bus (Shared: OLED + BMP280)

Both I2C devices share the same bus. BMP280 uses address 0x76, OLED uses 0x3C.

```
ESP32 GPIO 21 (SDA) ----+---- OLED SDA
                         +---- BMP280 SDA

ESP32 GPIO 22 (SCL) ----+---- OLED SCL
                         +---- BMP280 SCL
```

## 4.3 Analog Sensors

ESP32 ADC reads 0-3.3V. If sensor outputs 5V, use a voltage divider.

```
pH Sensor (BNC) ----> Voltage divider ----> GPIO 34
MQ-4 Gas Sensor ----> Voltage divider ----> GPIO 35
```

## 4.4 Relay + Solenoid

```
ESP32 GPIO 26 ----> IN (Relay Module)
Relay COM ----> 12V Power (+)
Relay NO  ----> Solenoid Valve (+)
Solenoid Valve (-) ----> 12V Power (-)
```

---

# 5. Software Setup

## 5.1 Raspberry Pi Setup

### Automated

```bash
git clone https://github.com/lastimosocharls-gif/Project-SULO.git
cd Project-SULO
chmod +x scripts/setup-rpi.sh
./setup-rpi.sh
```

### What the script does

- Installs PHP, PostgreSQL, Nginx
- Creates database and user
- Deploys Laravel and runs migrations
- Downloads Chart.js for offline dashboard
- Configures Nginx on port 8000

## 5.2 ESP32 Firmware

### Install PlatformIO

1. Open VS Code
2. Press Ctrl+Shift+X (Extensions)
3. Search "PlatformIO IDE"
4. Click Install

### Flash Firmware

1. Open `firmware/` folder in VS Code
2. Edit `firmware/config.h` with your WiFi credentials
3. Connect ESP32 via USB
4. Click PlatformIO alien icon then Upload

---

# 6. Configuration

## 6.1 WiFi (config.h)

```cpp
#define WIFI_SSID       "YourNetworkName"
#define WIFI_PASSWORD   "YourPassword"
#define SERVER_HOST     "192.168.4.1"
```

## 6.2 Thresholds (config.h)

```cpp
#define TEMP_MIN_C          25.0f
#define TEMP_MAX_C          55.0f
#define PH_MIN              6.0f
#define PH_MAX              8.5f
#define GAS_MAX_PPM         5000.0f
#define PRESSURE_MAX_KPA    200.0f   // Valve opens
#define PRESSURE_ALERT_KPA  180.0f   // Warning
```

---

# 7. Operating Guide

## 7.1 Startup

1. Power on Raspberry Pi (wait 30s)
2. Power on ESP32
3. OLED shows "SULO System Starting..."
4. Live readings appear within 10 seconds

## 7.2 OLED Display Screens

| Screen | Content |
|--------|---------|
| Overview | All sensor values |
| Temperature | Large temp + safe range |
| pH | Large pH + safe range |
| Gas | Large gas level + max |
| Pressure | Large pressure + valve status |
| Alerts | List of active conditions |

## 7.3 Status Indicators

- [NORMAL] — All safe (green)
- [WARNING] — Approaching limits (yellow)
- [CRITICAL] — Outside range (red)

---

# 8. Dashboard Guide

## 8.1 Access

Connect any device to the same WiFi, open browser, go to:

```
http://192.168.4.1:8000
```

## 8.2 Sections

- Header: Status badge (Normal/Warning/Critical)
- Sensor Cards: Live Temperature, pH, Gas, Pressure, Flow, Valve
- Charts: 24-hour Temperature+Pressure and pH+Gas graphs
- Alert Log: Table of recent alerts with time, type, severity

## 8.3 Auto-Refresh

Dashboard updates every 5 seconds automatically.

---

# 9. IR Remote Control

| Button | Function |
|--------|----------|
| Next | Next display screen |
| Previous | Previous display screen |
| Power | Return to Overview |
| Mute | Silence alert display |

Button codes are in `ESP32.ino`. Update them to match your remote.

---

# 10. Fail-Safe System

## How It Works

1. Pressure exceeds 200 kPa
2. ESP32 detects overpressure
3. GPIO 26 goes HIGH
4. Relay activates, solenoid valve OPENS
5. Gas vents to atmosphere
6. Pressure drops below 170 kPa
7. Valve CLOSES

## Important

- The fail-safe operates independently of WiFi/server
- Never disable the fail-safe in production
- Keep the solenoid valve accessible for manual override
- Test with valve disconnected from gas lines

---

# 11. Troubleshooting

| Problem | Cause | Solution |
|---------|-------|----------|
| ESP32 won't connect WiFi | Wrong credentials | Check config.h |
| Temperature shows -127 | Sensor not connected | Check GPIO 4 wiring |
| pH always 0 or 14 | Analog wiring issue | Check GPIO 34 |
| OLED blank | Wrong I2C address | Verify address 0x3C |
| Dashboard won't load | Nginx not running | Restart nginx service |
| "Server unreachable" | Pi offline | Check Pi power/network |
| IR remote not working | Wrong codes | Check Serial Monitor |

---

# 12. Maintenance

## Weekly
- Check dashboard for alerts
- Verify sensor readings are reasonable

## Monthly
- Calibrate pH sensor with buffer solutions
- Clean gas sensor surface
- Test solenoid valve operation

## Database Cleanup

Delete old records:
```
curl -X DELETE "http://localhost:8000/api/readings/prune?days=90"
```

---

# 13. Safety Guidelines

1. Never disable the fail-safe system
2. Keep solenoid valve accessible for manual override
3. Use waterproof sensors for digester submersion
4. Keep electronics away from water and gas (sealed enclosures)
5. Biogas is flammable — ensure proper ventilation
6. Never block the relief valve
7. Use proper wiring — no exposed connections near gas

## Biogas Composition Warning

- 50-70% Methane (CH4) — FLAMMABLE
- 30-50% Carbon Dioxide (CO2) — Asphyxiant
- Trace H2S — Toxic
- Always ensure adequate ventilation

---

# 14. API Reference

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/readings | ESP32 sends data |
| POST | /api/alerts | ESP32 sends alert |
| GET | /api/readings | Dashboard readings |
| GET | /api/readings/latest | Latest reading |
| GET | /api/readings/history?hours=24 | Hourly averages |
| GET | /api/alerts | Alert log |
| PATCH | /api/alerts/{id}/acknowledge | Acknowledge alert |
| GET | /api/status | Current status |
| DELETE | /api/readings/prune?days=90 | Cleanup old data |

---

# Appendix: File Structure

```
Project-SULO/
+-- firmware/          ESP32 code
+-- backend/           Laravel API + Dashboard
+-- scripts/           Raspberry Pi setup
+-- documentation/     Architecture + Manual
```

---

*Version 1.0 | August 2026*
