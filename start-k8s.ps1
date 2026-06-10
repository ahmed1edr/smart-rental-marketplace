Write-Host "[*] Starting Kubernetes Deployment for Smart Rental..."

# Apply ConfigMap and Secret
Write-Host "[*] Applying Configs and Secrets..."
kubectl apply -f k8s/configmap-secret.yaml

# Apply Databases (MySQL & Redis)
Write-Host "[*] Applying Databases..."
kubectl apply -f k8s/mysql.yaml
kubectl apply -f k8s/redis.yaml

# Wait for databases to be ready
Write-Host "[*] Waiting for databases to start..."
Start-Sleep -Seconds 15

# Apply Applications (Backend, Frontend, PhpMyAdmin)
Write-Host "[*] Applying Backend and Frontend..."
kubectl apply -f k8s/backend.yaml
kubectl apply -f k8s/frontend.yaml
kubectl apply -f k8s/phpmyadmin.yaml

Write-Host "[+] All resources applied!"
Write-Host "--------------------------------------------------------"
Write-Host "Port Forwarding commands (Run these in separate terminals to access the app):"
Write-Host ""
Write-Host "1. Frontend (React):   kubectl port-forward svc/rental-frontend-service 3000:3000"
Write-Host "2. Backend (Laravel):  kubectl port-forward svc/rental-backend-service 8000:8000"
Write-Host "3. WebSocket (Reverb): kubectl port-forward svc/rental-backend-service 8080:8080"
Write-Host "4. PhpMyAdmin:         kubectl port-forward svc/rental-pma-service 9999:80"
Write-Host "--------------------------------------------------------"
