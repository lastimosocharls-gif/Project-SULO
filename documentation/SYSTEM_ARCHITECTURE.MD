# System Architecture — SULO (Smart Utility for Local Organic Waste)

## 1. Architecture Overview

```mermaid
graph TB

    subgraph Hardware["Edge Layer — ESP32 IoT Device"]
        DIG[Mini Biogas Digester]

        TEMP[DS18B20<br/>Temperature Sensor]
        PH[pH Sensor]
        GAS[MQ-4 / MQ-135<br/>Gas Sensor]
        PRESS[BMP280<br/>Pressure Sensor]
        FLOW[YF-S401<br/>Flow Sensor]

        ESP[ESP32 Microcontroller]

        OLED[OLED Display]
        IR[IR Receiver]
        FAIL[Fail-safe Logic]
        RELAY[Relay Module]
        VALVE[Solenoid Valve]

        DIG --> TEMP
        DIG --> PH
        DIG --> GAS
        DIG --> PRESS
        DIG --> FLOW

        TEMP --> ESP
        PH --> ESP
        GAS --> ESP
        PRESS --> ESP
        FLOW --> ESP

        ESP --> OLED
        IR --> ESP

        ESP --> FAIL
        FAIL --> RELAY
        RELAY --> VALVE
    end

    subgraph Cloud["Cloud Layer — Laravel + PostgreSQL"]

        API[Laravel REST API]

        DB[(PostgreSQL)]

        API --> DB

    end

    subgraph Dashboard["Presentation Layer"]

        WEB[Monitoring Dashboard]

        CHART[Historical Charts]

        ALERT[Alert Logs]

    end

    ESP -->|HTTP POST / JSON| API

    API -->|JSON| WEB

    API --> CHART

    API --> ALERT
```


## 2. Sensor Data Flow

```mermaid
sequenceDiagram

participant D as Mini Digester
participant S as Sensors
participant E as ESP32
participant V as Solenoid Valve
participant L as Laravel API
participant DB as PostgreSQL
participant W as Dashboard

D->>S: Produce gas & environmental readings

S->>E: Temperature
S->>E: pH
S->>E: Gas Level
S->>E: Pressure
S->>E: Flow Rate

E->>E: Evaluate threshold values

alt Safe Conditions

    E->>L: HTTP POST sensor readings

else Unsafe Conditions

    E->>V: Open Solenoid Valve
    E->>L: Send Alert + Sensor Data

end

L->>DB: Store sensor readings
L->>DB: Store alerts

DB-->>L: Latest records

L-->>W: Live monitoring dashboard
```

## 3. Database Design (ER Diagram)

```mermaid
erDiagram

    SENSOR_READINGS ||--o{ ALERTS : triggers
    SENSOR_READINGS ||--|| DIGESTER_STATUS : updates

    SENSOR_READINGS {

        int reading_id PK

        decimal temperature

        decimal ph

        decimal gas_level

        decimal pressure

        decimal flow_rate

        datetime recorded_at

    }

    ALERTS {

        int alert_id PK

        int reading_id FK

        string alert_type

        string severity

        string message

        datetime created_at

    }

    DIGESTER_STATUS {

        int status_id PK

        int reading_id FK

        string valve_status

        string overall_status

        datetime updated_at

    }
```

Project-SULO/

│

├── firmware/
│   ├── ESP32.ino
│   ├── sensors.cpp
│   ├── display.cpp
│   ├── wifi.cpp
│   ├── failsafe.cpp
│   └── config.h
│

├── backend/
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │        ├── SensorController.php
│   │   │        ├── AlertController.php
│   │   │        └── DashboardController.php
│   │   │
│   │   └── Models/
│   │        ├── SensorReading.php
│   │        ├── Alert.php
│   │        └── DigesterStatus.php
│   │
│   ├── routes/
│   │     api.php
│   │
│   └── database/
│         migrations/
│

├── frontend/
│   ├── Dashboard
│   ├── Live Monitoring
│   ├── Historical Charts
│   └── Alert History
│

├── documentation/
│

├── README.md
│
└── LICENSE