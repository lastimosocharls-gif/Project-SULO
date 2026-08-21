#ifndef DISPLAY_H
#define DISPLAY_H

#include <Arduino.h>
#include "sensors.h"

void display_init();
void display_update(const SensorData &data, int currentScreen);
void display_show_alert(const char *message);

#endif // DISPLAY_H
