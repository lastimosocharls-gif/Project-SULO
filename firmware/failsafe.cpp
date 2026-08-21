#include "failsafe.h"
#include "config.h"

static bool valveOpen = false;

void failsafe_init() {
    pinMode(RELAY_PIN, OUTPUT);
    digitalWrite(RELAY_PIN, LOW); // Valve closed by default
    valveOpen = false;
    Serial.println("[FAILSAFE] Initialised — valve CLOSED");
}

void failsafe_evaluate(const SensorData &data) {
    if (data.pressure > PRESSURE_MAX_KPA) {
        if (!valveOpen) {
            digitalWrite(RELAY_PIN, HIGH); // Open valve
            valveOpen = true;
            Serial.println("[FAILSAFE] CRITICAL — Overpressure detected!");
            Serial.println("[FAILSAFE] Solenoid valve OPENED to vent gas");
        }
    } else if (data.pressure < (PRESSURE_ALERT_KPA - 10.0f)) {
        // Close valve only when pressure drops safely below warning level
        if (valveOpen) {
            digitalWrite(RELAY_PIN, LOW); // Close valve
            valveOpen = false;
            Serial.println("[FAILSAFE] Pressure normalised — valve CLOSED");
        }
    }
}
