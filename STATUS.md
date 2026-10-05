# Aktueller Entwicklungsstand / Architecture Notes

## Verschlüsselte DTU-Kommunikation (Version 1.23, Testversion)

Anlass: Forum https://community.symcon.de/t/modul-hoymiles-wifi-series/135536/74 („Data has wrong length.“ nach Firmware-Update der DTU).

- Referenz: https://github.com/suaveolent/hoymiles-wifi (`dtu.py`, `crypt_util.py`).
- `libs/HoymilesCrypt.php`: `Encryption` (AES-128-GCM, Key/Nonce per 3x SHA256 aus `enc_rand`), minimaler `Protobuf`-Codec und `AppInfo` (0xA301/0xA201) ohne protoc-generierte Klassen.
- IO: `SendCommand` prüft vor dem ersten verschlüsselbaren Kommando per AppInfo (`dtu_info.dfs` Bit 25, `enc_rand` Feld 27), ob verschlüsselt wird. Zustand liegt in den Buffern `EncryptionChecked`/`EncRand` und wird in `ApplyChanges` zurückgesetzt.
- Verschlüsseltes Format: Header-Länge und CRC16 ohne den 16 Byte Auth-Tag. Ausnahmen (immer Klartext): 0xA301, 0xA201, 0xA302.
- Kommt eine verschlüsselte Antwort, obwohl die DTU als unverschlüsselt gilt (Länge = Header + 16), wird AppInfo neu abgefragt und das Kommando einmal wiederholt.
- `ReadFrame` liest bis zur Länge aus dem Header (vorher nur ein `fread`).
- Getestet: Referenzvektor gegen Python (`tests/CryptTest.php`), Fake-DTU (verschlüsselt, unverschlüsselt, Wechsel zur Laufzeit, fragmentierte Frames), echte unverschlüsselte DTU lokal.

## Protobuf-Runtime 5.36.2 (Version 1.23)

- `libs/Google` und `libs/GPBMetadata/Google` komplett durch https://github.com/protocolbuffers/protobuf-php v5.36.2 (`src/`) ersetzt. Behebt „Using null as an array offset“ Deprecations. Benötigt PHP >= 8.2.
- Die generierten Klassen in `libs/Hoymiles` bleiben unverändert; `Internal\RepeatedField` existiert als Alias weiter.
- Unterschied zur alten Runtime: Felder mit Default-Wert werden weggelassen. Daher im IO `serializeToJsonString(\Google\Protobuf\PrintOptions::EMIT_DEFAULTS)`, sonst würden die Kind-Instanzen z.B. `"p":0` nie erhalten (sie aktualisieren nur vorhandene Keys). Beim binären Serialisieren entfallen Null-Felder (proto3-konform, wie hoymiles-wifi); RealData und Leistungslimit (0xA305, 100 % → 80 % → 100 %) am 05.10.2026 live mit unverschlüsselter DTU geprüft.

## Zusätzliche Werte und Funktionen aus hoymiles-wifi (Version 1.23)

- AppInfo (0xA301) liefert ohne Geheimnisse: `dtu_info.signal_strength` (Feld 9, identisch zu `wifi_rssi` aus GetConfig), DTU SW/HW (Feld 2/3), WLAN-Version (Feld 11), je Wechselrichter `pv_info` (Feld 11: SN 2, SW 4, HW 6). Versionsformatierung wie `hoymiles.py` (`generate_dtu_version_string`, `generate_sw_version_string`, `generate_version_string`).
- IO fragt AppInfo nach `CheckEncryption` erneut nach dem ersten RealData und dann alle 300 s ab (`AppInfo::Interval`, Buffer `LastAppInfo`). Zuordnung SN -> Inverter-Nummer über Buffer `InverterSerials` aus RealData.
- `ver` in InverterState ist die Inverter-Nummer (Filter der Inverter-Instanz), nicht die Firmware.
- Neue Variablen: DTU `signal`, `swVersion`, `hwVersion`, `wifiVersion`; Inverter `q` (0.1 var, Skalierung angenommen wie `p`), `wnum`, `swVersion`, `hwVersion`.
- SolarPort `code` (PvMO Feld 8) bewusst **keine** Variable: laut Firmware-Analyse im ioBroker-Adapter (Eistee82, `src/lib/deviceContext.ts`) kein Fehlercode, sondern drei Bytes aus dem Datenblock des WR (+0x37/+0x39/+0x3b, << 24/16/8), Bedeutung unbekannt, bei WB konstant 0x03000000, bei T-Serie 0. War kurzzeitig als Variable „Error code“ umgesetzt; auf dem Entwicklungssystem existieren dadurch noch die Variablen 51562 und 21673 (Löschen nur mit Freigabe des Nutzers).
- `HMSWIFI_RebootDTU` (Action 1) und `HMSWIFI_RebootInverter` (Action 8195, `dev_kind` 1, `mi_to_sn`) über 0x2305 (`CloudCommandResDTO`), wie hoymiles-wifi. Mit Fake-DTU getestet, **live noch nicht** (WR war abends offline).
- Debug: Rohdaten der Antwort 0xA201 (enthält `enc_rand`) werden maskiert, `EncRand` nur mit Länge ausgegeben.
- Test-Stubs (alt) leiten `SendDataToChildren` ohne DataID-Prüfung an alle Kinder weiter; im Harness landeten dadurch Inverter-Versionen in der DTU-Instanz. Live-Routing in Symcon prüfen.

## Backlog

### Warnungen / Alarme des Wechselrichters

Quelle: ioBroker-Adapter https://github.com/Eistee82/ioBroker.hoymiles (MIT, Copyright Eistee82; bei Übernahme von Code oder Tabellen den Hinweis erhalten).

- Action 50 (`ALARM_LIST`, 0xA305) liefert nur eine Quittung. Die Alarme kommen danach als `AlarmData` (`WInfoReqDTO`) mit Antwort-Tag 0xA204: je Eintrag `pv_sn`, `WCode`, `WNum`, `WTime1`, `WTime2`, `WData1`, `WData2` (`src/lib/proto/AlarmData.proto`). Der Adapter hält dafür eine Verbindung offen; prüfen, ob die DTU die Daten auch auf einer neuen Verbindung liefert.
- Zusätzlich `WarnData` (`WarnReqDTO`, `src/lib/proto/WarnData.proto`): seitenweise Liste, `package_now` 0-basiert, `package_nub` Anzahl; Folgeseiten aktiv nachfordern (`encodeWarnDataRequest`).
- Klartexte: 168 Codes in `src/lib/alarmCodes.ts` / `alarmCodesData.ts` (aus Hoymiles-Cloud-Wörterbuch `mwc` und S-Miles `warn_code.json`, de/en u.a.).
- Ziel: zur Variable „Warnungen“ (`wnum`) Code und Text der aktiven Warnungen liefern.
- Weitere Aktionen dort: Warnungen löschen (Action 42 `CLEAN_WARN`), Erdschluss löschen (Action 10), Sperren/Entsperren (Action 12/13), Wirk-/Blindleistungs-Limit (Action 47/48).
- BLE-only Geräte (2WB) lehnen Action 50 ab (Fehler 1), nicht relevant für WLAN-DTU.

### Kleinere Punkte

- GetConfig (0xA309): enthält WLAN-SSID/-Passwort, AP-Passwort, Sperr-Passwort, Server. Signalstärke kommt bereits aus AppInfo; nur umsetzen, wenn weitere Werte (IP, DHCP, Zero-Export) gebraucht werden, dann Rohdaten im Debug maskieren.
- Start/Stop des Wechselrichters: hoymiles-wifi nutzt 0x2305 mit `mi_to_sn` und `dev_kind`, das Modul 0xA305 ohne Seriennummer. Live prüfen, ggf. angleichen.
- Leistungslimit: hoymiles-wifi sendet `A:<limit>,B:0,C:0`; AppInfo `app_features` (key 8) meldet `1,A:1000,B:1000,C:1000`. Bei Geräten mit mehreren Wechselrichtern prüfen.
- Heartbeat (0xA302) als Alternative zum Ping-Watchdog; Leistungshistorie des Tages (0xA315, seitenweise); Performance-Data-Mode (Action 33).

### Größerer Umbau (erst wenn jemand die Hardware hat und testen kann)

- Stromzähler (`MeterMO` in RealData, `APPMeterInfoMO` in AppInfo): Leistung, Energie Bezug/Einspeisung, Spannung/Strom/Leistungsfaktor je Phase.
- 3-Phasen-Wechselrichter (`TGSMO`): Spannungen je Phase und verkettet, Ströme je Phase, Wirk-/Blindleistung.
- Batteriespeicher: Registrierung (0xC302), Daten (0xC303), Betriebsmodus setzen (0xC308).
- RSD (`RSDMO`), Repeater (`RpMO`/`APPRpInfoMO`), Gateways (0xDB01/0xDB06).
- Jeweils neue Gerätemodule, Weiterleitung im IO und Erweiterung des Konfigurators.

### Bewusst nicht umgesetzt

- WLAN-Zugangsdaten setzen (0xA310) und DTU-Firmware-Update (Action 2): Risiko, die DTU unerreichbar zu machen.

## Offen

- Live-Test (WR muss online sein): neue Variablen der DTU- und Inverter-Instanz prüfen; `HMSWIFI_RebootDTU` und `HMSWIFI_RebootInverter` nur nach Freigabe durch den Nutzer.
- Rückmeldung des Users aus dem Forum mit verschlüsselter DTU (Debug der IO-Instanz) abwarten.
- Beobachtung: Timer-Abfrage (0xA311) und SetPowerLimit (0xA305) liefen parallel über zwei TCP-Verbindungen, die DTU hat beide korrekt beantwortet. `SendCommand` serialisiert Anfragen nicht; bei Problemen hier ansetzen.
- Ungetestet mit echter verschlüsselter DTU: Werden die Kommandos 0xA305 (Leistungsbegrenzung, Start/Stop) verschlüsselt akzeptiert?
