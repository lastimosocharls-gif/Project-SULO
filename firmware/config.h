#ifndef CONFIG_H
#define CONFIG_H

// ─── WiFi Configuration ───
// Supports both local network and internet (for cloud mode)
#define WIFI_SSID       "YOUR_WIFI_SSID"
#define WIFI_PASSWORD   "YOUR_WIFI_PASSWORD"
#define WIFI_TIMEOUT_MS 15000

// ─── Deployment Mode ───
// Set to CLOUD_MODE for Supabase, or LOCAL_MODE for Raspberry Pi
#define CLOUD_MODE      true    // true = Supabase cloud, false = local Laravel

// ─── Cloud Configuration (Supabase) ───
// Get these from your Supabase project settings
#define SUPABASE_URL        "https://your-project.supabase.co"
#define SUPABASE_ANON_KEY   "your-anon-key-here"
#define SUPABASE_UNIT_ID    "your-unit-uuid-here"   // This ESP32's unit ID

// ─── Local Server Configuration (Legacy) ───
// Only used when CLOUD_MODE is false
#define SERVER_HOST     "192.168.4.1"
#define SERVER_PORT     8000
#define SERVER_URL      "http://" SERVER_HOST ":" SERVER_PORT

// ─── API Endpoints ───
#if CLOUD_MODE
    // Supabase REST API endpoints
    #define API_POST_READINGS  SUPABASE_URL "/rest/v1/sensor_readings"
    #define API_POST_ALERTS    SUPABASE_URL "/rest/v1/alerts"
    #define API_POST_STATUS    SUPABASE_URL "/rest/v1/digester_status"
#else
    // Local Laravel API endpoints
    #define API_POST_READINGS  SERVER_URL "/api/readings"
    #define API_POST_ALERTS    SERVER_URL "/api/alerts"
#endif

// ─── Sensor Pin Assignments ───
#define TEMP_SENSOR_PIN     4     // DS18B20 (OneWire bus)
#define PH_SENSOR_PIN       34    // pH sensor analog input
#define GAS_SENSOR_PIN      35    // MQ-4 / MQ-135 analog input
#define PRESSURE_SENSOR_SCL 22    // BMP280 I2C
#define PRESSURE_SENSOR_SDA 21    // BMP280 I2C
#define FLOW_SENSOR_PIN     25    // YF-S401 pulse input

// ─── Relay / Solenoid Valve ───
#define RELAY_PIN           26    // Solenoid valve relay

// ─── IR Receiver ───
#define IR_RECEIVER_PIN     15    // IR receiver data pin

// ─── Display (LCD / OLED) ───
#define DISPLAY_SDA         21    // I2C SDA for OLED
#define DISPLAY_SCL         22    // I2C SCL for OLED

// ─── Timing Intervals (ms) ───
#define SENSOR_POLL_INTERVAL    10000   // 10 seconds
#define DISPLAY_REFRESH_INTERVAL 1000   // 1 second
#define SERVER_POST_INTERVAL    15000   // 15 seconds

// ─── Safe Thresholds ───
#define TEMP_MIN_C          25.0f
#define TEMP_MAX_C          55.0f
#define PH_MIN              6.0f
#define PH_MAX              8.5f
#define GAS_MAX_PPM         5000.0f
#define PRESSURE_MAX_KPA    200.0f     // Overpressure threshold (kPa)
#define PRESSURE_ALERT_KPA  180.0f     // Warning threshold

// ─── Status Codes ───
#define STATUS_NORMAL   0
#define STATUS_WARNING  1
#define STATUS_CRITICAL 2

// ─── Display Screens ───
#define SCREEN_OVERVIEW     0
#define SCREEN_TEMPERATURE  1
#define SCREEN_PH           2
#define SCREEN_GAS          3
#define SCREEN_PRESSURE     4
#define SCREEN_ALERTS       5
#define SCREEN_COUNT        6

#endif // CONFIG_H
