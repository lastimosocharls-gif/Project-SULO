-- ============================================================
-- SULO Cloud Database Schema (Supabase)
-- Smart Utility for Local Organic Waste
-- Multi-unit biogas digester monitoring
-- ============================================================

-- ─── Enable UUID extension ───
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ─── UNITS TABLE ───
-- Each biogas digester installation = one unit
CREATE TABLE units (
    unit_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name TEXT NOT NULL,                          -- e.g., "Karenderya Unit A"
    location TEXT,                               -- e.g., "Main Kitchen, Brgy. 123"
    description TEXT,                            -- optional notes
    is_active BOOLEAN DEFAULT true,              -- soft delete / pause
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now()
);

-- ─── SENSOR READINGS TABLE ───
-- Time-series data from each digester unit
CREATE TABLE sensor_readings (
    reading_id BIGSERIAL PRIMARY KEY,
    unit_id UUID NOT NULL REFERENCES units(unit_id) ON DELETE CASCADE,
    temperature DECIMAL(6,2) NOT NULL,           -- Celsius
    ph DECIMAL(4,2) NOT NULL,                    -- pH level
    gas_level DECIMAL(8,2) NOT NULL,             -- ppm
    pressure DECIMAL(6,2) NOT NULL,              -- kPa
    flow_rate DECIMAL(8,4) NOT NULL,             -- L/min
    status SMALLINT DEFAULT 0,                   -- 0=normal, 1=warning, 2=critical
    valve_open BOOLEAN DEFAULT false,
    recorded_at TIMESTAMPTZ DEFAULT now(),
    created_at TIMESTAMPTZ DEFAULT now()
);

-- Indexes for sensor_readings
CREATE INDEX idx_sensor_readings_unit_id ON sensor_readings(unit_id);
CREATE INDEX idx_sensor_readings_recorded_at ON sensor_readings(recorded_at DESC);
CREATE INDEX idx_sensor_readings_unit_recorded ON sensor_readings(unit_id, recorded_at DESC);
CREATE INDEX idx_sensor_readings_status ON sensor_readings(status);

-- ─── ALERTS TABLE ───
-- Threshold breaches and system alerts per unit
CREATE TABLE alerts (
    alert_id BIGSERIAL PRIMARY KEY,
    reading_id BIGINT NOT NULL REFERENCES sensor_readings(reading_id) ON DELETE CASCADE,
    unit_id UUID NOT NULL REFERENCES units(unit_id) ON DELETE CASCADE,
    alert_type TEXT NOT NULL,                    -- overpressure, threshold_breach, etc.
    severity TEXT NOT NULL CHECK (severity IN ('warning', 'critical')),
    message TEXT NOT NULL,
    acknowledged BOOLEAN DEFAULT false,
    acknowledged_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT now()
);

-- Indexes for alerts
CREATE INDEX idx_alerts_unit_id ON alerts(unit_id);
CREATE INDEX idx_alerts_severity ON alerts(severity);
CREATE INDEX idx_alerts_created_at ON alerts(created_at DESC);
CREATE INDEX idx_alerts_acknowledged ON alerts(acknowledged);

-- ─── DIGESTER STATUS TABLE ───
-- Current status per unit (one row per unit, updated on each reading)
CREATE TABLE digester_status (
    status_id BIGSERIAL PRIMARY KEY,
    unit_id UUID NOT NULL UNIQUE REFERENCES units(unit_id) ON DELETE CASCADE,
    reading_id BIGINT REFERENCES sensor_readings(reading_id) ON DELETE SET NULL,
    valve_status TEXT DEFAULT 'closed' CHECK (valve_status IN ('open', 'closed')),
    overall_status TEXT DEFAULT 'normal' CHECK (overall_status IN ('normal', 'warning', 'critical')),
    updated_at TIMESTAMPTZ DEFAULT now()
);

-- ─── ADMINS TABLE ───
-- Admin users who can access the dashboard
CREATE TABLE admins (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    role TEXT DEFAULT 'viewer' CHECK (role IN ('admin', 'viewer')),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT now()
);

-- ─── HELPER FUNCTIONS ───

-- Auto-update updated_at on units
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_units_updated_at
    BEFORE UPDATE ON units
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Auto-update updated_at on digester_status
CREATE TRIGGER update_digester_status_updated_at
    BEFORE UPDATE ON digester_status
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- ─── REALTIME SUBSCRIPTIONS ───
-- Enable realtime for live dashboard updates
ALTER PUBLICATION supabase_realtime ADD TABLE sensor_readings;
ALTER PUBLICATION supabase_realtime ADD TABLE alerts;
ALTER PUBLICATION supabase_realtime ADD TABLE digester_status;

-- ─── ROW LEVEL SECURITY (RLS) ───
-- Enable RLS on all tables
ALTER TABLE units ENABLE ROW LEVEL SECURITY;
ALTER TABLE sensor_readings ENABLE ROW LEVEL SECURITY;
ALTER TABLE alerts ENABLE ROW LEVEL SECURITY;
ALTER TABLE digester_status ENABLE ROW LEVEL SECURITY;
ALTER TABLE admins ENABLE ROW LEVEL SECURITY;

-- Public policies for ESP32 data ingestion (using service_role key)
CREATE POLICY "ESP32 can insert sensor readings"
    ON sensor_readings FOR INSERT
    WITH CHECK (true);

CREATE POLICY "ESP32 can insert alerts"
    ON alerts FOR INSERT
    WITH CHECK (true);

CREATE POLICY "ESP32 can update digester status"
    ON digester_status FOR INSERT
    WITH CHECK (true);

CREATE POLICY "ESP32 can update digester status"
    ON digester_status FOR UPDATE
    USING (true);

-- Admin policies (authenticated users can read all data)
CREATE POLICY "Admins can read all units"
    ON units FOR SELECT
    USING (true);

CREATE POLICY "Admins can read all sensor readings"
    ON sensor_readings FOR SELECT
    USING (true);

CREATE POLICY "Admins can read all alerts"
    ON alerts FOR SELECT
    USING (true);

CREATE POLICY "Admins can read all digester status"
    ON digester_status FOR SELECT
    USING (true);

CREATE POLICY "Admins can acknowledge alerts"
    ON alerts FOR UPDATE
    USING (true);

CREATE POLICY "Admins can manage units"
    ON units FOR ALL
    USING (true);

-- ─── SEED DATA ───
-- Insert default admin user (password: admin123 - change in production!)
-- Password hash generated with bcrypt
INSERT INTO admins (email, password_hash, full_name, role) VALUES
    ('admin@sulo.local', '$2y$10$YourHashedPasswordHere', 'System Administrator', 'admin');

-- NOTE: Add your digester units via the admin dashboard or SQL:
-- INSERT INTO units (name, location, description) VALUES
--     ('Karenderya Unit A', 'Main Kitchen, Brgy. Hilera', 'Primary biogas digester'),
--     ('Karenderya Unit B', 'Annex Kitchen, Brgy. Hilera', 'Secondary digester');

-- ============================================================
-- SCHEMA COMPLETE
-- Run this in Supabase SQL Editor to set up your database
-- ============================================================
