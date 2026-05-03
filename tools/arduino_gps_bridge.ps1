param(
    [string]$PortName = "COM7",
    [int]$BaudRate = 115200,
    [int]$VehicleId = 3,
    [string]$ServerUrl = "http://localhost:8000/api/gps_tracking.php",
    [string]$ApiKey = "change-this-gps-api-key",
    [int]$PostIntervalSeconds = 5
)

$ErrorActionPreference = "Stop"

function Convert-NmeaCoordinate {
    param(
        [string]$Raw,
        [string]$Direction
    )

    if ([string]::IsNullOrWhiteSpace($Raw) -or $Raw.Length -lt 4) {
        return $null
    }

    $dot = $Raw.IndexOf(".")
    $degreeDigits = if ($dot -gt 4) { 3 } else { 2 }
    $degrees = [double]$Raw.Substring(0, $degreeDigits)
    $minutes = [double]$Raw.Substring($degreeDigits)
    $decimal = $degrees + ($minutes / 60.0)

    if ($Direction -in @("S", "W")) {
        $decimal *= -1
    }

    return $decimal
}

function Try-ParseGpsLine {
    param(
        [string]$Line,
        [ref]$Latitude,
        [ref]$Longitude
    )

    $line = $Line.Trim()
    if ($line -match '^GPS,\s*(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)') {
        $Latitude.Value = [double]$matches[1]
        $Longitude.Value = [double]$matches[2]
        return $true
    }

    if ($line -match 'LAT(?:ITUDE)?\s*[:=]\s*(-?\d+(?:\.\d+)?).*(?:LNG|LON|LONGITUDE)\s*[:=]\s*(-?\d+(?:\.\d+)?)') {
        $Latitude.Value = [double]$matches[1]
        $Longitude.Value = [double]$matches[2]
        return $true
    }

    if ($line -match '^\$G[NP]RMC,') {
        $parts = $line.Split(',')
        if ($parts.Count -ge 7 -and $parts[2] -eq 'A') {
            $lat = Convert-NmeaCoordinate $parts[3] $parts[4]
            $lng = Convert-NmeaCoordinate $parts[5] $parts[6]
            if ($lat -ne $null -and $lng -ne $null) {
                $Latitude.Value = $lat
                $Longitude.Value = $lng
                return $true
            }
        }
    }

    if ($line -match '^\$G[NP]GGA,') {
        $parts = $line.Split(',')
        $fixQuality = 0
        if ($parts.Count -ge 7 -and [int]::TryParse($parts[6], [ref]$fixQuality) -and $fixQuality -gt 0) {
            $lat = Convert-NmeaCoordinate $parts[2] $parts[3]
            $lng = Convert-NmeaCoordinate $parts[4] $parts[5]
            if ($lat -ne $null -and $lng -ne $null) {
                $Latitude.Value = $lat
                $Longitude.Value = $lng
                return $true
            }
        }
    }

    return $false
}

function Send-GpsLocation {
    param(
        [double]$Latitude,
        [double]$Longitude
    )

    $body = @{
        api_key = $ApiKey
        vehicle_id = $VehicleId
        latitude = "{0:F8}" -f $Latitude
        longitude = "{0:F8}" -f $Longitude
    }

    Invoke-RestMethod -Uri $ServerUrl -Method Post -Body $body -TimeoutSec 10 | Out-Null
}

Write-Host "Haven Zen Arduino GPS bridge"
Write-Host "Port: $PortName @ $BaudRate"
Write-Host "Vehicle ID: $VehicleId"
Write-Host "Server: $ServerUrl"
Write-Host "Press Ctrl+C to stop."

$serial = [System.IO.Ports.SerialPort]::new($PortName, $BaudRate, [System.IO.Ports.Parity]::None, 8, [System.IO.Ports.StopBits]::One)
$serial.ReadTimeout = 1000
$serial.DtrEnable = $true
$serial.RtsEnable = $true

$lastPostAt = [datetime]::MinValue

try {
    $serial.Open()
    Start-Sleep -Milliseconds 1500

    while ($true) {
        try {
            $line = $serial.ReadLine()
        } catch [System.TimeoutException] {
            continue
        }

        if ([string]::IsNullOrWhiteSpace($line)) {
            continue
        }

        Write-Host ("SERIAL: " + $line.Trim())

        $lat = 0.0
        $lng = 0.0
        if (Try-ParseGpsLine -Line $line -Latitude ([ref]$lat) -Longitude ([ref]$lng)) {
            if ((Get-Date) -lt $lastPostAt.AddSeconds($PostIntervalSeconds)) {
                continue
            }

            try {
                Send-GpsLocation -Latitude $lat -Longitude $lng
                $lastPostAt = Get-Date
                Write-Host ("POSTED: vehicle {0} -> {1:F8}, {2:F8}" -f $VehicleId, $lat, $lng) -ForegroundColor Green
            } catch {
                Write-Host ("POST FAILED: " + $_.Exception.Message) -ForegroundColor Red
            }
        }
    }
} finally {
    if ($serial.IsOpen) {
        $serial.Close()
    }
}
