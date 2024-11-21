SCHEMA
====================================================================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL, -- Agency Admin, Municipal Office, Tax Collector
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE entities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('Household', 'Commercial') NOT NULL, -- General Household or Commercial
    category VARCHAR(50), -- e.g., Hospital, Industry (only for Commercial)
    cluster_id INT, -- Null for non-clustered entities
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    billing_status ENUM('Individual', 'Clustered') DEFAULT 'Individual',
    monthly_bill_amount DECIMAL(10, 2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE clusters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    zone_id INT, -- Defined by admin on map
    FOREIGN KEY (zone_id) REFERENCES zones(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_id INT NOT NULL,
    tax_collector_id INT NOT NULL, -- Refers to the user collecting tax
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_mode ENUM('Cash', 'UPI', 'Cheque', 'Partial') NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    status ENUM('Paid', 'Denied', 'Deferred', 'Door Closed') NOT NULL,
    remarks TEXT,
    FOREIGN KEY (entity_id) REFERENCES entities(id),
    FOREIGN KEY (tax_collector_id) REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    boundary GEOMETRY NOT NULL, -- Polygon shape to store zone boundaries
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE zone_entities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT NOT NULL,
    entity_id INT NOT NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id),
    FOREIGN KEY (entity_id) REFERENCES entities(id)
);

CREATE TABLE tax_collector_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_collector_id INT NOT NULL, -- Refers to the user assigned as TC
    zone_id INT NOT NULL, -- Refers to the zone assigned
    assigned_date DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (tax_collector_id) REFERENCES users(id),
    FOREIGN KEY (zone_id) REFERENCES zones(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE monthly_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_id INT NOT NULL, -- Household or commercial entity
    billing_month DATE NOT NULL, -- First day of the month (e.g., 2024-11-01)
    amount_due DECIMAL(10, 2) NOT NULL,
    amount_paid DECIMAL(10, 2) DEFAULT 0.00,
    payment_status ENUM('Paid', 'Partially Paid', 'Unpaid') DEFAULT 'Unpaid',
    last_payment_date TIMESTAMP NULL,
    remarks TEXT,
    FOREIGN KEY (entity_id) REFERENCES entities(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE cluster_monthly_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cluster_id INT NOT NULL,
    billing_month DATE NOT NULL,
    amount_due DECIMAL(10, 2) NOT NULL,
    amount_paid DECIMAL(10, 2) DEFAULT 0.00,
    payment_status ENUM('Paid', 'Partially Paid', 'Unpaid') DEFAULT 'Unpaid',
    last_payment_date TIMESTAMP NULL,
    remarks TEXT,
    FOREIGN KEY (cluster_id) REFERENCES clusters(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_id INT,
    cluster_id INT, -- Optional, null for individual households
    tax_collector_id INT NOT NULL, -- The TC responsible for this collection
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(10, 2) NOT NULL,
    payment_mode ENUM('Cash', 'UPI', 'Cheque', 'Mixed') NOT NULL,
    FOREIGN KEY (entity_id) REFERENCES entities(id),
    FOREIGN KEY (cluster_id) REFERENCES clusters(id),
    FOREIGN KEY (tax_collector_id) REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE denial_reasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE denial_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_id INT, -- Household or commercial entity
    cluster_id INT, -- For cluster-level denial, optional
    tax_collector_id INT NOT NULL, -- TC who reported the denial
    denial_reason_id INT NOT NULL, -- Reason for denial
    denial_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT, -- Additional comments
    FOREIGN KEY (entity_id) REFERENCES entities(id),
    FOREIGN KEY (cluster_id) REFERENCES clusters(id),
    FOREIGN KEY (tax_collector_id) REFERENCES users(id),
    FOREIGN KEY (denial_reason_id) REFERENCES denial_reasons(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE tax_collector_zone_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_collector_id INT NOT NULL, -- Refers to the tax collector
    zone_id INT NOT NULL, -- Refers to the zone
    assigned_date DATE NOT NULL, -- Date when this zone was assigned
    unassigned_date DATE NULL, -- Date when the zone was unassigned (optional)
    remarks TEXT, -- Reason for change, if any
    FOREIGN KEY (tax_collector_id) REFERENCES users(id),
    FOREIGN KEY (zone_id) REFERENCES zones(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_collector_id INT,
    total_collections DECIMAL(10, 2) NOT NULL,
    targets DECIMAL(10, 2) NOT NULL,
    performance_percentage DECIMAL(5, 2) NOT NULL, -- (total_collections / targets) * 100
    zone_id INT,
    FOREIGN KEY (tax_collector_id) REFERENCES users(id),
    FOREIGN KEY (zone_id) REFERENCES zones(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



