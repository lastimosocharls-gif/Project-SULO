#include "wifi.h"
#include "config.h"

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

void wifi_init() {
    Serial.print("[WIFI] Connecting to ");
    Serial.println(WIFI_SSID);

    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    unsigned long start = millis();
    while (WiFi.status() != WL_CONNECTED && millis() - start < WIFI_TIMEOUT_MS) {
        delay(500);
        Serial.print(".");
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println();
        Serial.print("[WIFI] Connected! IP: ");
        Serial.println(WiFi.localIP());
    } else {
        Serial.println();
        Serial.println("[WIFI] Connection failed — will retry");
    }
}

bool wifi_is_connected() {
    return WiFi.status() == WL_CONNECTED;
}

bool wifi_post_readings(const SensorData &data) {
    if (!wifi_is_connected()) return false;

    HTTPClient http;
    http.begin(API_POST_READINGS);
    http.addHeader("Content-Type", "application/json");
    http.setTimeout(5000);

    // Build JSON payload (ArduinoJson v7 API)
    JsonDocument doc;
    doc["temperature"] = data.temperature;
    doc["ph"]          = data.ph;
    doc["gas_level"]   = data.gasLevel;
    doc["pressure"]    = data.pressure;
    doc["flow_rate"]   = data.flowRate;
    doc["status"]      = data.status;
    doc["valve_open"]  = data.valveTriggered;

    String payload;
    serializeJson(doc, payload);

    int code = http.POST(payload);
    http.end();

    if (code > 0) {
        Serial.print("[WIFI] POST /readings -> ");
        Serial.println(code);
        return code == 200 || code == 201;
    } else {
        Serial.print("[WIFI] POST failed: ");
        Serial.println(http.errorToString(code));
        return false;
    }
}

bool wifi_post_alert(const SensorData &data, const char *alertType,
                     const char *severity, const char *message) {
    if (!wifi_is_connected()) return false;

    HTTPClient http;
    http.begin(API_POST_ALERTS);
    http.addHeader("Content-Type", "application/json");
    http.setTimeout(5000);

    // Build JSON payload (ArduinoJson v7 API)
    JsonDocument doc;
    doc["temperature"] = data.temperature;
    doc["ph"]          = data.ph;
    doc["gas_level"]   = data.gasLevel;
    doc["pressure"]    = data.pressure;
    doc["flow_rate"]   = data.flowRate;
    doc["alert_type"]  = alertType;
    doc["severity"]    = severity;
    doc["message"]     = message;

    String payload;
    serializeJson(doc, payload);

    int code = http.POST(payload);
    http.end();

    if (code > 0) {
        Serial.print("[WIFI] POST /alerts -> ");
        Serial.println(code);
        return code == 200 || code == 201;
    } else {
        Serial.print("[WIFI] Alert POST failed: ");
        Serial.println(http.errorToString(code));
        return false;
    }
}
