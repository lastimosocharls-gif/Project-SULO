#ifndef FAILSAFE_H
#define FAILSAFE_H

#include <Arduino.h>
#include "sensors.h"

void failsafe_init();
void failsafe_evaluate(const SensorData &data);

#endif // FAILSAFE_H
