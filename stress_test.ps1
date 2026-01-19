# Stress Testing - Concurrent Users Simulation
# Simulasi 10+ users bersamaan di rush hour

param(
    [int]$ConcurrentUsers = 10,
    [int]$SlotsPerUser = 5,
    [string]$BaseUrl = "http://localhost:8000"
)

Write-Host "🚀 Stress Testing - STM2 Warehouse System" -ForegroundColor Magenta
Write-Host "=========================================" -ForegroundColor Magenta
Write-Host "Concurrent Users: $ConcurrentUsers" -ForegroundColor Cyan
Write-Host "Slots per User: $SlotsPerUser" -ForegroundColor Cyan
Write-Host "Total Operations: $($ConcurrentUsers * $SlotsPerUser)" -ForegroundColor Cyan

# Fungsi untuk simulasi user behavior
function Simulate-User {
    param(
        [int]$UserId,
        [int]$SlotCount,
        [string]$BaseUrl
    )

    $userResults = @{
        UserId = $UserId
        SlotsCreated = 0
        SlotsCompleted = 0
        Errors = @()
        StartTime = Get-Date
    }

    try {
        # Login sebagai user (dalam simulasi, semua sebagai admin)
        $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

        # Login
        $loginData = "username=admin&password=password"
        $loginResponse = Invoke-RestMethod -Uri "$BaseUrl/login" -Method POST -WebSession $session -Headers @{
            "Content-Type" = "application/x-www-form-urlencoded"
        } -Body $loginData

        Write-Host "👤 User $UserId logged in" -ForegroundColor Green

        # Create slots dengan delay random
        for ($i = 1; $i -le $SlotCount; $i++) {
            try {
                $poNum = "STRESS{0:D2}{1:D3}" -f $UserId, $i
                $truckNum = "STRESS{0:D2}{1:D3}" -f $UserId, $i
                $warehouse = Get-Random -Minimum 1 -Maximum 4
                $delay = Get-Random -Minimum 1 -Maximum 30
                $startTime = (Get-Date).AddMinutes($delay).ToString("yyyy-MM-dd HH:mm")

                # Create slot
                $slotData = @{
                    po_number = $poNum
                    truck_number = $truckNum
                    truck_type = "Container 20ft"
                    direction = "inbound"
                    warehouse_id = $warehouse
                    vendor_id = 1
                    planned_start = $startTime
                }

                $body = $slotData.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Join-String -Separator "&"
                $createResponse = Invoke-RestMethod -Uri "$BaseUrl/slots" -Method POST -WebSession $session -Headers @{
                    "Content-Type" = "application/x-www-form-urlencoded"
                } -Body $body

                $userResults.SlotsCreated++

                # Random delay antar operasi
                Start-Sleep -Milliseconds (Get-Random -Minimum 100 -Maximum 500)

                # Simulasi progression (arrival -> start -> complete)
                if ($createResponse.id) {
                    $slotId = $createResponse.id

                    # Arrival
                    $arrivalData = @{
                        ticket_number = "STRESS$(Get-Date -UFormat %s)"
                        sj_number = "SJ$slotId"
                        truck_type = "Container 20ft"
                        actual_arrival = (Get-Date).ToString("yyyy-MM-dd HH:mm")
                        actual_gate_id = 1
                    }
                    $arrivalBody = $arrivalData.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Join-String -Separator "&"
                    Invoke-RestMethod -Uri "$BaseUrl/slots/$slotId/arrival" -Method POST -WebSession $session -Headers @{
                        "Content-Type" = "application/x-www-form-urlencoded"
                    } -Body $arrivalBody

                    Start-Sleep -Milliseconds (Get-Random -Minimum 200 -Maximum 800)

                    # Start
                    $startData = @{ actual_gate_id = 1 }
                    $startBody = $startData.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Join-String -Separator "&"
                    Invoke-RestMethod -Uri "$BaseUrl/slots/$slotId/start" -Method POST -WebSession $session -Headers @{
                        "Content-Type" = "application/x-www-form-urlencoded"
                    } -Body $startBody

                    Start-Sleep -Milliseconds (Get-Random -Minimum 300 -Maximum 1000)

                    # Complete
                    $completeData = @{
                        mat_doc = "MD$slotId"
                        sj_number = "SJ$slotId"
                        truck_type = "Container 20ft"
                        vehicle_number = "B$slotId"
                        driver_number = "DRV$slotId"
                        actual_finish = (Get-Date).ToString("yyyy-MM-dd HH:mm")
                    }
                    $completeBody = $completeData.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Join-String -Separator "&"
                    Invoke-RestMethod -Uri "$BaseUrl/slots/$slotId/complete" -Method POST -WebSession $session -Headers @{
                        "Content-Type" = "application/x-www-form-urlencoded"
                    } -Body $completeBody

                    $userResults.SlotsCompleted++
                }

                Write-Host "✅ User $UserId: Slot $i/$SlotCount completed" -ForegroundColor Green
            }
            catch {
                $userResults.Errors += "Slot $i`: $($_.Exception.Message)"
                Write-Host "❌ User $UserId: Slot $i failed - $($_.Exception.Message)" -ForegroundColor Red
            }
        }
    }
    catch {
        $userResults.Errors += "Login failed: $($_.Exception.Message)"
        Write-Host "❌ User $UserId: Login failed - $($_.Exception.Message)" -ForegroundColor Red
    }

    $userResults.EndTime = Get-Date
    $userResults.Duration = ($userResults.EndTime - $userResults.StartTime).TotalSeconds

    return $userResults
}

# Jalankan stress test
Write-Host "`n🏁 Starting stress test..." -ForegroundColor Yellow
$testStartTime = Get-Date

# Start semua concurrent users
$userJobs = @()
for ($userId = 1; $userId -le $ConcurrentUsers; $userId++) {
    $job = Start-Job -ScriptBlock {
        param($baseUrl, $userId, $slotCount)

        # Import fungsi
        function Simulate-User {
            param(
                [int]$UserId,
                [int]$SlotCount,
                [string]$BaseUrl
            )

            $userResults = @{
                UserId = $UserId
                SlotsCreated = 0
                SlotsCompleted = 0
                Errors = @()
                StartTime = Get-Date
            }

            try {
                $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
                $loginData = "username=admin&password=password"
                $loginResponse = Invoke-RestMethod -Uri "$BaseUrl/login" -Method POST -WebSession $session -Headers @{
                    "Content-Type" = "application/x-www-form-urlencoded"
                } -Body $loginData

                for ($i = 1; $i -le $SlotCount; $i++) {
                    try {
                        $poNum = "STRESS{0:D2}{1:D3}" -f $UserId, $i
                        $truckNum = "STRESS{0:D2}{1:D3}" -f $UserId, $i
                        $warehouse = Get-Random -Minimum 1 -Maximum 4
                        $delay = Get-Random -Minimum 1 -Maximum 30
                        $startTime = (Get-Date).AddMinutes($delay).ToString("yyyy-MM-dd HH:mm")

                        $slotData = @{
                            po_number = $poNum
                            truck_number = $truckNum
                            truck_type = "Container 20ft"
                            direction = "inbound"
                            warehouse_id = $warehouse
                            vendor_id = 1
                            planned_start = $startTime
                        }

                        $body = $slotData.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Join-String -Separator "&"
                        $createResponse = Invoke-RestMethod -Uri "$BaseUrl/slots" -Method POST -WebSession $session -Headers @{
                            "Content-Type" = "application/x-www-form-urlencoded"
                        } -Body $body

                        $userResults.SlotsCreated++
                        Start-Sleep -Milliseconds (Get-Random -Minimum 100 -Maximum 500)

                        if ($createResponse.id) {
                            $slotId = $createResponse.id

                            $arrivalData = @{
                                ticket_number = "STRESS$(Get-Date -UFormat %s)"
                                sj_number = "SJ$slotId"
                                truck_type = "Container 20ft"
                                actual_arrival = (Get-Date).ToString("yyyy-MM-dd HH:mm")
                                actual_gate_id = 1
                            }
                            $arrivalBody = $arrivalData.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Join-String -Separator "&"
                            Invoke-RestMethod -Uri "$BaseUrl/slots/$slotId/arrival" -Method POST -WebSession $session -Headers @{
                                "Content-Type" = "application/x-www-form-urlencoded"
                            } -Body $arrivalBody

                            Start-Sleep -Milliseconds (Get-Random -Minimum 200 -Maximum 800)

                            $startData = @{ actual_gate_id = 1 }
                            $startBody = $startData.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Join-String -Separator "&"
                            Invoke-RestMethod -Uri "$BaseUrl/slots/$slotId/start" -Method POST -WebSession $session -Headers @{
                                "Content-Type" = "application/x-www-form-urlencoded"
                            } -Body $startBody

                            Start-Sleep -Milliseconds (Get-Random -Minimum 300 -Maximum 1000)

                            $completeData = @{
                                mat_doc = "MD$slotId"
                                sj_number = "SJ$slotId"
                                truck_type = "Container 20ft"
                                vehicle_number = "B$slotId"
                                driver_number = "DRV$slotId"
                                actual_finish = (Get-Date).ToString("yyyy-MM-dd HH:mm")
                            }
                            $completeBody = $completeData.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Join-String -Separator "&"
                            Invoke-RestMethod -Uri "$BaseUrl/slots/$slotId/complete" -Method POST -WebSession $session -Headers @{
                                "Content-Type" = "application/x-www-form-urlencoded"
                            } -Body $completeBody

                            $userResults.SlotsCompleted++
                        }
                    }
                    catch {
                        $userResults.Errors += "Slot $i`: $($_.Exception.Message)"
                    }
                }
            }
            catch {
                $userResults.Errors += "Login failed: $($_.Exception.Message)"
            }

            $userResults.EndTime = Get-Date
            $userResults.Duration = ($userResults.EndTime - $userResults.StartTime).TotalSeconds

            return $userResults
        }

        return Simulate-User -UserId $userId -SlotCount $slotCount -BaseUrl $baseUrl
    } -ArgumentList $BaseUrl, $userId, $SlotsPerUser

    $userJobs += $job

    # Stagger user start times
    Start-Sleep -Milliseconds 200
}

# Wait semua jobs selesai
Write-Host "`n⏳ Waiting for all users to complete..." -ForegroundColor Yellow
$allResults = @()
foreach ($job in $userJobs) {
    $result = Receive-Job -Job $job -Wait
    $allResults += $result
    Remove-Job -Job $job
}

$testEndTime = Get-Date
$totalTestDuration = ($testEndTime - $testStartTime).TotalSeconds

# Generate report
Write-Host "`n📊 STRESS TEST RESULTS" -ForegroundColor Magenta
Write-Host "=====================" -ForegroundColor Magenta

$totalSlotsCreated = ($allResults | Measure-Object -Property SlotsCreated -Sum).Sum
$totalSlotsCompleted = ($allResults | Measure-Object -Property SlotsCompleted -Sum).Sum
$totalErrors = ($allResults | ForEach-Object { $_.Errors.Count } | Measure-Object -Sum).Sum
$avgDuration = ($allResults | Measure-Object -Property Duration -Average).Average

Write-Host "📈 Test Summary:" -ForegroundColor Cyan
Write-Host "  Total Test Duration: $([math]::Round($totalTestDuration, 2)) seconds" -ForegroundColor White
Write-Host "  Total Slots Created: $totalSlotsCreated" -ForegroundColor Green
Write-Host "  Total Slots Completed: $totalSlotsCompleted" -ForegroundColor Green
Write-Host "  Total Errors: $totalErrors" -ForegroundColor $(if ($totalErrors -gt 0) { "Red" } else { "Green" })
Write-Host "  Average User Duration: $([math]::Round($avgDuration, 2)) seconds" -ForegroundColor White

Write-Host "`n👥 User Performance:" -ForegroundColor Cyan
foreach ($result in $allResults) {
    $status = if ($result.Errors.Count -eq 0) { "✅" } else { "❌" }
    Write-Host "  User $($result.UserId.PadLeft(2)): $status $($result.SlotsCreated) created, $($result.SlotsCompleted) completed, $($result.Errors.Count) errors ($([math]::Round($result.Duration, 2))s)" -ForegroundColor White
}

Write-Host "`n⚡ Performance Metrics:" -ForegroundColor Cyan
$throughput = $totalSlotsCompleted / $totalTestDuration
Write-Host "  Throughput: $([math]::Round($throughput, 2)) slots/second" -ForegroundColor Green
Write-Host "  Success Rate: $([math]::Round(($totalSlotsCompleted / $totalSlotsCreated) * 100, 2))%" -ForegroundColor $(if (($totalSlotsCompleted / $totalSlotsCreated) * 100) -ge 95 { "Green" } else { "Yellow" })

if ($totalErrors -gt 0) {
    Write-Host "`n❌ Error Summary:" -ForegroundColor Red
    $allResults | ForEach-Object {
        if ($_.Errors.Count -gt 0) {
            Write-Host "  User $($_.UserId):" -ForegroundColor Red
            $_.Errors | ForEach-Object { Write-Host "    - $_" -ForegroundColor Red }
        }
    }
}

Write-Host "`n🎯 Performance Benchmarks:" -ForegroundColor Yellow
if ($throughput -ge 5) {
    Write-Host "  ✅ Excellent: >5 slots/second" -ForegroundColor Green
} elseif ($throughput -ge 3) {
    Write-Host "  ✅ Good: 3-5 slots/second" -ForegroundColor Green
} elseif ($throughput -ge 1) {
    Write-Host "  ⚠️  Acceptable: 1-3 slots/second" -ForegroundColor Yellow
} else {
    Write-Host "  ❌ Poor: <1 slot/second" -ForegroundColor Red
}

$successRate = ($totalSlotsCompleted / $totalSlotsCreated) * 100
if ($successRate -ge 95) {
    Write-Host "  ✅ Excellent: ≥95% success rate" -ForegroundColor Green
} elseif ($successRate -ge 90) {
    Write-Host "  ⚠️  Good: 90-95% success rate" -ForegroundColor Yellow
} else {
    Write-Host "  ❌ Poor: <90% success rate" -ForegroundColor Red
}

Write-Host "`n🏁 Stress test completed!" -ForegroundColor Green
