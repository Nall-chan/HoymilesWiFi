[![SDK](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Module Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FHoymilesWiFi%2Frefs%2Fheads%2Fmaster%2Flibrary.json&query=%24.version&label=Modul%20Version&color=blue)](https://community.symcon.de/t/modul-hoymiles-wifi-series-beta/135536/)
[![Symcon Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FHoymilesWiFi%2Frefs%2Fheads%2Fmaster%2Flibrary.json&query=%24.compatibility.version&suffix=%3E&label=Symcon%20Version&color=green)](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v80-v81-q3-2025/)  
[![License](https://img.shields.io/badge/License-Custom--NC--SA-green.svg)](#7-lizenz)
[![Check Style](https://github.com/Nall-chan/HoymilesWiFi/workflows/Check%20Style/badge.svg)](https://github.com/Nall-chan/HoymilesWiFi/actions)
[![Run Tests](https://github.com/Nall-chan/HoymilesWiFi/workflows/Run%20Tests/badge.svg)](https://github.com/Nall-chan/HoymilesWiFi/actions)  
[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](#6-spenden)
[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](#6-spenden)  

# Hoymiles WiFi Wechselrichter <!-- omit in toc -->  

Integration der Hoymiles Wechselrichter mit integrierten WiFi  

- HMS-600W/700W/800W/900W/1000W-2T (Wi-Fi integrated)
- HMS-300W/350W/400W/450W/500W-1T (Wi-Fi integrated)

## Inhaltsverzeichnis <!-- omit in toc -->

- [1. Funktionsumfang](#1-funktionsumfang)
- [2. Voraussetzungen](#2-voraussetzungen)
  - [Firmware der DTU](#firmware-der-dtu)
- [3. Software-Installation](#3-software-installation)
- [4. Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
- [5. Anhang](#5-anhang)
  - [1. GUID der Module](#1-guid-der-module)
  - [2. Changelog](#2-changelog)
- [6. Spenden](#6-spenden)
- [7. Lizenz](#7-lizenz)

## 1. Funktionsumfang

Folgende Module beinhaltet das Hoymiles WiFi Smart Rollos Repository:

- **Hoymiles WiFi IO** ([Dokumentation](HoymilesWiFi%20IO/README.md))  
  IO Instanz zur Kommunikation mit der integrierten DTU.  

- **Hoymiles WiFi Configurator** ([Dokumentation](HoymilesWiFi%20Configurator/README.md))  
  Konfigurator Instanz zum auslesen der bekannten Geräte und einfachen anlegen von Instanzen in Symcon.  

- **Hoymiles WiFi DTU** ([Dokumentation](HoymilesWiFi%20DTU/README.md))  
  Geräte Instanz für die integrierte DTU.  

- **Hoymiles WiFi Inverter** ([Dokumentation](HoymilesWiFi%20Inverter/README.md))  
  Geräte Instanz für den integrierten Inverter.  

- **Hoymiles WiFi SolarPort** ([Dokumentation](HoymilesWiFi%20SolarPort/README.md))  
  Geräte Instanz für jeweils einen Anschluss von Solarmodulen.  

## 2. Voraussetzungen  

- Symcon ab Version 8.1  
- Hoymiles Wechselrichter mit WiFi (integrierte DTU)
- DTU-Firmware ab V01.01.01 empfohlen (siehe [Firmware der DTU](#firmware-der-dtu))

### Firmware der DTU

Getestet mit den DTU-Firmware-Versionen V00.01.11 und V01.01.01. Die installierte Version zeigt die Statusvariable `Software-Version` der [DTU-Instanz](HoymilesWiFi%20DTU/README.md).  

- Ab V01.01.01 kommuniziert die DTU verschlüsselt. Diese Firmware wird erst ab Version 1.23 dieser Library unterstützt, ältere Versionen melden `Data has wrong length.`.  
- Die Warnliste des Wechselrichters (Statusvariablen `Aktive Warnungen`, `Aktuelle Warnung` und `Letzte Warnung`) wird erst ab V01.01.01 geliefert, mit V00.01.11 bleibt sie leer.  

Die Firmware wird über die App **S-Miles Installer** aktualisiert:  

1. App S-Miles Installer öffnen.  
2. Anlage öffnen, sofern die App nicht direkt zur Anlage springt.  
3. Unten rechts über das letzte Icon die weiteren Funktionen öffnen und `Geräteliste` wählen.  
4. Kategorie `DTU` auswählen und die Kachel der DTU antippen.  
5. In der Liste der Eigenschaften unter `Gerätewartung` die `Firmware-Aktualisierung` wählen.  
6. Mit `Aktualisieren` abschließen.  

Während der Aktualisierung ist die DTU nicht erreichbar, die IO-Instanz meldet in dieser Zeit einen Fehler und verbindet sich danach selbstständig neu.  

> [!IMPORTANT]
> Vor einer Firmware-Aktualisierung (DTU oder Wechselrichter) die IO-Instanz schließen (Haken `Öffnen` entfernen) und danach wieder öffnen. Solange Symcon die DTU abfragt, kann die Aktualisierung bei 0 % stehen bleiben. Der Schlafmodus (`HMSWIFI_SetInactive`) reicht nicht, wenn ein Watchdog eingestellt ist, da dieser die Instanz wieder aktiviert.

> [!WARNING]
> Eine Rückkehr zu einer älteren Firmware ist über die App nicht möglich.

  
## 3. Software-Installation

Über den 'Module-Store' in IPS das Modul 'Hoymiles WiFi' hinzufügen.  
**Bei kommerzieller Nutzung (z.B. als Errichter oder Integrator) wenden Sie sich bitte an den Autor.**  
![Module-Store](imgs/install.png)  

## 4. Einrichten der Instanzen in IP-Symcon

Nach der installation des Modules, muss eine Instanz des [Configurator-Moduls](HoymilesWiFi%20Configurator/README.md) angelegt werden.  
Dadurch wird automatisch der benötigte [IO](HoymilesWiFi%20IO/README.md) erstellt.  

## 5. Anhang

### 1. GUID der Module

|           Modul            |     Typ      |                  GUID                  |
| :------------------------: | :----------: | :------------------------------------: |
|      Hoymiles WiFi IO      |      IO      | {5972AA13-358F-A088-CEBD-207C289C9395} |
| Hoymiles WiFi Konfigurator | Configurator | {4062635D-2680-4A39-C364-05EB8B196DA9} |
|     Hoymiles WiFi DTU      |    Device    | {BB414362-B36F-81C5-2701-E968A29F58AD} |
|   Hoymiles WiFi Inverter   |    Device    | {52D8E128-5588-B496-4BE5-14E8EFD737B8} |
|  Hoymiles WiFi SolarPort   |    Device    | {65B18475-D1B7-825C-5958-5300C1100845} |

### 2. Changelog

**Version 1.26:**  

- Neu: Laufzeit-Leistungslimit in Watt für die HMS-W-2T-Familie (`HMSWIFI_SetPowerLimitWatt`, Statusvariable `Leistungslimit (Watt)` über die Instanz-Konfiguration). Wird bei einem Neustart des Wechselrichters zurückgesetzt, beschreibt keinen Flash-Speicher und eignet sich daher für häufige Änderungen, z.B. eine Nulleinspeisung  
- Neu: IO zeigt Geräteinformationen (Seriennummern, Versionen, Modell und Nennleistung der Wechselrichter) in der Konfiguration an, abrufbar per `HMSWIFI_GetDeviceInfo`  
- Erkennt die Instanz die Nennleistung des Inverters, wird sie als Maximum für `Leistungslimit (Watt)` genutzt  
- Ein in der App geändertes Leistungslimit wird erkannt und in `Leistungslimit` (%) übernommen  
- Dokumentation: Warnung, dass jedes Setzen des Leistungslimit in % den Flash-Speicher von DTU und Wechselrichter beschreibt  
- Fix: Kamen zwei Anfragen gleichzeitig (z.B. zyklische Abfrage und `HMSWIFI_SetInverterState`), beantwortete die DTU nur eine davon; die andere meldete eine Zeitüberschreitung, obwohl der Befehl teilweise trotzdem ausgeführt wurde. Anfragen an die DTU werden jetzt nacheinander gesendet  

**Version 1.25:**  

- Die Statusvariable Inverter `Warnungen gesamt` (ein interner Ereigniszähler des Wechselrichters) entfällt, die Anzahl der aktuellen Warnungen liefert `Aktive Warnungen`. Die bestehende Variable wird nicht mehr aktualisiert und kann gelöscht werden  
- Fix: Nach der Abfrage der Warnungen lieferte die DTU teilweise keine aktuellen Werte mehr (Daten blieben stehen). Die DTU wird jetzt nur noch bei neuen Warn-Ereignissen und sonst höchstens alle 30 Minuten zur Aktualisierung der Warnliste aufgefordert, dazwischen wird die Liste alle 5 Minuten nur gelesen  
- Dokumentation: Hinweise zur DTU-Firmware und Anleitung zur Aktualisierung  
- Warncodes mit zusätzlichen Status-Bits (z.B. 8408 oder 28888) werden mit Klartext angezeigt  
- Fix: `HMSWIFI_RebootDTU` und `HMSWIFI_RebootInverter` meldeten einen Fehler, obwohl der Neustart ausgeführt wurde (die DTU bestätigt den Befehl nicht)  
- Fix: Fehler im IO führten bei `HMSWIFI_SetPowerLimit`, `HMSWIFI_SetInverterState`, `HMSWIFI_RebootDTU` und `HMSWIFI_RebootInverter` zu einem PHP-Fehler statt zur Rückgabe `false`  
- Fix: Statusvariable `Link` wechselte kurz auf Alarm, wenn die DTU den Link-Status in einer Antwort nicht mitgeliefert hat. Alarm erst nach 3 Abfragen in Folge ohne Link-Status  

**Version 1.24:**  

- Neue Statusvariablen Inverter: Aktive Warnungen, Aktuelle Warnung und Letzte Warnung  
- Neue Instanz-Funktion `HMSWIFI_GetWarnings` liefert die Warnliste des Inverters  
- Fehler bei der Abfrage der Warnliste werden nur im Debug ausgegeben und ändern nicht den Status des IO  
- Fix: Leistungslimit sprang auf 0 %, wenn die DTU kein Limit mitgeliefert hat  
- Leistungslimit kann nur noch im gültigen Bereich von 2 bis 100 % gesetzt werden  
- Statusvariable Inverter `Warnungen` heißt jetzt `Warnungen gesamt` (bestehende Variable bei Bedarf selbst umbenennen)  
- Fix: Leistungsfaktor wird als cos φ ohne Einheit dargestellt (vorher fälschlich in %)  
- Fix: Schaltfläche `Wechselrichter neu starten` war zu schmal  
- Fix: Ungültige Parameter der Darstellung von Leistungsfaktor und Link entfernt  

**Version 1.23:**  

- Unterstützung für DTUs mit verschlüsselter Kommunikation (neuere DTU-Firmware, Fehler `Data has wrong length.`)  
- Antworten der DTU werden vollständig anhand der Länge im Header gelesen  
- Erweiterte Debug-Ausgaben im IO  
- Protobuf-Bibliothek auf Version 5.36.2 aktualisiert (behebt Deprecation-Meldungen mit neueren PHP-Versionen)  
- Fehlermeldungen des IO übersetzt  
- Neue Statusvariablen DTU: WLAN Signalstärke, Software-, Hardware- und WLAN-Version  
- Neue Statusvariablen Inverter: Blindleistung, Warnungen, Software- und Hardware-Version  
- Neue Instanz-Funktionen `HMSWIFI_RebootDTU` und `HMSWIFI_RebootInverter` (inkl. Schaltflächen in der Instanz-Konfiguration)  
- Schlüsselmaterial verschlüsselter DTUs wird im Debug maskiert  

**Version 1.22:**  

- Fix: Support Links  
- Update Submodule  
  
**Version 1.21:**  

- Unter bestimmten Umständen konnte die Instanz-Konfiguration des IO nicht mehr geöffnet werden  
- Startverhalten des IO beim Symcon Neustart angepasst  
- Umkonfigurieren der IO Instanz berücksichtigt nicht mehr den gespeicherten letzten Zustand (aktiv/inaktiv)  
- Neue Power-On Überwachung mit zusätzlich Netzwerk-Ping oder eigener Bedingung  

**Version 1.2:**  

- Version für Symcon 8.1 und neuer  
- Durchgängige Nutzung von Darstellungen anstatt von Profilen  
- Neue Instanz-Funktion `HMSWIFI_SetInverterState` für die Inverter-Instanz  

**Version 1.0:**  

- Erstes Release nach Beta  

## 6. Spenden  
  
  Die Library ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](https://paypal.me/Nall4chan)  

[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](https://www.amazon.de/hz/wishlist/ls/YU4AI9AQT9F?ref_=wl_share)

## 7. Lizenz

**IPS-Modul:**  
[Custom NC-SA](LICENSE)

**Klartexte der Warn-/Alarmcodes der Hoymiles Wechselrichter**
 
- **Quelle:** ioBroker.hoymiles (https://github.com/Eistee82/ioBroker.hoymiles) (src/lib/alarmCodes.ts und src/lib/alarmCodesData.ts)
- **MIT License, Copyright (c) Eistee82**
- **Die Texte stammen dort aus dem Hoymiles-Cloud-Wörterbuch (mwc) und der warn_code.json der S-Miles App.**
