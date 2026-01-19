# Simple Warehouse Test - PowerShell Compatible
param(
    [int]$SlotCount = 10,
    [string]$BaseUrl = "http://localhost:8000"
)

Write-Host "Starting warehouse test..." -ForegroundColor Green

# Simple URL encode function
function ConvertTo-FormString {
    param($hashtable)
    $parts = @()
    foreach ($key in $hashtable.Keys) {
        $value = $hashtable[$key]
        $parts += "$key=$value"
    }
    return $parts -join "&"
}

# Login function
function Test-Login {
    try {
        Write-Host "Getting login page..." -ForegroundColor Yellow

        # Get login page for CSRF token
        $loginPage = Invoke-WebRequest -Uri "$BaseUrl/login" -Method GET -UseBasicParsing
        $html = $loginPage.Content

        # Extract CSRF token
        $csrfToken = ""
        if ($html -match 'name="_token"[^>]*value="([^"]+)"') {
            $csrfToken = $matches[1]
        }

        if (-not $csrfToken) {
            Write-Host "Could not extract CSRF token" -ForegroundColor Red
            return $null
        }

        Write-Host "CSRF token: $csrfToken" -ForegroundColor Green

        # Login with CSRF token
        $loginData = @{
            username = "admin"
            password = "password"
            _token = $csrfToken
        }

        $body = ConvertTo-FormString $loginData

        $response = Invoke-WebRequest -Uri "$BaseUrl/login" -Method POST -Body $body -ContentType "application/x-www-form-urlencoded" -UseBasicParsing

        Write-Host "Login successful!" -ForegroundColor Green
        return $response
    }
    catch {
        Write-Host "Login failed: $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

# Create slot function
function Test-CreateSlot {
    param($session, $poNum, $truckNum, $warehouse)

    try {
        $startTime = (Get-Date).AddMinutes((Get-Random -Minimum 1 -Maximum 60)).ToString("yyyy-MM-dd HH:mm")
        $data = @{
            po_number = $poNum
            truck_number = $truckNum
            truck_type = "Container 20ft"
            direction = "inbound"
            warehouse_id = $warehouse
            vendor_id = 1
            planned_start = $startTime
        }

        $body = ConvertTo-FormString $data

        $response = Invoke-WebRequest -Uri "$BaseUrl/slots" -Method POST -Body $body -ContentType "application/x-www-form-urlencoded" -WebSession $session -UseBasicParsing

        Write-Host "✅ Created slot: $poNum" -ForegroundColor Green
        return $response
    }
    catch {
        Write-Host "❌ Failed to create slot $poNum`: $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

# Progress slot function
function Test-ProgressSlot {
    param($session, $slotId, $operation)

    try {
        $data = switch ($operation) {
            "arrival" { @{
                ticket_number = "A$(Get-Date -UFormat %s)"
                sj_number = "SJ$slotId"
                truck_type = "Container 20ft"
                actual_arrival = (Get-Date).ToString("yyyy-MM-dd HH:mm")
                actual_gate_id = 1
            }}
            "start" { @{ actual_gate_id = 1 }}
            "complete" { @{
                mat_doc = "MD$slotId"
                sj_number = "SJ$slotId"
                truck_type = "Container 20ft"
                vehicle_number = "B$slotId"
                driver_number = "DRV$slotId"
                actual_finish = (Get-Date).ToString("yyyy-MM-dd HH:mm")
            }}
        }

        $body = ConvertTo-FormString $data

        $response = Invoke-WebRequest -Uri "$BaseUrl/slots/$slotId/$operation" -Method POST -Body $body -ContentType "application/x-www-form-urlencoded" -WebSession $session -UseBasicParsing

        Write-Host "✅ $operation slot $slotId" -ForegroundColor Cyan
        return $true
    }
    catch {
        Write-Host "❌ Failed to $operation slot $slotId`: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Main test
Write-Host "Logging in..." -ForegroundColor Yellow
$session = Test-Login
if (-not $session) {
    Write-Host "Cannot continue without login" -ForegroundColor Red
    exit 1
}

Write-Host "Creating $SlotCount slots..." -ForegroundColor Yellow

$createdSlots = @()
for ($i = 1; $i -le $SlotCount; $i++) {
    $poNum = "TEST{0:D3}" -f $i
    $truckNum = "TRUCK{0:D3}" -f $i
    $warehouse = (Get-Random -Minimum 1 -Maximum 4)

    $slot = Test-CreateSlot -session $session -poNum $poNum -truckNum $truckNum -warehouse $warehouse
    if ($slot) {
        $createdSlots += $i  # Use simple ID
    }

    Start-Sleep -Milliseconds 1000
}

Write-Host "Processing $($createdSlots.Count) slots..." -ForegroundColor Yellow

# Process slots sequentially
foreach ($slotId in $createdSlots) {
    Write-Host "Processing slot $slotId..." -ForegroundColor Cyan

    # Arrival
    Test-ProgressSlot -session $session -slotId $slotId -operation "arrival"
    Start-Sleep -Milliseconds 1000

    # Start
    Test-ProgressSlot -session $session -slotId $slotId -operation "start"
    Start-Sleep -Milliseconds 1000

    # Complete
    Test-ProgressSlot -session $session -slotId -operation "complete"
    Start-Sleep -Milliseconds 500
}

Write-Host "`nTest completed!" -ForegroundColor Green
Write-Host "Created: $($createdSlots.Count) slots" -ForegroundColor Cyan
Write-Host "Processed: $($createdSlots.Count) slots" -ForegroundColor Cyan

# Test API endpoints
try {
    $dashboard = Invoke-WebRequest -Uri "$BaseUrl/dashboard" -WebSession $session -UseBasicParsing
    Write-Host "✅ Dashboard API working" -ForegroundColor Green
}
catch {
    Write-Host "❌ Dashboard API failed: $($_.Exception.Message)" -ForegroundColor Red
}

try {
    $gateStatus = Invoke-WebRequest -Uri "$BaseUrl/api/gate-status" -WebSession $session -UseBasicParsing
    Write-Host "✅ Gate status API working" -ForegroundColor Green
}
catch {
    Write-Host "❌ Gate status API failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nAll tests completed!" -ForegroundColor Green
