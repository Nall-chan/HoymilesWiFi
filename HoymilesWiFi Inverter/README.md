[![SDK](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Module Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FHoymilesWiFi%2Frefs%2Fheads%2Fmaster%2Flibrary.json&query=%24.version&label=Modul%20Version&color=blue)](https://community.symcon.de/t/modul-hoymiles-wifi-series-beta/135536/)
[![Symcon Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FHoymilesWiFi%2Frefs%2Fheads%2Fmaster%2Flibrary.json&query=%24.compatibility.version&suffix=%3E&label=Symcon%20Version&color=green)](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v80-v81-q3-2025/)  
[![License](https://img.shields.io/badge/License-Custom--NC--SA-green.svg)](#10-lizenz)
[![Check Style](https://github.com/Nall-chan/HoymilesWiFi/workflows/Check%20Style/badge.svg)](https://github.com/Nall-chan/HoymilesWiFi/actions)
[![Run Tests](https://github.com/Nall-chan/HoymilesWiFi/workflows/Run%20Tests/badge.svg)](https://github.com/Nall-chan/HoymilesWiFi/actions)  
[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](#9-spenden)
[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](#9-spenden)  

# Hoymiles WiFi Inverter <!-- omit in toc -->

Anzeigen und Steuern der Werte des Inverters

## Inhaltsverzeichnis <!-- omit in toc -->

- [1. Funktionsumfang](#1-funktionsumfang)
- [2. Voraussetzungen](#2-voraussetzungen)
- [3. Software-Installation](#3-software-installation)
- [4. Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
- [5. Statusvariablen](#5-statusvariablen)
  - [Statusvariablen](#statusvariablen)
- [6. PHP-Befehlsreferenz](#6-php-befehlsreferenz)
- [8. Changelog](#8-changelog)
- [9. Spenden](#9-spenden)
- [10. Lizenz](#10-lizenz)

## 1. Funktionsumfang

- Anzeigen der Werte des Inverters
- Setzen des Leistungslimit

## 2. Voraussetzungen

- IP-Symcon ab Version 8.1
- Hoymiles Wechselrichter mit WiFi (integrierte DTU)
  
## 3. Software-Installation

Dieses Modul ist Bestandteil der [Hoymiles WiFi-Library](../README.md#3-software-installation).  

## 4. Einrichten der Instanzen in IP-Symcon

Unter 'Instanz hinzufügen' kann das 'Hoymiles WiFi Inverter'-Modul mithilfe des Schnellfilters gefunden werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

Es wird empfohlen diese Instanz über die dazugehörige Instanz des [Configurator-Moduls](../HoymilesWiFi%20Configurator/README.md) anzulegen.  

![Instanzen](../imgs/inst.png)  

**Konfigurationsseite**:  

| Name   | Typ     | Standardwert | Beschreibung          |
| ------ | ------- | :----------: | --------------------- |
| Number | integer |      1       | Adresse des Inverters |

![Config](imgs/config.png)  

## 5. Statusvariablen

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

### Statusvariablen

| Name             | Typ     | Beschreibung                              |
| ---------------- | ------- | ----------------------------------------- |
| Spannung         | float   | Spannung Ausgangsseite                    |
| Frequenz         | float   | Frequenz Ausgangsseite                    |
| Leistung         | float   | Abgegeben Leistung                        |
| Strom            | float   | Strom Ausgangsseite                       |
| Leistungsfaktor  | float   | Leistungsfaktor cos φ (z.B. `0,950`)      |
| Temperatur       | float   | Temperatur des Inverters                  |
| Link             | bool    | Inverter mit DTU verbunden                |
| Leistungslimit   | integer | Einstellbares Limit des Inverters         |
| Blindleistung    | float   | Blindleistung Ausgangsseite (var)         |
| Aktive Warnungen | integer | Anzahl der aktuell aktiven Warnungen      |
| Aktuelle Warnung | string  | Texte der aktiven Warnungen               |
| Letzte Warnung   | string  | Text und Code der letzten Warnung         |
| Software-Version | string  | Firmware des Inverters (z.B. `V01.00.08`) |
| Hardware-Version | string  | Hardware des Inverters (z.B. `H00.04.00`) |

Software- und Hardware-Version werden nach dem Start der IO-Instanz und danach alle 5 Minuten abgefragt.  
Die DTU aktualisiert ihre Warnliste bei neuen Warn-Ereignissen, sonst höchstens alle 30 Minuten. Eine in der App bereits beendete Warnung kann daher hier bis zu 30 Minuten länger als aktiv angezeigt werden. Die Liste wird erst ab DTU-Firmware V01.01.01 geliefert (siehe [Firmware der DTU](../README.md#firmware-der-dtu)).  
Das Leistungslimit wird von der DTU nicht bei jedem Abruf geliefert, dann bleibt der letzte bekannte Wert erhalten.  

## 6. PHP-Befehlsreferenz

```php
bool HMSWIFI_SetPowerLimit(integer $InstanzID, int $Limit);
```

Setzen des Leistungslimit des Inverters.  
Der neue Wert in `$Limit` ist in Prozent anzugeben (2 bis 100).  
> [!CAUTION]
> Bitte auf die Nutzung der Leistungsbegrenzung bei Nulleinspeisung verzichten, da es durch übermäßige Schreibvorgänge im EEPROM zu einer Beschädigung des Wechselrichters kommen kann.  

---

```php
bool HMSWIFI_SetInverterState(integer $InstanzID, bool $State);
```

Ein (`true`) oder ausschalten (`false`) des Inverters über den Parameter `State`.  

---

```php
bool HMSWIFI_RebootInverter(integer $InstanzID);
```

Startet den Inverter neu.  
Liefert `true`, wenn der Befehl gesendet wurde. Die DTU bestätigt den Befehl nicht immer, das IO geht dann kurz in einen Fehlerzustand, bis wieder Daten kommen. Die IO-Instanz muss nach ihrem Start mindestens einmal erfolgreich Daten abgerufen haben, damit die Seriennummer des Inverters bekannt ist.  
Nach dem Neustart beginnt der Tagesertrag wieder bei 0.  
In der Instanz-Konfiguration steht dafür die Schaltfläche `Wechselrichter neu starten` zur Verfügung.  

```php
HMSWIFI_RebootInverter(12345);
```

---

```php
array HMSWIFI_GetWarnings(integer $InstanzID);
```

Liefert die zuletzt von der DTU gelesene Warnliste des Inverters.  
Jeder Eintrag enthält `Code`, `Text`, `Number`, `StartTime`, `EndTime` (0 = noch aktiv), `Active`, `Data1` und `Data2`.  
Die Klartexte der Warncodes stammen aus dem Projekt [ioBroker.hoymiles](https://github.com/Eistee82/ioBroker.hoymiles) (MIT-Lizenz).  

```php
print_r(HMSWIFI_GetWarnings(12345));
```

## 8. Changelog

siehe Changelog der [Hoymiles WiFi-Library](../README.md#2-changelog).  

## 9. Spenden  
  
Die Library ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](https://paypal.me/Nall4chan)  

[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](https://www.amazon.de/hz/wishlist/ls/YU4AI9AQT9F?ref_=wl_share)

## 10. Lizenz

IPS-Modul:  
[Custom NC-SA](../LICENSE)
