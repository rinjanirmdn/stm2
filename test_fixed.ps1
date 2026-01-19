# Simple Warehouse Test Script - CSRF Fixed
param(
    [int]$SlotCount = 20,
    [string]$BaseUrl = "http://localhost:8000"
)

Write-Host "Starting warehouse test..." -ForegroundColor Green

# Login function with proper session handling
function Test-Login {
    try {
        # Disable security warnings for testing
        $ProgressPreference = 'SilentlyContinue'

        # Get login page first to get CSRF token and cookies
        $loginPage = Invoke-WebRequest -Uri "$BaseUrl/login" -Method GET -SessionVariable 'session'
        $html = $loginPage.Content

        # Extract CSRF token
        $csrfToken = ""
        if ($html -match '<meta name="csrf-token" content="([^"]+)"') {
            $csrfToken = $matches[1]
        } elseif ($html -match '<input[^>]*name="_token"[^>]*value="([^"]+)"') {
            $csrfToken = $matches[1]
        }

        if (-not $csrfToken) {
            Write-Host "Could not extract CSRF token" -ForegroundColor Red
            return $null
        }

        Write-Host "CSRF token extracted: $csrfToken" -ForegroundColor Yellow

        # Login with CSRF token using same session
        $loginData = @{
            username = "admin"
            password = "password"
            _token = $csrfToken
        }

        $body = $loginData.GetEnumerator() | ForEach-Object {
            [System.Web.HttpUtility]::UrlEncode($_.Key) + "=" + [System.Web.HttpUtility]::UrlEncode($_.Value)
        } | Out-String -Stream | ForEach-Object { $_.Trim() } | Where-Object { $_ }

        # Join manually for compatibility
        $bodyArray = $body -split "`n"
        $bodyString = $bodyArray -join "&"

        $response = Invoke-WebRequest -Uri "$BaseUrl/login" -Method POST -WebSession $session -Headers @{
            "Content-Type" = "application/x-www-form-urlencoded"
            "Referer" = "$BaseUrl/login"
        } -Body $bodyString

        Write-Host "Login successful!" -ForegroundColor Green
        return $session
    }
    catch {
        Write-Host "Login failed: $($_.Exception.Message)" -ForegroundColor Red
        if ($_.Exception.Response) {
            Write-Host "Status: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
        }
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

        $body = $data.GetEnumerator() | ForEach-Object {
            [System.Web.HttpUtility]::UrlEncode($_.Key) + "=" + [System.Web.HttpUtility]::UrlEncode($_.Value)
        } | Out-String -Stream | ForEach-Object { $_.Trim() } | Where-Object { $_ } | Join-String -Separator "&"

        $response = Invoke-WebRequest -Uri "$BaseUrl/slots" -Method POST -WebSession $session -Headers @{
            "Content-Type" = "application/x-www-form-urlencoded"
            "Referer" = "$BaseUrl/slots/create"
        } -Body $body

        Write-Host "✅ Created slot: $poNum" -ForegroundColor Green
        return $response
    }
    catch {
        Write-Host "❌ Failed to create slot $poNum`: $($_.Exception.Message)" -ForegroundColor Red
        if ($_.Exception.Response) {
            Write-Host "Status: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
        }
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

        $body = $data.GetEnumerator() | ForEach-Object {
            [System.Web.HttpUtility]::UrlEncode($_.Key) + "=" + [System.Web.HttpUtility]::UrlEncode($_.Value)
        } | Out-String -Stream | ForEach-Object { $_.Trim() } | Where-Object { $_ } | Join-String -Separator "&"

        $response = Invoke-WebRequest -Uri "$BaseUrl/slots/$slotId/$operation" -Method POST -WebSession $session -Headers @{
            "Content-Type" = "application/x-www-form-urlencoded"
            "Referer" = "$BaseUrl/slots/$slotId"
        } -Body $body

        Write-Host "✅ $operation slot $slotId" -ForegroundColor Cyan
        return $true
    }
    catch {
        Write-Host "❌ Failed to $operation slot $slotId`: $($_.Exception.Message)" -ForegroundColor Red
        if ($_.Exception.Response) {
            Write-Host "Status: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
        }
        return $false
    }
}

# Load System.Web for URL encoding
try {
    Add-Type -AssemblyName System.Web
}
catch {
    Write-Host "Warning: Could not load System.Web assembly" -ForegroundColor Yellow
}

# Main test
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
    if ($slot -and $slot.Content) {
        # Try to extract slot ID from response
        if ($slot.Content -match 'id["\s]*:["\s]*(\d+)') {
            $createdSlots += [int]$matches[1]
        } else {
            $createdSlots += $i  # Fallback
        }
    }

    Start-Sleep -Milliseconds 500
}

Write-Host "Processing $($createdSlots.Count) slots..." -ForegroundColor Yellow

# Process slots sequentially to avoid session conflicts
foreach ($slotId in $createdSlots) {
    Write-Host "Processing slot $slotId..." -ForegroundColor Cyan

    # Arrival
    Test-ProgressSlot -session $session -slotId $slotId -operation "arrival"
    Start-Sleep -Milliseconds 1000

    # Start
    Test-ProgressSlot -session $session -slotId $slotId -operation "start"
    Start-Sleep -Milliseconds 1000

    # Complete
    Test-ProgressSlot -session $session -slotId $slotId -operation "complete"
    Start-Sleep -Milliseconds 500
}

Write-Host "`nTest completed!" -ForegroundColor Green
Write-Host "Created: $($createdSlots.Count) slots" -ForegroundColor Cyan
Write-Host "Processed: $($createdSlots.Count) slots" -ForegroundColor Cyan

# Test API endpoints
try {
    $dashboard = Invoke-WebRequest -Uri "$BaseUrl/dashboard" -WebSession $session
    Write-Host "✅ Dashboard API working" -ForegroundColor Green
}
catch {
    Write-Host "❌ Dashboard API failed: $($_.Exception.Message)" -ForegroundColor Red
}

try {
    $gateStatus = Invoke-WebRequest -Uri "$BaseUrl/api/gate-status" -WebSession $session
    Write-Host "✅ Gate status API working" -ForegroundColor Green
}
catch {
    Write-Host "❌ Gate status API failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nAll tests completed!" -ForegroundColor Green
