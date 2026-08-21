#include "sensors.h"
#include "config.h"

#include <OneWire.h>
#include <DallasTemperature.h>
#include <Adafruit_BMP280.h>

// ─── Sensor instances ───
static OneWire oneWire(TEMP_SENSOR_PIN);
static DallasTemperature tempSensor(&oneWire);
static Adafruit_BMP280 bmp;

// ─── Flow sensor ───
static volatile unsigned long pulseCount = 0;
static unsigned long lastFlowCheck = 0;
static const float FLOW_FACTOR = 7.5f; // pulses per litre (YF-S401)

void IRAM_ATTR flowPulseISR() {
    pulseCount++;
}

// ─── pH calibration constants ───
static const float PH_OFFSET = 0.0f;
static const float PH_SCALE  = 3.5f;

void sensors_init() {
    // Temperature
    tempSensor.begin();

    // Pressure (BMP280)
    if (!bmp.begin(0x76)) {
        Serial.println("[SENSOR] BMP280 not found — check wiring");
    } else {
        bmp.setSampling(Adafruit_BMP280::MODE_NORMAL,
                        Adafruit_BMP280::SAMPLING_X2,
                        Adafruit_BMP280::SAMPLING_X16,
                        Adafruit_BMP280::FILTER_X16,
                        Adafruit_BMP280::STANDBY_MS_500);
    }

    // Flow sensor (interrupt-driven)
    pinMode(FLOW_SENSOR_PIN, INPUT_PULLUP);
    attachInterrupt(digitalPinToInterrupt(FLOW_SENSOR_PIN), flowPulseISR, RISING);
    lastFlowCheck = millis();

    // Gas sensor (analog)
    pinMode(GAS_SENSOR_PIN, INPUT);

    // pH sensor (analog)
    pinMode(PH_SENSOR_PIN, INPUT);

    Serial.println("[SENSOR] All sensors initialised");
}

SensorData sensors_read() {
    SensorData data = {};

    // ── Temperature (with error check) ──
    tempSensor.requestTemperatures();
    float rawTemp = tempSensor.getTempCByIndex(0);
    // DS18B20 returns -127.0 on read failure or 85.0 on power-on default
    if (rawTemp == DEVICE_DISCONNECTED_C || rawTemp < -40.0f || rawTemp > 125.0f) {
        Serial.println("[SENSOR] Temperature read error — using last known value");
        data.temperature = 0.0f; // Will show as "0.0 C" — operator can see sensor fault
    } else {
        data.temperature = rawTemp;
    }

    // ── pH (voltage -> pH via calibration) ──
    int phRaw = analogRead(PH_SENSOR_PIN);
    float phVoltage = (phRaw / 4095.0f) * 3.3f;
    data.ph = (phVoltage * PH_SCALE) + PH_OFFSET;

    // ── Gas level ──
    int gasRaw = analogRead(GAS_SENSOR_PIN);
    data.gasLevel = (gasRaw / 4095.0f) * 10000.0f; // rough ppm estimate

    // ── Pressure ──
    data.pressure = bmp.readPressure() / 1000.0f; // Pa -> kPa

    // ── Flow rate ──
    noInterrupts();
    unsigned long pulses = pulseCount;
    pulseCount = 0;
    interrupts();

    unsigned long now = millis();
    float elapsed = (now - lastFlowCheck) / 1000.0f;
    lastFlowCheck = now;

    if (elapsed > 0.0f) {
        // BUG FIX: Cast pulseCount to float to avoid integer division truncation
        float litresPerSec = ((float)pulses / FLOW_FACTOR) / elapsed;
        data.flowRate = litresPerSec * 60.0f; // -> L/min
    } else {
        data.flowRate = 0.0f;
    }

    // ── Evaluate thresholds ──
    data.valveTriggered = false;
    sensors_evaluate_thresholds(data);

    return data;
}

bool sensors_evaluate_thresholds(SensorData &data) {
    uint8_t maxStatus = STATUS_NORMAL;

    auto check = [&](float value, float minVal, float maxVal) {
        if (value < minVal || value > maxVal) {
            maxStatus = STATUS_CRITICAL;
        } else if (value < minVal + (maxVal - minVal) * 0.1f ||
                   value > maxVal - (maxVal - minVal) * 0.1f) {
            if (maxStatus < STATUS_WARNING) maxStatus = STATUS_WARNING;
        }
    };

    check(data.temperature, TEMP_MIN_C, TEMP_MAX_C);
    check(data.ph, PH_MIN, PH_MAX);
    check(data.gasLevel, 0, GAS_MAX_PPM);

    // Pressure has special fail-safe logic
    if (data.pressure > PRESSURE_MAX_KPA) {
        maxStatus = STATUS_CRITICAL;
        data.valveTriggered = true;
    } else if (data.pressure > PRESSURE_ALERT_KPA) {
        if (maxStatus < STATUS_WARNING) maxStatus = STATUS_WARNING;
    }

    data.status = maxStatus;
    return data.valveTriggered;
}
