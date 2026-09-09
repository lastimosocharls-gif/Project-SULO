# SULO Cloud Setup Guide

**Smart Utility for Local Organic Waste** — Cloud-based Multi-Unit Monitoring

This guide walks you through setting up SULO with Supabase cloud database, replacing the Raspberry Pi local server.

---

## Architecture Overview

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

**No Raspberry Pi needed!** Each ESP32 sends data directly to Supabase cloud.

---

## Prerequisites

1. **Supabase account** (free tier works) — https://supabase.com
2. **ESP32 with sensors** (same hardware as before)
3. **WiFi with internet access** (any router)
4. **Modern web browser** for admin dashboard

---

## Step 1: Create Supabase Project

1. Go to https://supabase.com and sign up (free)
2. Click **"New Project"**
3. Choose a project name: `sulo-cloud`
4. Set a strong database password (save it!)
5. Choose a region close to you (e.g., Southeast Asia)
6. Click **"Create new project"**
7. Wait 1-2 minutes for setup

---

## Step 2: Set Up Database Schema

1. In your Supabase dashboard, go to **SQL Editor** (left sidebar)
2. Click **"New query"**
3. Copy the contents of `database/supabase-schema.sql`
4. Paste it into the SQL editor
5. Click **"Run"** (or press Ctrl+Enter)
6. Verify: Go to **Table Editor** — you should see:
   - `units`
   - `sensor_readings`
   - `alerts`
   - `digester_status`
   - `admins`

---

## Step 3: Create Admin User

1. In Supabase dashboard, go to **Authentication** → **Users**
2. Click **"Add user"**
3. Enter email and password (e.g., `admin@sulo.local` / `your-password`)
4. Click **"Create user"**

**Note:** The default admin in the schema is a placeholder. Create your real admin through Supabase Auth.

---

## Step 4: Get Your API Credentials

1. Go to **Settings** (gear icon) → **API**
2. Copy these values:
   - **Project URL** (e.g., `https://xyzcompany.supabase.co`)
   - **anon/public key** (long string starting with `eyJ...`)
3. Save these — you'll need them for the ESP32 and dashboard

---

## Step 5: Configure ESP32 Firmware

Edit `firmware/config.h`:

```cpp
// ─── WiFi Configuration ───
#define WIFI_SSID       "YOUR_WIFI_SSID"
#define WIFI_PASSWORD   "YOUR_WIFI_PASSWORD"

// ─── Deployment Mode ───
#define CLOUD_MODE      true    // true = Supabase cloud

// ─── Cloud Configuration (Supabase) ───
#define SUPABASE_URL        "https://xyzcompany.supabase.co"  // Your project URL
#define SUPABASE_ANON_KEY   "your-anon-key-here"               // Your anon key
#define SUPABASE_UNIT_ID    "your-unit-uuid-here"              // This ESP32's unit ID
```

### Getting Your Unit ID

After setting up the database, you need to get the UUID for your digester unit:

1. Go to **Table Editor** → **units**
2. Find your unit (or insert a new one)
3. Copy the `unit_id` UUID
4. Paste it into `SUPABASE_UNIT_ID` in config.h

---

## Step 6: Flash ESP32

1. Open the `firmware/` folder in PlatformIO
2. Make sure `config.h` has your cloud credentials
3. Connect ESP32 via USB
4. Click **Upload** in PlatformIO
5. Open Serial Monitor to verify:
   ```
   [WIFI] Connecting to YOUR_WIFI_SSID
   [WIFI] Connected! IP: 192.168.1.xxx
   [WIFI] Mode: CLOUD (Supabase)
   [WIFI] POST /readings -> 201
   ```

---

## Step 7: Deploy Admin Dashboard

The admin dashboard is a static HTML file — no server needed!

### Option A: Local (for testing)
1. Open `admin/index.html` in your browser
2. Edit the Supabase credentials at the top of the `<script>` tag:
   ```javascript
   const SUPABASE_URL = 'https://xyzcompany.supabase.co';
   const SUPABASE_ANON_KEY = 'your-anon-key-here';
   ```
3. Login with your Supabase auth credentials

### Option B: Free Hosting (recommended)
Deploy to any static hosting:

**Vercel (recommended):**
```bash
cd admin
npx vercel --prod
```

**Netlify:**
```bash
cd admin
npx netlify deploy --prod --dir .
```

**GitHub Pages:**
1. Push `admin/` folder to a GitHub repo
2. Go to Settings → Pages
3. Enable GitHub Pages from the `admin` folder

---

## Step 8: Add More Units

To add another digester unit:

1. Go to **Table Editor** → **units**
2. Click **"Insert row"**
3. Fill in:
   - `name`: "Kitchen Unit B"
   - `location": "Back Kitchen"
   - `description": "Secondary digester"
4. Save and copy the new `unit_id`
5. Flash that unit's ESP32 with the new `SUPABASE_UNIT_ID`

---

## API Endpoints (Supabase REST)

The ESP32 uses Supabase REST API automatically:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/rest/v1/sensor_readings` | Send sensor data |
| POST | `/rest/v1/alerts` | Send alert events |
| GET | `/rest/v1/sensor_readings` | Query readings |
| GET | `/rest/v1/alerts` | Query alerts |
| GET | `/rest/v1/units` | List all units |

---

## Real-time Updates

The admin dashboard subscribes to real-time changes:

- **New sensor readings** → Dashboard updates instantly
- **New alerts** → Alert table refreshes automatically
- **No page refresh needed!**

---

## Security Notes

1. **Change the default admin password** immediately
2. **Never commit API keys** to public repos
3. The `anon` key is safe to use in client-side code (RLS protects data)
4. For production, use **Row Level Security** policies to restrict access
5. Enable **HTTPS** (Supabase provides this by default)

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| ESP32 can't connect to WiFi | Check SSID/password, ensure internet access |
| POST returns 401 | Check `SUPABASE_ANON_KEY` is correct |
| POST returns 400 | Check `SUPABASE_UNIT_ID` exists in `units` table |
| Dashboard shows no data | Verify RLS policies allow SELECT for authenticated users |
| Dashboard login fails | Create user in Supabase Auth dashboard |

---

## Cost

| Component | Cost |
|-----------|------|
| Supabase Free Tier | **$0/month** (500 MB DB, 50K MAUs) |
| ESP32 + Sensors | ~₱3,000-5,000 (one-time) |
| Admin Dashboard Hosting | **$0/month** (Vercel/Netlify free tier) |
| **Total** | **₱0/month** + hardware cost |

---

## Migration from Raspberry Pi

If you're moving from the local Pi setup:

1. Export data from local PostgreSQL (optional)
2. Set up Supabase as above
3. Update ESP32 `config.h` to use cloud credentials
4. Flash ESP32
5. Deploy admin dashboard
6. Shut down Raspberry Pi (no longer needed!)

---

## Next Steps

- [ ] Add push notifications (email/SMS on critical alerts)
- [ ] Add data export (CSV download from dashboard)
- [ ] Add unit management (create/delete units from dashboard)
- [ ] Add user roles (admin vs viewer permissions)
- [ ] Add mobile app (Flutter/React Native)

---

**Need help?** Check the Supabase docs: https://supabase.com/docs
