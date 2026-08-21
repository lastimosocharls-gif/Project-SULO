#ifndef SENSORS_H
#define SENSORS_H

#include <Arduino.h>

struct SensorData {
    float temperature;    // °C
    float ph;             // pH level
    float gasLevel;       // ppm
    float pressure;       // kPa
    float flowRate;       // L/min
    uint8_t status;       // STATUS_NORMAL / STATUS_WARNING / STATUS_CRITICAL
    bool valveTriggered;  // true if fail-safe activated
};

void sensors_init();
SensorData sensors_read();
bool sensors_evaluate_thresholds(SensorData &data);

#endif // SENSORS_H
