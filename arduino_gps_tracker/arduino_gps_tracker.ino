/*
  Haven Zen Arduino Uno GPS bridge

  Wiring for a common NEO-6M/NEO-7M GPS module:
  GPS VCC -> Arduino 5V or 3.3V, depending on your module board
  GPS GND -> Arduino GND
  GPS TX  -> Arduino D4
  GPS RX  -> Arduino D3, optional

  The sketch reads NMEA sentences from the GPS on SoftwareSerial and sends
  simple coordinate lines to the PC:

    GPS,11.30023340,124.76303070

  The PC bridge script then posts those coordinates to Haven Zen.
*/

#include <SoftwareSerial.h>

const byte GPS_RX_PIN = 4; // Arduino receives GPS TX here.
const byte GPS_TX_PIN = 3; // Optional Arduino TX to GPS RX.
const unsigned long PC_BAUD = 115200;
const unsigned long GPS_BAUD = 9600;

SoftwareSerial gpsSerial(GPS_RX_PIN, GPS_TX_PIN);
String nmeaLine = "";
unsigned long lastStatusAt = 0;
unsigned long lastGpsByteAt = 0;
unsigned long lastGpsSentenceAt = 0;

float nmeaToDecimal(String raw, String direction) {
  if (raw.length() < 4) {
    return 0.0;
  }

  int dotIndex = raw.indexOf('.');
  int degreesDigits = (dotIndex > 4) ? 3 : 2;
  float degrees = raw.substring(0, degreesDigits).toFloat();
  float minutes = raw.substring(degreesDigits).toFloat();
  float decimal = degrees + (minutes / 60.0);

  if (direction == "S" || direction == "W") {
    decimal *= -1.0;
  }

  return decimal;
}

String fieldAt(String sentence, int index) {
  int current = 0;
  int start = 0;

  for (int i = 0; i <= sentence.length(); i++) {
    if (i == sentence.length() || sentence.charAt(i) == ',') {
      if (current == index) {
        return sentence.substring(start, i);
      }
      current++;
      start = i + 1;
    }
  }

  return "";
}

void emitCoordinates(float latitude, float longitude) {
  if (latitude == 0.0 || longitude == 0.0) {
    return;
  }

  Serial.print("GPS,");
  Serial.print(latitude, 8);
  Serial.print(",");
  Serial.println(longitude, 8);
}

void parseNmea(String sentence) {
  sentence.trim();
  if (!sentence.startsWith("$")) {
    return;
  }

  if (sentence.startsWith("$GPRMC") || sentence.startsWith("$GNRMC")) {
    String fixStatus = fieldAt(sentence, 2);
    if (fixStatus != "A") {
      return;
    }

    float latitude = nmeaToDecimal(fieldAt(sentence, 3), fieldAt(sentence, 4));
    float longitude = nmeaToDecimal(fieldAt(sentence, 5), fieldAt(sentence, 6));
    emitCoordinates(latitude, longitude);
    return;
  }

  if (sentence.startsWith("$GPGGA") || sentence.startsWith("$GNGGA")) {
    int fixQuality = fieldAt(sentence, 6).toInt();
    if (fixQuality <= 0) {
      return;
    }

    float latitude = nmeaToDecimal(fieldAt(sentence, 2), fieldAt(sentence, 3));
    float longitude = nmeaToDecimal(fieldAt(sentence, 4), fieldAt(sentence, 5));
    emitCoordinates(latitude, longitude);
  }
}

void setup() {
  Serial.begin(PC_BAUD);
  gpsSerial.begin(GPS_BAUD);
  nmeaLine.reserve(120);

  Serial.println("HAVENZEN_GPS_BRIDGE_READY");
  Serial.println("Waiting for GPS fix...");
}

void loop() {
  while (gpsSerial.available()) {
    char c = gpsSerial.read();
    lastGpsByteAt = millis();

    if (c == '\n') {
      lastGpsSentenceAt = millis();
      parseNmea(nmeaLine);
      nmeaLine = "";
    } else if (c != '\r') {
      if (nmeaLine.length() < 120) {
        nmeaLine += c;
      } else {
        nmeaLine = "";
      }
    }
  }

  if (millis() - lastStatusAt > 10000) {
    if (lastGpsByteAt == 0 || millis() - lastGpsByteAt > 15000) {
      Serial.println("NO_GPS_SERIAL_DATA_CHECK_WIRING_D4");
    } else if (lastGpsSentenceAt == 0 || millis() - lastGpsSentenceAt > 15000) {
      Serial.println("GPS_BYTES_SEEN_WAITING_FOR_SENTENCE");
    } else {
      Serial.println("GPS_SIGNAL_SEEN_WAITING_FOR_VALID_FIX");
    }
    lastStatusAt = millis();
  }
}
