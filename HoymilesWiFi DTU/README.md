[![SDK](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Module Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FHoymilesWiFi%2Frefs%2Fheads%2Fmaster%2Flibrary.json&query=%24.version&label=Modul%20Version&color=blue)](https://community.symcon.de/t/modul-hoymiles-wifi-series-beta/135536/)
[![Symcon Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FHoymilesWiFi%2Frefs%2Fheads%2Fmaster%2Flibrary.json&query=%24.compatibility.version&suffix=%3E&label=Symcon%20Version&color=green)](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v80-v81-q3-2025/)  
[![License](https://img.shields.io/badge/License-Custom--NC--SA-green.svg)](#9-lizenz)
[![Check Style](https://github.com/Nall-chan/HoymilesWiFi/workflows/Check%20Style/badge.svg)](https://github.com/Nall-chan/HoymilesWiFi/actions)
[![Run Tests](https://github.com/Nall-chan/HoymilesWiFi/workflows/Run%20Tests/badge.svg)](https://github.com/Nall-chan/HoymilesWiFi/actions)  
[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](#8-spenden)
[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](#8-spenden)  

# Hoymiles WiFi DTU <!-- omit in toc -->

Darstellen der ausgelesenen Werte aus der DTU

## Inhaltsverzeichnis <!-- omit in toc -->

- [1. Funktionsumfang](#1-funktionsumfang)
- [2. Voraussetzungen](#2-voraussetzungen)
- [3. Software-Installation](#3-software-installation)
- [4. Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
- [5. Statusvariablen](#5-statusvariablen)
  - [Statusvariablen](#statusvariablen)
- [6. PHP-Befehlsreferenz](#6-php-befehlsreferenz)
- [7. Changelog](#7-changelog)
- [8. Spenden](#8-spenden)
- [9. Lizenz](#9-lizenz)

## 1. Funktionsumfang

- Anzeigen der Werte der DTU

## 2. Voraussetzungen

- Symcon ab Version 8.1  
- Hoymiles Wechselrichter mit WiFi (integrierte DTU)

## 3. Software-Installation

Dieses Modul ist Bestandteil der [Hoymiles WiFi-Library](../README.md#3-software-installation).  

## 4. Einrichten der Instanzen in IP-Symcon

Unter 'Instanz hinzufügen' kann das 'Hoymiles WiFi DTU'-Modul mithilfe des Schnellfilters gefunden werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

Es wird empfohlen diese Instanz über die dazugehörige Instanz des [Configurator-Moduls](../HoymilesWiFi%20Configurator/README.md) anzulegen.  

![Instanzen](../imgs/inst.png)  

## 5. Statusvariablen

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

### Statusvariablen

| Name              | Typ     | Beschreibung                                 |
| ----------------- | ------- | -------------------------------------------- |
| Uhrzeit           | Integer | Uhrzeit der DTU                              |
| Leistung          | Float   | Aktuelle Leistung                            |
| Ertrag täglich    | Float   | Tagesertrag                                  |
| WLAN Signalstärke | Integer | Signalstärke des WLAN in Prozent             |
| Software-Version  | String  | Firmware der DTU (z.B. `V00.01.11`)          |
| Hardware-Version  | String  | Hardware der DTU (z.B. `H00.01.00`)          |
| WLAN-Version      | String  | Version des WLAN-Moduls (z.B. `2.1.21.4_hm`) |

Signalstärke und Versionen werden nach dem Start der IO-Instanz und danach alle 5 Minuten abgefragt.  

## 6. PHP-Befehlsreferenz

```php
bool HMSWIFI_RebootDTU(integer $InstanzID);
```

Startet die DTU neu.  
Liefert `true`, wenn der Befehl gesendet wurde. Die DTU bestätigt den Befehl nicht immer, sondern startet sofort neu. Während des Neustarts ist die DTU kurzzeitig nicht erreichbar und das IO geht in einen Fehlerzustand, bis wieder Daten kommen.  
In der Instanz-Konfiguration steht dafür die Schaltfläche `DTU neu starten` zur Verfügung.  

```php
HMSWIFI_RebootDTU(12345);
```

## 7. Changelog

siehe Changelog der [Hoymiles WiFi-Library](../README.md#2-changelog).  

## 8. Spenden  
  
Die Library ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](https://paypal.me/Nall4chan)  

[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](https://www.amazon.de/hz/wishlist/ls/YU4AI9AQT9F?ref_=wl_share)

## 9. Lizenz

IPS-Modul:  
[Custom NC-SA](../LICENSE)
