# 1. test sur postman methode POST

http://localhost/monolith/index.php/orders
{
    "product_id": "PROD-001", 
    "quantity": 2
}

# 2. creer le conteneur sur Docker et tester pour voir comment les deux systeme communique

# 3. Mesure du temps de réponse du Monolithe (WampServer)

(Measure-Command {
    Invoke-RestMethod -Method Post -Uri "http://localhost/monolith/index.php/orders" -Body '{"product_id": "PROD-001", "quantity": 1}' -ContentType "application/json"
}).TotalMilliseconds

# 4. Lancement de la stack Docker

docker-compose up --build -d

# 5. Mesure du temps de réponse du Microservice (Docker Desktop)
(Measure-Command {
    Invoke-RestMethod -Method Post -Uri "http://localhost:8081/inventory/PROD-001/deduct" -Body '{"quantity": 1}' -ContentType "application/json"
}).TotalMilliseconds
# Autre chose....

#  1. Démarrer Docker Desktop sous Windows via l'icone  Docker Desktop 

#  2. Vérifier la connexion dans PowerShell
docker ps

# 3. Relancer l'orchestration
docker-compose up --build -d
# 4 Chronométrer le Monolithe (WampServer / MySQL local)
(Measure-Command {
    Invoke-RestMethod -Method Post -Uri "http://localhost/monolith-wamp/index.php/orders" -Body '{"product_id": "PROD-001", "quantity": 1}' -ContentType "application/json"
}).TotalMilliseconds
# 5 2. Chronométrer le Microservice (Docker Desktop / PostgreSQL / Réseau Virtuel WSL2)
(Measure-Command {
    Invoke-RestMethod -Method Post -Uri "http://localhost:8081/inventory/PROD-001/deduct" -Body '{"quantity": 1}' -ContentType "application/json"
}).TotalMilliseconds
