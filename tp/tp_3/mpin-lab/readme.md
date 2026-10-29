# Travaux Pratiques 3 : Architecture Contract-First & Serveur de Mocking Automatique
# Environnement cible : Windows (Docker Desktop + Postman)
# Livrables attendus : Un dépôt Git contenant le fichier de spécification OpenAPI valide et l'orchestrateur Docker fonctionnel.
# C:\mpin-lab\
├── delivery-api.yaml      <-- Le contrat OpenAPI v3
└── docker-compose.yml     <-- L'orchestrateur du serveur de Mock
# Commande de déploiement à exécuter sous PowerShell 
docker-compose up -d --build
# Scénario A : Le Test de Lecture Nominal (GET)
# Methode : GET
# URL : http://localhost:4010/deliveries/DLV-9982
# Statut : 200 OK

# Scénario B : Le Crash Test de Conformité (POST Rejeté)

# Methode : POST
# URL : http://localhost:4010/deliveries
# Headers  : Content-Type: application/json
# Statut : 200 OK
# Body 
{
    "order_id": "ORD-4512",
    "notes": "Laisser devant la porte"
}
