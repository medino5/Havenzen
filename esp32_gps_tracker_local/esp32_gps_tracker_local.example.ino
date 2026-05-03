#include <WiFi.h>
#include <HTTPClient.h>

// Copy this file to esp32_gps_tracker_local.ino and fill in local values before uploading.
// Keep real Wi-Fi names, passwords, server IPs, and API keys out of Git.

const char* WIFI_SSID = "your-wifi-or-hotspot-name";
const char* WIFI_PASSWORD = "your-wifi-password";
const char* SERVER_URL = "http://your-server-ip-or-domain/havenzen/api/gps_tracking.php";
const char* API_KEY = "change-this-gps-api-key";

const int VEHICLE_ID = 3;

void setup() {
  Serial.begin(115200);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
}

void loop() {
  // Use the local full tracker sketch for GPS parsing and posting.
  // This template exists so the repository documents required config without secrets.
}
