#ifndef WIFI_H
#define WIFI_H

#include <Arduino.h>
#include "sensors.h"

void wifi_init();
bool wifi_is_connected();
bool wifi_post_readings(const SensorData &data);
bool wifi_post_alert(const SensorData &data, const char *alertType, const char *severity, const char *message);

#endif // WIFI_H
