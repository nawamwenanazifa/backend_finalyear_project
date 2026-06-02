# test-simple.ps1 - Simplified version
Write-Host "Loading Status System Test" -ForegroundColor Cyan
Write-Host ""

# Start the operation
Write-Host "Starting operation..." -ForegroundColor Yellow

$body = '{"operation_name":"Test Import Process"}'
$response = Invoke-RestMethod -Uri "http://localhost:8000/api/loading/simulate" -Method Post -Body $body -ContentType "application/json"

$operationId = $response.operation_id
Write-Host "Operation started with ID: $operationId" -ForegroundColor Green
Write-Host ""

# Monitor progress
Write-Host "Monitoring progress:" -ForegroundColor Cyan
Write-Host ""

for ($i = 1; $i -le 15; $i++) {
    Start-Sleep -Seconds 1
    
    try {
        $status = Invoke-RestMethod -Uri "http://localhost:8000/api/loading/status/$operationId" -Method Get
        
        $percent = $status.percentage
        $message = $status.message
        
        Write-Host "Step $i : $percent% - $message" -ForegroundColor White
        
        if ($status.status -eq "completed") {
            Write-Host ""
            Write-Host "SUCCESS! Operation completed successfully!" -ForegroundColor Green
            break
        }
        
        if ($status.status -eq "failed") {
            Write-Host ""
            Write-Host "ERROR: Operation failed!" -ForegroundColor Red
            Write-Host "Error: $($status.error)" -ForegroundColor Red
            break
        }
        
    } catch {
        Write-Host "Error checking status: $_" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Test Complete" -ForegroundColor Cyan