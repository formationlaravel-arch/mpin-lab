CREATE TABLE stocks (
    product_id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    stock INT NOT NULL
);

CREATE TABLE orders (
    order_id VARCHAR(50) PRIMARY KEY,
    product_id VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    status VARCHAR(50) NOT NULL
);

INSERT INTO stocks (product_id, name, stock) VALUES 
('PROD-001', 'Livre Architecture Microservices', 100000),
('PROD-002', 'Clé USB 64Go', 50000);
