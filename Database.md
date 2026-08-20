Database Schema & Entity Relationship Specification
SIMLAB - Computer Laboratory Management System
1. Entity Relationship Diagram (Conceptual ERD)
+-----------------+       1:N       +-----------------+
|      LABS       | <-------------- |     ASSETS      |
+-----------------+                 +-----------------+
        |                                   |
        | 1:N                               | 1:N
        v                                   v
+-----------------+                 +-----------------+
|    SCHEDULES    |                 |ASSET_PERIPHERALS|
+-----------------+                 +-----------------+
        |                                   |
        | 1:N                               | 1:N
        v                                   v
+-----------------+                 +-----------------+
|    BOOKINGS     |                 |  TICKETS LOG    |
+-----------------+                 +-----------------+
                                            |
                                            v
                                    +-----------------+
                                    | PRESENCE_LOGS   |
                                    +-----------------+


2. Definisi Tabel SQL (DDL Specification)
2.1. Tabel labs (Data Ruangan Lab)
CREATE TABLE labs (
    id SERIAL PRIMARY KEY,
    lab_code VARCHAR(20) UNIQUE NOT NULL, -- e.g. 'LAB-1', 'LAB-2'
    name VARCHAR(100) NOT NULL,          -- e.g. 'Laboratorium komputer 1'
    capacity INT NOT NULL DEFAULT 0,     -- e.g. 20, 15
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


2.2. Tabel assets (Master Data Unit PC & Server)
CREATE TYPE asset_status_enum AS ENUM ('Ready', 'Degraded', 'Maintenance', 'Scrapped');
CREATE TYPE asset_category_enum AS ENUM ('PC Desktop', 'Workstation', 'Server', 'Laptop');

CREATE TABLE assets (
    id SERIAL PRIMARY KEY,
    asset_code VARCHAR(50) UNIQUE NOT NULL, -- e.g. 'LAB1-PC-01'
    name VARCHAR(100) NOT NULL,             -- e.g. 'PC Client 01'
    lab_id INT REFERENCES labs(id) ON DELETE CASCADE,
    seat_label VARCHAR(30) NOT NULL,        -- e.g. 'Meja A-01'
    category asset_category_enum DEFAULT 'PC Desktop',
    
    -- Hardware Specs
    cpu_spec VARCHAR(100) NOT NULL,
    ram_gb INT NOT NULL,
    ram_type VARCHAR(20) DEFAULT 'DDR4',
    storage_primary VARCHAR(100) NOT NULL,
    storage_secondary VARCHAR(100),
    gpu_spec VARCHAR(100),
    
    -- Network Specs
    ip_address VARCHAR(45),
    mac_address VARCHAR(17),
    serial_number VARCHAR(100),
    
    -- Operational Status
    procurement_source VARCHAR(100),
    purchase_date DATE,
    warranty_expiry DATE,
    status asset_status_enum DEFAULT 'Ready',
    qr_code_url TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_assets_lab ON assets(lab_id);
CREATE INDEX idx_assets_status ON assets(status);


2.3. Tabel asset_peripherals (Periferal Child Asset)
CREATE TYPE peripheral_type_enum AS ENUM ('Monitor', 'Keyboard', 'Mouse', 'Headset', 'UPS', 'Webcam', 'Other');

CREATE TABLE asset_peripherals (
    id SERIAL PRIMARY KEY,
    peripheral_code VARCHAR(50) UNIQUE NOT NULL,
    asset_id INT REFERENCES assets(id) ON DELETE SET NULL, -- NULL jika stok di gudang
    type peripheral_type_enum NOT NULL,
    brand VARCHAR(50) NOT NULL,
    model_name VARCHAR(100),
    serial_number VARCHAR(100),                            -- Nullable / Opsional
    condition VARCHAR(50) DEFAULT 'Baik / Normal',
    location_note VARCHAR(100),                           -- e.g. 'Terpasang di PC-01' / 'Gudang Rak A'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


2.4. Tabel tickets (Helpdesk & Servis Log)
CREATE TYPE ticket_priority_enum AS ENUM ('Low', 'Medium', 'High');
CREATE TYPE ticket_status_enum AS ENUM ('Open', 'In Progress', 'Resolved');

CREATE TABLE tickets (
    id SERIAL PRIMARY KEY,
    ticket_code VARCHAR(30) UNIQUE NOT NULL, -- e.g. 'TKT-101'
    asset_id INT REFERENCES assets(id) ON DELETE CASCADE,
    component_issue VARCHAR(100) NOT NULL,   -- e.g. 'Mouse / Keyboard'
    description TEXT NOT NULL,
    priority ticket_priority_enum DEFAULT 'Medium',
    status ticket_status_enum DEFAULT 'Open',
    reporter_name VARCHAR(100) NOT NULL,
    technician_name VARCHAR(100),
    resolution_notes TEXT,
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP
);

CREATE INDEX idx_tickets_status ON tickets(status);


2.5. Tabel schedules & bookings (Jadwal & Reservasi)
CREATE TABLE schedules (
    id SERIAL PRIMARY KEY,
    lab_id INT REFERENCES labs(id) ON DELETE CASCADE,
    day_name VARCHAR(15) NOT NULL,           -- 'Senin', 'Selasa', etc.
    time_slot VARCHAR(30) NOT NULL,          -- '07:00 - 09:15'
    subject_name VARCHAR(100) NOT NULL,      -- 'Pemrograman Web'
    class_group VARCHAR(50) NOT NULL,        -- 'XII RPL 1'
    instructor_name VARCHAR(100) NOT NULL
);

CREATE TABLE bookings (
    id SERIAL PRIMARY KEY,
    lab_id INT REFERENCES labs(id) ON DELETE CASCADE,
    applicant_name VARCHAR(100) NOT NULL,    -- e.g. 'Pak Hendra'
    event_name VARCHAR(150) NOT NULL,        -- e.g. 'Workshop Cyber Security'
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status VARCHAR(20) DEFAULT 'APPROVED',   -- 'PENDING', 'APPROVED', 'REJECTED'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


2.6. Tabel presences (User Session & Seat Mapping)
CREATE TABLE presences (
    id SERIAL PRIMARY KEY,
    asset_id INT REFERENCES assets(id) ON DELETE CASCADE,
    user_identifier VARCHAR(100) NOT NULL,   -- NISN / NIM / Nama Siswa
    user_fullname VARCHAR(100) NOT NULL,
    session_date DATE NOT NULL DEFAULT CURRENT_DATE,
    check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_out_time TIMESTAMP
);


