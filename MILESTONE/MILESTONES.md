# SULO Project Milestones

**SULO (Smart Utility for Local Organic Waste)** — IoT-based miniaturized biogas monitoring and fail-safe system.

This file tracks the major development milestones for the project. Each milestone below can be created directly as a **GitHub Milestone** (Issues → Milestones → New milestone), with the checklist items added as individual issues and linked to it.

---

## Milestone 1 — Planning & Requirements
**Goal:** Define scope, requirements, and constraints before any build work starts.

- [ ] Finalize functional & non-functional requirements
- [ ] Confirm miniaturized prototype scope (vessel size, demo-only gas yield)
- [ ] Finalize sensor thresholds (temp, pH, pressure, gas composition)
- [ ] Draft circuit diagram (sensors, ESP32, display, valve, remote)
- [ ] Design PostgreSQL database schema (sensor_readings, alerts, digester_status)

---

## Milestone 2 — System & Software Architecture
**Goal:** Lock in the architecture before writing firmware or backend code.

- [ ] Finalize three-tier architecture (Edge layer → Cloud layer)
- [ ] Confirm component list: ESP32, sensors, OLED display, solenoid valve, IR remote
- [ ] Confirm cloud stack: Laravel backend + PostgreSQL (no local SD backup)
- [ ] Document data flow from sensors → microcontroller → cloud dashboard

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
- [ ] Build display screens (live readings, alert screen, summary)
- [ ] Wire IR receiver (TSOP1738) to ESP32
- [ ] Program remote button mapping (next/prev screen, silence alert)
- [ ] Test remote control range and reliability

---

## Milestone 5 — Fail-Safe & Automation Logic
**Goal:** Automate safety response without manual intervention.

- [ ] Implement threshold-checking logic in firmware
- [ ] Wire and test solenoid valve via relay
- [ ] Trigger automatic valve release on overpressure
- [ ] Trigger alerts on pH acidification / abnormal readings
- [ ] Simulate failure conditions to validate fail-safe response time

---

## Milestone 6 — Cloud Dashboard & Database
**Goal:** Enable remote monitoring through a web-based dashboard.

- [ ] Set up Laravel backend with PostgreSQL
- [ ] Build API endpoint to receive sensor data (HTTP POST from ESP32)
- [ ] Implement WiFi connectivity + retry logic on the ESP32
- [ ] Build dashboard UI: live values, historical charts, alert log
- [ ] Add authentication for operator/admin access

---

## Milestone 7 — System Integration & Testing
**Goal:** Confirm every component works together end-to-end.

- [ ] Functional testing (login, sensor accuracy, data logging, alerts)
- [ ] Constraint & fail-safe testing (checklist from SAD document)
- [ ] Calibration validation testing
- [ ] End-to-end test: sensor reading → display update → cloud sync
- [ ] Connectivity loss / recovery test (retry buffer behavior)

---

## Milestone 8 — Deployment, Demo & Documentation
**Goal:** Finalize the prototype for defense/demo day.

- [ ] Assemble final enclosure for demo unit
- [ ] Prepare live demo script (waste input → readings → fail-safe → dashboard)
- [ ] Finalize SAD document (Requirements, Design, Testing, Validation)
- [ ] Finalize hardware components list
- [ ] Record backup demo video (in case of live-demo failure)
- [ ] Submit final capstone deliverables

---

## Suggested GitHub Setup

1. Go to your repo → **Issues** → **Milestones** → **New milestone**
2. Create one milestone per section above (e.g. "Milestone 3 — Hardware & Sensor Integration")
3. Create a GitHub Issue for each checklist item and assign it to the matching milestone
4. Use labels like `hardware`, `firmware`, `cloud`, `testing`, `docs` to filter issues by area