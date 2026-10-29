CREATE TABLE stocks (
    product_id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    stock INT NOT NULL
);

INSERT INTO stocks (product_id, name, stock) VALUES 
('PROD-001', 'Livre Architecture Microservices (Postgres)', 15),
('PROD-002', 'Clé USB 64Go (Postgres)', 5);
