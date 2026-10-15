-- wamp_monolith_db 
CREATE TABLE global_ecommerce (
    item_id VARCHAR(50) PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    stock_qty INT NOT NULL,
    order_count INT DEFAULT 0
);

INSERT INTO global_ecommerce (item_id, item_name, stock_qty) VALUES 
('PROD-001', 'Livre Architecture Microservices', 15),
('PROD-002', 'Clé USB 64Go', 5);
