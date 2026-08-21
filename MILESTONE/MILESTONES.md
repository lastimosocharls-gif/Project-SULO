# SULO Project Milestones

**SULO (Smart Utility for Local Organic Waste)** — IoT-based miniaturized biogas monitoring and fail-safe system.

This file tracks the major development milestones for the project.

---

## Milestone 1 — Planning & Requirements
**Goal:** Define scope, requirements, and constraints before any build work starts.

- [x] Finalize functional & non-functional requirements
- [x] Confirm miniaturized prototype scope (vessel size, demo-only gas yield)
- [x] Finalize sensor thresholds (temp, pH, pressure, gas composition)
- [ ] Draft circuit diagram (sensors, ESP32, display, valve, remote)
- [x] Design PostgreSQL database schema (sensor_readings, alerts, digester_status)

---

## Milestone 2 — System & Software Architecture
**Goal:** Lock in the architecture before writing firmware or backend code.

- [x] Finalize local-only architecture (Edge layer -> Local Server, no cloud/internet)
- [x] Confirm component list: ESP32, sensors, OLED display, solenoid valve, IR remote
- [x] Confirm local stack: Laravel backend + PostgreSQL (self-hosted on Raspberry Pi)
- [x] Document data flow from sensors -> microcontroller -> local database -> LAN dashboard

---

## Milestone 3 — Hardware & Sensor Integration
**Goal:** Get all sensors wired, reading, and calibrated on the miniaturized vessel.

- [ ] Assemble miniature digester vessel with sensor ports
- [ ] Wire and test DS18B20 (temperature)
- [ ] Wire and test pH sensor
- [ ] Wire and test MQ-4 / MQ-135 (gas composition)
- [ ] Wire and test YF-S401 (flow)
- [ ] Wire and test BMP280 (pressure)
- [ ] Calibrate all sensors against reference instruments

---

## Milestone 4 — Display & Remote Control
**Goal:** Give the system an on-site, no-phone-needed interface.

- [ ] Wire OLED display (I2C) to ESP32
- [x] Build display screens (live readings, alert screen, summary)
- [ ] Wire IR receiver (VS1838B) to ESP32
- [x] Program remote button mapping (next/prev screen, silence alert)
- [ ] Test remote control range and reliability

---

## Milestone 5 — Fail-Safe & Automation Logic
**Goal:** Automate safety response without manual intervention.

- [x] Implement threshold-checking logic in firmware
- [ ] Wire and test solenoid valve via relay
- [x] Trigger automatic valve release on overpressure
- [x] Trigger alerts on pH acidification / abnormal readings
- [ ] Simulate failure conditions to validate fail-safe response time

---

## Milestone 6 — Local Dashboard & Database
**Goal:** Enable on-site monitoring through a locally-hosted web dashboard (no internet required).

- [x] Set up Laravel backend with PostgreSQL (self-hosted on Raspberry Pi)
- [x] Build API endpoint to receive sensor data (HTTP POST from ESP32)
- [x] Implement WiFi connectivity + retry logic on the ESP32
- [x] Build dashboard UI: live values, historical charts, alert log
- [ ] Deploy backend on Raspberry Pi with Nginx
- [ ] Set static IP on Raspberry Pi for LAN access
- [ ] Verify dashboard accessible from any device on same WiFi network

---

## Milestone 7 — System Integration & Testing
**Goal:** Confirm every component works together end-to-end on the local network.

- [ ] Functional testing (sensor accuracy, data logging, alerts)
- [ ] Constraint & fail-safe testing (checklist from SAD document)
- [ ] Calibration validation testing
- [ ] End-to-end test: sensor reading -> display update -> local DB sync -> dashboard
- [ ] WiFi connectivity loss / recovery test (retry buffer behavior)
- [ ] LAN-only access test (confirm no internet dependency)

---

## Milestone 8 — Deployment, Demo & Documentation
**Goal:** Finalize the prototype for defense/demo day.

- [ ] Assemble final enclosure for demo unit
- [ ] Prepare live demo script (waste input -> readings -> fail-safe -> dashboard)
- [ ] Finalize SAD document (Requirements, Design, Testing, Validation)
- [ ] Finalize hardware components list
- [ ] Record backup demo video (in case of live-demo failure)
- [ ] Submit final capstone deliverables

---

## Suggested GitHub Setup

1. Go to your repo -> Issues -> Milestones -> New milestone
2. Create one milestone per section above
3. Create a GitHub Issue for each checklist item and assign it to the matching milestone
4. Use labels like `hardware`, `firmware`, `local-server`, `testing`, `docs` to filter issues
