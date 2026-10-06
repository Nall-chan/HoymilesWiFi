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

## Rückmeldung Forum (Beitrag 80-84) und Fixes (Version 1.24)

- power1625 (DTU V01.01.01, WR V01.03.09, verschlüsselt): Version 1.23 funktioniert.
- Leistungslimit 0 %: DTU lässt `pLim` (Feld 13) im InverterState zeitweise weg (morgens beim Anlaufen, abends; live bei uns 17:33-17:50 beobachtet, App zeigte 100 %). Proto3 überträgt 0 nicht, „fehlt“ und „0 %“ sind auf der Leitung nicht unterscheidbar. Die alte Runtime war gepatcht (`existField` immer true) und hatte dasselbe Verhalten. Fix: Inverter ignoriert `pLim == 0` (wie ioBroker `powerLimitEcho`). Gültiger Bereich ist 2-100 % (ioBroker `POWER_LIMIT_MIN`), `SetPowerLimit` prüft das jetzt; ein echtes 0 % gibt es nicht.
- Leistungsfaktor: Rohwert ist cos φ x 1000 (live 949 bei 328 W / 108 var = 0,95; ioBroker `SCALE_POWER_FACTOR = 1000`). Jetzt Faktor 0.001, 3 Nachkommastellen, -1..1, ohne Einheit.
- Darstellung: `MaintainVariable` prüft die Parameter nur bei geänderter Darstellung. `MULTILINE` gibt es nur für String, für Boolean nur `OPTIONS` + allgemeine Parameter (`IPS_GetPresentation` der Wertanzeige). Beim Leistungsfaktor und Link bereinigt.
- Button „Wechselrichter neu starten“ 300px breit.

## Warnungen / Alarme des Wechselrichters (Version 1.24, live nur teilweise geprüft)

Quelle: ioBroker-Adapter https://github.com/Eistee82/ioBroker.hoymiles (MIT, Copyright Eistee82). Klartexte in `libs/HoymilesWarnCodes.php` (223 Codes, en/de, generiert aus `alarmCodes.ts` + `alarmCodesData.ts`, Cloud-Wörterbuch hat Vorrang), Hinweis im Dateikopf.

- Ablauf im IO (`CheckWarnings`): ändert sich `wnum` eines WR oder sind 300 s vergangen, wird Action 50 (`ALARM_LIST`, 0xA305) gesendet. Beim nächsten RealData (frühestens nach 5 s) wird die Liste per 0xA304 (`WarnResDTO`: ymd_hms 1, package_now 2, offset 4, time 5) abgeholt; Antwort 0xA204 `WarnReqDTO` (dtu_sn 1, time 2, package_nub 3, package_now 4, warn_device 5, warns 6: pv_sn 1, code 2, num 3, s_time 4, e_time 5, w_data1 6, w_data2 7). Folgeseiten bis `package_nub` (max. 20).
- ioBroker hält die Verbindung offen und bekommt 0xA204 nach Action 50 als Push. Live geprüft (06.10.2026, DTU V00.01.11 im HMS-2T): die DTU schließt die Verbindung nach **jeder** Antwort (auch nach Heartbeat 0xA302), ein Push ist damit nicht möglich. Der direkte Pull per 0xA304 (wie S-Miles) liefert nur `dtu_sn`, `time`, `warn_device` 15 und keine Einträge, auch direkt nachdem `wnum` von 6 auf 7 gestiegen war und nach Action 50 bzw. Action 46 (`READ_MI_HU_WARN`). Die S-Miles App zeigte zur selben Zeit ebenfalls keine Alarme (weder DTU noch Inverter), die leere Liste ist also vermutlich korrekt; `wnum` zählt auch Ereignisse, die die App nicht als Alarm zeigt (Sprung 6 -> 7 um 18:25 beim Abregeln, evtl. Code 38). Offen: mit einem echten Alarm in der App prüfen, ob er lokal per 0xA304 kommt. Die Implementierung bleibt drin; das Debug der IO (`WarnData`, `Warning`) zeigt bei anderen DTUs (z.B. power1625, V01.01.01), ob dort Einträge kommen. Heartbeat-Antwort 0xA202: offset 1, time 2, csq 3 (-27), dtu_sn 4, Feld 6 unbekannt.
- Action 50 und 0xA304 laufen mit `SendCommand(..., $Quiet = true)`: Fehler nur im Debug (`ERROR (quiet)`), kein `trigger_error`, kein Statuswechsel. Grund: ungetestet, ob verschlüsselte DTUs diese Kommandos beantworten.
- Verteilung an die Inverter über `pv_sn` -> `InverterSerials`. Inverter: Variablen `wActive` (aktive = `e_time` 0), `wText` (Texte der aktiven), `wLast` (neueste nach `s_time`), Liste im Buffer, `HMSWIFI_GetWarnings()`.
- Unklar: Format von `s_time`/`e_time` (ioBroker nimmt Unix-Sekunden an), Bedeutung `warn_device`. Mit echten Einträgen prüfen.
- `wnum` ist kein Code, sondern ein Zähler (laut ioBroker die Anzahl der Warnungen im WR). User sieht 38, wir 6.
- Offen im Backlog: Warnungen löschen (Action 42 `CLEAN_WARN`), Erdschluss löschen (Action 10), Sperren/Entsperren (Action 12/13), Wirk-/Blindleistungs-Limit (Action 47/48). BLE-only Geräte (2WB) lehnen Action 50 ab, nicht relevant.

## Backlog

### Kleinere Punkte

- Datenalter erkennen über `miSignal` (InverterState Feld 20): Bei eingefrorenen Daten (06.10.2026 ab 18:40:34, WR abgeschaltet, DTU antwortet weiter mit dem letzten Stand) blieb das untere Byte konstant (0x8E), das obere stieg je Abfrage um ca. 0x0F (0x82 -> 0x91 -> 0xA0 -> 0xAE -> 0xBD bei ~11 s Abstand). Tagsüber z.B. 0x440081, 0x8000B4. Vermutung: Alter der WR-Daten bzw. Zähler seit letztem Kontakt. Morgen beim Anlaufen gegen echte Werte prüfen; falls bestätigt, eingefrorene Daten erkennen (z.B. über `Link`).
- Testgeräte: HMS-xxxW-2T mit in den WR integrierter WLAN-DTU. DTU und WR gehen gemeinsam offline, wenn der WR abschaltet; vorher liefert die DTU noch eine Weile den letzten Datenstand.

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

- Live-Test (WR muss online sein): Warnliste mit echten Einträgen prüfen (Zeitformat, Zuordnung); `HMSWIFI_RebootDTU` und `HMSWIFI_RebootInverter` nur nach Freigabe durch den Nutzer.
- Rückmeldung des Users aus dem Forum mit verschlüsselter DTU (Debug der IO-Instanz) abwarten.
- Beobachtung: Timer-Abfrage (0xA311) und SetPowerLimit (0xA305) liefen parallel über zwei TCP-Verbindungen, die DTU hat beide korrekt beantwortet. `SendCommand` serialisiert Anfragen nicht; bei Problemen hier ansetzen.
- Ungetestet mit echter verschlüsselter DTU: Werden die Kommandos 0xA305 (Leistungsbegrenzung, Start/Stop) verschlüsselt akzeptiert?
