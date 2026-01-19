# Simple Warehouse Test Script
param(
    [int]$SlotCount = 20,
    [string]$BaseUrl = "http://localhost:8000"
)

Write-Host "Starting warehouse test..." -ForegroundColor Green

# Login function
function Test-Login {
    try {
        # Get login page first to get CSRF token
        $loginPage = Invoke-WebRequest -Uri "$BaseUrl/login" -Method GET
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

        # Login with CSRF token
        $body = "username=admin&password=password&_token=$csrfToken"
        $response = Invoke-RestMethod -Uri "$BaseUrl/login" -Method POST -Headers @{
            "Content-Type" = "application/x-www-form-urlencoded"
        } -Body $body

        # Create session
        $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
        # Add cookies from response
        if ($loginPage.Headers."Set-Cookie") {
            foreach ($cookie in $loginPage.Headers."Set-Cookie") {
                $session.Cookies.SetCookies($BaseUrl, $cookie)
            }
        }

        return $session
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

        $body = $data.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Out-String -Stream | ForEach-Object { $_.Trim() } | Where-Object { $_ } | Join-String -Separator "&"

        $response = Invoke-RestMethod -Uri "$BaseUrl/slots" -Method POST -WebSession $session -Headers @{
            "Content-Type" = "application/x-www-form-urlencoded"
        } -Body $body

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

        $body = $data.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Out-String -Stream | ForEach-Object { $_.Trim() } | Where-Object { $_ } | Join-String -Separator "&"

        $response = Invoke-RestMethod -Uri "$BaseUrl/slots/$slotId/$operation" -Method POST -WebSession $session -Headers @{
            "Content-Type" = "application/x-www-form-urlencoded"
        } -Body $body

        Write-Host "✅ $operation slot $slotId" -ForegroundColor Cyan
        return $true
    }
    catch {
        Write-Host "❌ Failed to $operation slot $slotId`: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
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
    if ($slot -and $slot.id) {
        $createdSlots += $slot.id
    }

    Start-Sleep -Milliseconds 200
}

Write-Host "Processing $($createdSlots.Count) slots..." -ForegroundColor Yellow

# Process slots in batches
$batchSize = 5
for ($i = 0; $i -lt $createdSlots.Count; $i += $batchSize) {
    $batch = $createdSlots[$i..[math]::Min($i + $batchSize - 1, $createdSlots.Count - 1)]

    Write-Host "Processing batch $($i/$batchSize + 1)..." -ForegroundColor Cyan

    # Concurrent processing
    $jobs = @()
    foreach ($slotId in $batch) {
        $job = Start-Job -ScriptBlock {
            param($baseUrl, $slotId)

            # Get CSRF token first
            $loginPage = Invoke-WebRequest -Uri "$baseUrl/login" -Method GET
            $html = $loginPage.Content
            $csrfToken = ""
            if ($html -match '<meta name="csrf-token" content="([^"]+)"') {
                $csrfToken = $matches[1]
            } elseif ($html -match '<input[^>]*name="_token"[^>]*value="([^"]+)"') {
                $csrfToken = $matches[1]
            }

            $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
            if ($loginPage.Headers."Set-Cookie") {
                foreach ($cookie in $loginPage.Headers."Set-Cookie") {
                    $session.Cookies.SetCookies($baseUrl, $cookie)
                }
            }

            # Login with CSRF
            $body = "username=admin&password=password&_token=$csrfToken"
            Invoke-RestMethod -Uri "$baseUrl/login" -Method POST -WebSession $session -Headers @{
                "Content-Type" = "application/x-www-form-urlencoded"
            } -Body $body

            # Arrival
            $arrivalData = @{
                ticket_number = "A$(Get-Date -UFormat %s)"
                sj_number = "SJ$slotId"
                truck_type = "Container 20ft"
                actual_arrival = (Get-Date).ToString("yyyy-MM-dd HH:mm")
                actual_gate_id = 1
            }
            $arrivalBody = $arrivalData.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Out-String -Stream | ForEach-Object { $_.Trim() } | Where-Object { $_ } | Join-String -Separator "&"
            Invoke-RestMethod -Uri "$baseUrl/slots/$slotId/arrival" -Method POST -WebSession $session -Headers @{
                "Content-Type" = "application/x-www-form-urlencoded"
            } -Body $arrivalBody

            Start-Sleep -Milliseconds 500

            # Start
            $startBody = "actual_gate_id=1"
            Invoke-RestMethod -Uri "$baseUrl/slots/$slotId/start" -Method POST -WebSession $session -Headers @{
                "Content-Type" = "application/x-www-form-urlencoded"
            } -Body $startBody

            Start-Sleep -Milliseconds 500

            # Complete
            $completeData = @{
                mat_doc = "MD$slotId"
                sj_number = "SJ$slotId"
                truck_type = "Container 20ft"
                vehicle_number = "B$slotId"
                driver_number = "DRV$slotId"
                actual_finish = (Get-Date).ToString("yyyy-MM-dd HH:mm")
            }
            $completeBody = $completeData.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Out-String -Stream | ForEach-Object { $_.Trim() } | Where-Object { $_ } | Join-String -Separator "&"
            Invoke-RestMethod -Uri "$baseUrl/slots/$slotId/complete" -Method POST -WebSession $session -Headers @{
                "Content-Type" = "application/x-www-form-urlencoded"
            } -Body $completeBody
        } -ArgumentList $BaseUrl, $slotId

        $jobs += $job
    }

    # Wait for batch completion
    $jobs | Wait-Job | Receive-Job | Out-Null
    $jobs | Remove-Job

    Write-Host "Batch completed" -ForegroundColor Green
    Start-Sleep -Seconds 1
}

Write-Host "`nTest completed!" -ForegroundColor Green
Write-Host "Created: $($createdSlots.Count) slots" -ForegroundColor Cyan
Write-Host "Processed: $($createdSlots.Count) slots" -ForegroundColor Cyan

# Test API endpoints
try {
    $dashboard = Invoke-RestMethod -Uri "$BaseUrl/dashboard" -WebSession $session
    Write-Host "✅ Dashboard API working" -ForegroundColor Green
}
catch {
    Write-Host "❌ Dashboard API failed" -ForegroundColor Red
}

try {
    $gateStatus = Invoke-RestMethod -Uri "$BaseUrl/api/gate-status" -WebSession $session
    Write-Host "✅ Gate status API working" -ForegroundColor Green
}
catch {
    Write-Host "❌ Gate status API failed" -ForegroundColor Red
}

Write-Host "`nAll tests completed!" -ForegroundColor Green
