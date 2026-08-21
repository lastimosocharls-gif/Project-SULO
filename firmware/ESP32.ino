/**
 * SULO — Smart Utility for Local Organic Waste
 * ESP32 Main Firmware
 *
 * Monitors biogas digester conditions via local sensors,
 * displays readings on OLED, communicates with local
 * Laravel backend over WiFi, and actuates fail-safe valve.
 *
 * No internet required — operates entirely on local WiFi network.
 */

#include "config.h"
#include "sensors.h"
#include "display.h"
#include "wifi.h"
#include "failsafe.h"

#include <IRremote.h>

// ─── Timing state ───
static unsigned long lastSensorRead   = 0;
static unsigned long lastDisplayUpdate = 0;
static unsigned long lastServerPost    = 0;

// ─── Current display screen ───
static int currentScreen = SCREEN_OVERVIEW;

// ─── Latest sensor reading ───
static SensorData currentData = {};

// ─── IR remote button codes ───
// (These are example codes — update to match your actual remote)
#define IR_BTN_NEXT     0xFFC23D
#define IR_BTN_PREV     0xFF22DD
#define IR_BTN_MUTE     0xFFA25D
#define IR_BTN_POWER    0xFFE01F

// ─── Forward declaration ───
void handleIRCommand(unsigned long code);

void setup() {
    Serial.begin(115200);
    Serial.println();
    Serial.println("═══════════════════════════════════════");
    Serial.println("  SULO — Biogas Digester Monitor");
    Serial.println("  Local-only mode | No internet needed");
    Serial.println("═══════════════════════════════════════");

    // ── Step 1: Initialise subsystems ──
    display_init();
    sensors_init();
    failsafe_init();
    wifi_init();

    // IR receiver
    IrReceiver.begin(IR_RECEIVER_PIN);

    Serial.println("[BOOT] System ready — entering main loop");
    Serial.println();
}

void loop() {
    unsigned long now = millis();

    // ── Step 2: IR Remote Input ──
    if (IrReceiver.decode()) {
        if (IrReceiver.decodedIRData.decodedRawData != 0) {
            handleIRCommand(IrReceiver.decodedIRData.decodedRawData);
        }
        IrReceiver.resume();
    }

    // ── Step 3: Sensor Polling ──
    if (now - lastSensorRead >= SENSOR_POLL_INTERVAL) {
        lastSensorRead = now;
        currentData = sensors_read();

        // Print to serial for debugging
        Serial.print("[DATA] T=");
        Serial.print(currentData.temperature, 1);
        Serial.print(" pH=");
        Serial.print(currentData.ph, 2);
        Serial.print(" Gas=");
        Serial.print(currentData.gasLevel, 0);
        Serial.print(" P=");
        Serial.print(currentData.pressure, 1);
        Serial.print(" F=");
        Serial.print(currentData.flowRate, 2);
        Serial.print(" | Status=");
        Serial.println(currentData.status == STATUS_NORMAL ? "NORMAL" :
                       currentData.status == STATUS_WARNING ? "WARNING" : "CRITICAL");
    }

    // ── Step 4: Display Update ──
    if (now - lastDisplayUpdate >= DISPLAY_REFRESH_INTERVAL) {
        lastDisplayUpdate = now;
        display_update(currentData, currentScreen);
    }

    // ── Step 5: Fail-Safe Check ──
    failsafe_evaluate(currentData);

    // ── Step 6: Server Communication ──
    if (now - lastServerPost >= SERVER_POST_INTERVAL) {
        lastServerPost = now;

        bool posted = wifi_post_readings(currentData);
        if (!posted) {
            Serial.println("[WIFI] Could not reach local server");
        }

        // If critical, also send alert
        if (currentData.status == STATUS_CRITICAL && posted) {
            if (currentData.valveTriggered) {
                wifi_post_alert(currentData, "overpressure",
                                "critical", "Overpressure detected — solenoid valve actuated");
            } else {
                wifi_post_alert(currentData, "threshold_breach",
                                "critical", "Sensor reading outside safe range");
            }
        } else if (currentData.status == STATUS_WARNING && posted) {
            wifi_post_alert(currentData, "warning",
                            "warning", "Sensor reading approaching threshold");
        }
    }

    // ── WiFi reconnection ──
    if (!wifi_is_connected()) {
        static unsigned long lastReconnect = 0;
        if (now - lastReconnect > 30000) {
            lastReconnect = now;
            Serial.println("[WIFI] Attempting reconnect...");
            wifi_init();
        }
    }
}

// ─── IR Command Handler ───
void handleIRCommand(unsigned long code) {
    switch (code) {
        case IR_BTN_NEXT:
            currentScreen = (currentScreen + 1) % SCREEN_COUNT;
            Serial.print("[IR] Next screen -> ");
            Serial.println(currentScreen);
            break;

        case IR_BTN_PREV:
            currentScreen = (currentScreen - 1 + SCREEN_COUNT) % SCREEN_COUNT;
            Serial.print("[IR] Prev screen -> ");
            Serial.println(currentScreen);
            break;

        case IR_BTN_MUTE:
            Serial.println("[IR] Alert silenced (display only)");
            // Could set a flag to suppress LCD alert flashing
            break;

        case IR_BTN_POWER:
            currentScreen = SCREEN_OVERVIEW;
            Serial.println("[IR] Reset to overview screen");
            break;

        default:
            Serial.print("[IR] Unknown code: 0x");
            Serial.println(code, HEX);
            break;
    }
}
