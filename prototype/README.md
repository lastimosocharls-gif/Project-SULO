# SULO Prototype

**Smart Utility for Local Organic Waste** — Interactive prototype demonstrating the cloud-based multi-unit monitoring system.

## What's Included

### 1. `index.html` — Admin Dashboard Prototype
A fully functional admin dashboard that works **entirely in the browser** with no backend needed.

**Features:**
- ✅ Login page (use any credentials)
- ✅ Multi-unit support (3 simulated digester units)
- ✅ Real-time sensor data (updates every 2 seconds)
- ✅ Live charts (temperature, pressure, pH, gas level)
- ✅ Alert system with acknowledge functionality
- ✅ Unit selector (view all or drill into one unit)
- ✅ Pause/Resume simulation
- ✅ Statistics bar (units, alerts, readings)

### 2. `esp32-simulator.html` — ESP32 Firmware Simulator
Simulates the ESP32 microcontroller sending data to Supabase cloud.

**Features:**
- ✅ Serial monitor output (like Arduino IDE)
- ✅ Live sensor readings display
- ✅ Simulated HTTP POST requests to Supabase
- ✅ Alert and critical event simulation
- ✅ Visual representation of firmware config.h

## How to Run

### Option 1: Direct Browser (Easiest)
1. Open `index.html` in any modern browser
2. Click "Sign In" (use any email/password)
3. Explore the dashboard!

### Option 2: Local Server (Recommended)
```bash
# Navigate to prototype folder
cd prototype

# Start a simple HTTP server
# Python 3:
python -m http.server 8080

# Or Node.js:
npx serve .

# Or PHP:
php -S localhost:8080
```
Then open `http://localhost:8080`

### Option 3: ESP32 Simulator
1. Open `esp32-simulator.html` in your browser
2. Click "▶ Start" to begin simulation
3. Watch the serial monitor output
4. Try "Simulate Alert" and "Simulate Critical" buttons

## Demo Flow

1. **Login** → Click "Sign In" on the login page
2. **View Units** → See 3 digester units with live status cards
3. **Select a Unit** → Click any unit card or use the dropdown
4. **Watch Data** → See real-time sensor values updating
5. **View Charts** → Temperature, pressure, pH, gas level over 24h
6. **Manage Alerts** → Acknowledge alerts, see severity levels
7. **Pause/Resume** → Control the simulation with the Pause button

## Simulated Units

| Unit | Location | Characteristics |
|------|----------|-----------------|
| Karenderya Unit A | Main Kitchen | Normal operation, steady readings |
| Karenderya Unit B | Annex Kitchen | Lower gas output, stable pH |
| Bakery Digester | Panaderia | Higher temperature, higher gas |

## What This Demonstrates

This prototype shows how the real SULO system would work:

1. **ESP32** collects sensor data (temperature, pH, gas, pressure, flow)
2. **ESP32** sends data via HTTP POST to **Supabase cloud**
3. **Supabase** stores data in PostgreSQL database
4. **Admin Dashboard** displays real-time data from all units
5. **Alerts** are generated when thresholds are exceeded
6. **Multi-unit** support allows monitoring multiple digesters

## No Backend Required

This prototype runs **entirely in the browser**:
- No Node.js server needed
- No PHP/Laravel needed
- No Raspberry Pi needed
- No Supabase account needed (data is simulated)

Perfect for:
- Capstone project demonstrations
- Stakeholder presentations
- Testing the UI/UX before real deployment
- Understanding the data flow

## Next Steps

After exploring the prototype:

1. **Create Supabase project** → Follow `CLOUD_SETUP.md`
2. **Set up database** → Run `database/supabase-schema.sql`
3. **Flash real ESP32** → Update `firmware/config.h` with your credentials
4. **Deploy admin dashboard** → Use `admin/index.html`
5. **Connect real sensors** → Start collecting real data!

## Files

```
prototype/
├── README.md                 # This file
├── index.html                # Admin dashboard prototype
└── esp32-simulator.html      # ESP32 firmware simulator
```

---

**Note:** This is a demonstration prototype. The real system uses Supabase for persistent cloud storage and real ESP32 hardware for sensor data collection.
