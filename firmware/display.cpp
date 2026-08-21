#include "display.h"
#include "config.h"
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

#define SCREEN_WIDTH  128
#define SCREEN_HEIGHT 64
#define OLED_RESET    -1

static Adafruit_SSD1306 oled(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

void display_init() {
    Wire.begin(DISPLAY_SDA, DISPLAY_SCL);
    if (!oled.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
        Serial.println("[DISPLAY] OLED init failed");
        return;
    }
    oled.clearDisplay();
    oled.setTextSize(1);
    oled.setTextColor(SSD1306_WHITE);
    oled.setCursor(0, 0);
    oled.println("SULO System");
    oled.println("Starting...");
    oled.display();
}

// ─── Helper: status label ───
static const char* statusLabel(uint8_t status) {
    switch (status) {
        case STATUS_NORMAL:   return "NORMAL";
        case STATUS_WARNING:  return "WARNING";
        case STATUS_CRITICAL: return "CRITICAL";
        default:              return "UNKNOWN";
    }
}

// ─── Helper: draw header bar ───
static void drawHeader(const char *title, uint8_t status) {
    oled.setTextSize(1);
    oled.setCursor(0, 0);
    oled.print("SULO | ");
    oled.print(title);

    // Status indicator (top-right)
    oled.setCursor(80, 0);
    oled.print("[");
    oled.print(statusLabel(status));
    oled.print("]");
}

// ─── Screens ───

static void screenOverview(const SensorData &data) {
    drawHeader("Overview", data.status);

    oled.setTextSize(1);
    oled.setCursor(0, 16);
    oled.print("Temp:  ");
    oled.print(data.temperature, 1);
    oled.println(" C");

    oled.setCursor(0, 26);
    oled.print("pH:    ");
    oled.println(data.ph, 2);

    oled.setCursor(0, 36);
    oled.print("Gas:   ");
    oled.print(data.gasLevel, 0);
    oled.println(" ppm");

    oled.setCursor(0, 46);
    oled.print("Press: ");
    oled.print(data.pressure, 1);
    oled.println(" kPa");

    oled.setCursor(0, 56);
    oled.print("Flow:  ");
    oled.print(data.flowRate, 2);
    oled.println(" L/m");
}

static void screenTemperature(const SensorData &data) {
    drawHeader("Temp", data.status);
    oled.setTextSize(2);
    oled.setCursor(10, 25);
    oled.print(data.temperature, 1);
    oled.println(" C");
    oled.setTextSize(1);
    oled.setCursor(10, 50);
    oled.print("Safe: ");
    oled.print((int)TEMP_MIN_C);
    oled.print("-");
    oled.print((int)TEMP_MAX_C);
    oled.println(" C");
}

static void screenPH(const SensorData &data) {
    drawHeader("pH", data.status);
    oled.setTextSize(2);
    oled.setCursor(10, 25);
    oled.print(data.ph, 2);
    oled.setTextSize(1);
    oled.setCursor(10, 50);
    oled.print("Safe: ");
    oled.print(PH_MIN, 1);
    oled.print("-");
    oled.print(PH_MAX, 1);
}

static void screenGas(const SensorData &data) {
    drawHeader("Gas", data.status);
    oled.setTextSize(2);
    oled.setCursor(10, 25);
    oled.print(data.gasLevel, 0);
    oled.setTextSize(1);
    oled.setCursor(10, 50);
    oled.print("Max safe: ");
    oled.print((int)GAS_MAX_PPM);
    oled.println(" ppm");
}

static void screenPressure(const SensorData &data) {
    drawHeader("Pressure", data.status);
    oled.setTextSize(2);
    oled.setCursor(10, 25);
    oled.print(data.pressure, 1);
    oled.setTextSize(1);
    oled.setCursor(10, 50);
    oled.print("Max: ");
    oled.print((int)PRESSURE_MAX_KPA);
    oled.println(" kPa");
    if (data.valveTriggered) {
        oled.setCursor(10, 58);
        oled.print(">> VALVE OPEN <<");
    }
}

static void screenAlerts(const SensorData &data) {
    drawHeader("Alerts", data.status);
    oled.setTextSize(1);
    oled.setCursor(0, 20);

    if (data.status == STATUS_NORMAL) {
        oled.println("No active alerts.");
    } else {
        if (data.temperature < TEMP_MIN_C || data.temperature > TEMP_MAX_C) {
            oled.println("- Temperature OOB");
        }
        if (data.ph < PH_MIN || data.ph > PH_MAX) {
            oled.println("- pH out of range");
        }
        if (data.gasLevel > GAS_MAX_PPM) {
            oled.println("- High gas level");
        }
        if (data.pressure > PRESSURE_ALERT_KPA) {
            oled.println("- Pressure WARNING");
        }
        if (data.pressure > PRESSURE_MAX_KPA) {
            oled.println("- OVERPRESSURE!");
            oled.println("  Solenoid OPEN");
        }
    }
}

// ─── Public API ───

void display_update(const SensorData &data, int currentScreen) {
    oled.clearDisplay();

    switch (currentScreen) {
        case SCREEN_OVERVIEW:     screenOverview(data);    break;
        case SCREEN_TEMPERATURE:  screenTemperature(data); break;
        case SCREEN_PH:           screenPH(data);          break;
        case SCREEN_GAS:          screenGas(data);         break;
        case SCREEN_PRESSURE:     screenPressure(data);    break;
        case SCREEN_ALERTS:       screenAlerts(data);      break;
        default:                  screenOverview(data);    break;
    }

    oled.display();
}

void display_show_alert(const char *message) {
    oled.clearDisplay();
    oled.setTextSize(1);
    oled.setTextColor(SSD1306_WHITE);
    oled.setCursor(0, 0);
    oled.println("!! ALERT !!");
    oled.println();
    oled.println(message);
    oled.display();
}
