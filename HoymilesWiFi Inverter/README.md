[![SDK](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Modul%20version-1.21-blue.svg)](https://community.symcon.de/t/modul-hoymiles-wifi-series-beta/135536/)
[![Version](https://img.shields.io/badge/Symcon%20Version-8.1%20%3E-green.svg)](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v80-v81-q3-2025/)  
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
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

| Name            | Typ     | Beschreibung                      |
| --------------- | ------- | --------------------------------- |
| Spannung        | float   | Spannung Ausgangsseite            |
| Frequenz        | float   | Frequenz Ausgangsseite            |
| Leistung        | float   | Abgegeben Leistung                |
| Strom           | float   | Strom Ausgangsseite               |
| Leistungsfaktor | float   | Leistungsfaktor                   |
| Temperatur      | float   | Temperatur des Inverters          |
| Link            | bool    | Inverter mit DTU verbunden        |
| Leistungslimit  | integer | Einstellbares Limit des Inverters |

## 6. PHP-Befehlsreferenz

```php
bool HMSWIFI_SetPowerLimit(integer $InstanzID, int $Limit);
```

Setzen des Leistungslimit des Inverters.  
Der neue Wert in `$Limit` ist in Prozent anzugeben.  
> [!CAUTION]
> Bitte auf die Nutzung der Leistungsbegrenzung bei Nulleinspeisung verzichten, da es durch übermäßige Schreibvorgänge im EEPROM zu einer Beschädigung des Wechselrichters kommen kann.  

---

```php
bool HMSWIFI_SetInverterState(integer $InstanzID, bool $State);
```

Ein (`true`) oder ausschalten (`false`) des Inverters über den Parameter `State`.  

## 8. Changelog

siehe Changelog der [Hoymiles WiFi-Library](../README.md#2-changelog).  

## 9. Spenden  
  
Die Library ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](https://paypal.me/Nall4chan)  

[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](https://www.amazon.de/hz/wishlist/ls/YU4AI9AQT9F?ref_=wl_share)

## 10. Lizenz

[CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)  
