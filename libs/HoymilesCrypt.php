<?php

declare(strict_types=1);

namespace Hoymiles\DTU{
    /**
     * Verschlüsselung der DTU-Kommunikation (neuere DTU-Firmware).
     *
     * Ablauf wie in https://github.com/suaveolent/hoymiles-wifi (crypt_util.py):
     * - Die DTU meldet in APPInfoData (dtu_info.dfs, Bit 25), ob sie verschlüsselt.
     * - dtu_info.enc_rand (16 Byte) ist die Basis für Schlüssel und Nonce.
     * - Payload wird mit AES-128-GCM verschlüsselt, der 16 Byte Auth-Tag hängt am Ende.
     * - Das Längenfeld im Header und die CRC16 enthalten den Auth-Tag nicht.
     */
    class Encryption
    {
        public const Cipher = 'aes-128-gcm';
        public const TagLength = 16;
        public const EncRandLength = 16;
        public const IsEncryptedBitIndex = 25;

        /**
         * Kommandos welche auch bei verschlüsselten DTUs immer im Klartext übertragen werden.
         */
        public const NotEncryptedCommands = [
            0xA301, // APPInfoDataResDTO (Request)
            0xA201, // APPInfoDataReqDTO (Response)
            0xA302  // HBResDTO
        ];

        /**
         * Antworten mit Schlüsselmaterial (enc_rand), deren Rohdaten nicht im Debug landen dürfen.
         */
        public const SensitiveResponses = [
            0xA201  // APPInfoDataReqDTO
        ];

        /**
         * Prüft ob PHP die benötigte Cipher-Methode bereitstellt.
         *
         * @return bool
         */
        public static function IsAvailable(): bool
        {
            return function_exists('openssl_encrypt') && in_array(self::Cipher, openssl_get_cipher_methods(), true);
        }

        /**
         * Wertet das dfs-Feld aus APPDtuInfoMO aus.
         *
         * @param int $Dfs
         * @return bool true wenn die DTU verschlüsselt kommuniziert
         */
        public static function IsEncryptedDtu(int $Dfs): bool
        {
            return (($Dfs >> self::IsEncryptedBitIndex) & 1) === 1;
        }

        /**
         * Prüft ob ein Kommando (Request oder Response) verschlüsselt übertragen wird.
         *
         * @param int $Command
         * @return bool
         */
        public static function IsEncryptedCommand(int $Command): bool
        {
            return !in_array($Command, self::NotEncryptedCommands, true);
        }

        /**
         * Verschlüsselt eine Payload.
         *
         * @param string $EncRand 16 Byte enc_rand der DTU
         * @param int $Command Kommando aus dem Header
         * @param int $Sequenz Sequenz aus dem Header
         * @param string $PlainData Protobuf-Daten
         * @return string|false Geheimtext inkl. 16 Byte Auth-Tag
         */
        public static function Encrypt(string $EncRand, int $Command, int $Sequenz, string $PlainData): string|false
        {
            $Tag = '';
            $CipherData = openssl_encrypt(
                $PlainData,
                self::Cipher,
                self::DeriveKey($EncRand),
                OPENSSL_RAW_DATA,
                self::DeriveNonce($EncRand, $Command, $Sequenz),
                $Tag,
                self::AdditionalData($Command, $Sequenz),
                self::TagLength
            );
            if ($CipherData === false) {
                return false;
            }
            return $CipherData . $Tag;
        }

        /**
         * Entschlüsselt eine Payload.
         *
         * @param string $EncRand 16 Byte enc_rand der DTU
         * @param int $Command Kommando aus dem Header
         * @param int $Sequenz Sequenz aus dem Header
         * @param string $CipherData Geheimtext inkl. 16 Byte Auth-Tag
         * @return string|false Protobuf-Daten oder false bei Fehler (z.B. Auth-Tag ungültig)
         */
        public static function Decrypt(string $EncRand, int $Command, int $Sequenz, string $CipherData): string|false
        {
            if (strlen($CipherData) < self::TagLength) {
                return false;
            }
            return openssl_decrypt(
                substr($CipherData, 0, -self::TagLength),
                self::Cipher,
                self::DeriveKey($EncRand),
                OPENSSL_RAW_DATA,
                self::DeriveNonce($EncRand, $Command, $Sequenz),
                substr($CipherData, -self::TagLength),
                self::AdditionalData($Command, $Sequenz)
            );
        }

        /**
         * AES-128 Schlüssel: dreifacher SHA256 über enc_rand, die ersten 16 Byte.
         *
         * @param string $EncRand
         * @return string
         */
        public static function DeriveKey(string $EncRand): string
        {
            return substr(self::TripleSha256($EncRand), 0, 16);
        }

        /**
         * Nonce: dreifacher SHA256 über Kommando, Sequenz (je uint16 LE) und enc_rand, die letzten 12 Byte.
         *
         * @param string $EncRand
         * @param int $Command
         * @param int $Sequenz
         * @return string
         */
        public static function DeriveNonce(string $EncRand, int $Command, int $Sequenz): string
        {
            return substr(self::TripleSha256(self::AdditionalData($Command, $Sequenz) . $EncRand), -12);
        }

        private static function AdditionalData(int $Command, int $Sequenz): string
        {
            return pack('vv', $Command & 0xFFFF, $Sequenz & 0xFFFF);
        }

        private static function TripleSha256(string $Data): string
        {
            return hash('sha256', hash('sha256', hash('sha256', $Data, true), true), true);
        }
    }

    /**
     * Minimaler Protobuf-Codec, nur für APPInfoData.
     * Damit werden keine generierten Klassen (protoc) benötigt.
     */
    class Protobuf
    {
        public const WireVarint = 0;
        public const WireFixed64 = 1;
        public const WireLengthDelimited = 2;
        public const WireFixed32 = 5;

        /**
         * Kodiert einen Integer als Varint.
         *
         * @param int $Value
         * @return string
         */
        public static function EncodeVarint(int $Value): string
        {
            $Result = '';
            for ($i = 0; $i < 10; $i++) {
                $Byte = $Value & 0x7F;
                // logischer Shift, damit negative Werte terminieren
                $Value = ($Value >> 7) & (PHP_INT_MAX >> 6);
                if ($Value === 0) {
                    return $Result . chr($Byte);
                }
                $Result .= chr($Byte | 0x80);
            }
            return $Result;
        }

        /**
         * Kodiert ein Varint-Feld.
         *
         * @param int $Field
         * @param int $Value
         * @return string
         */
        public static function EncodeVarintField(int $Field, int $Value): string
        {
            return self::EncodeVarint(($Field << 3) | self::WireVarint) . self::EncodeVarint($Value);
        }

        /**
         * Kodiert ein Feld vom Typ string/bytes/message.
         *
         * @param int $Field
         * @param string $Value
         * @return string
         */
        public static function EncodeBytesField(int $Field, string $Value): string
        {
            return self::EncodeVarint(($Field << 3) | self::WireLengthDelimited) . self::EncodeVarint(strlen($Value)) . $Value;
        }

        /**
         * Zerlegt eine Protobuf-Nachricht in ihre Felder.
         *
         * @param string $Data
         * @return array|false [Feldnummer => [Wert, ...]], Varint als int, alles andere als string
         */
        public static function DecodeFields(string $Data): array|false
        {
            $Fields = [];
            $Pos = 0;
            $Length = strlen($Data);
            while ($Pos < $Length) {
                $Key = self::DecodeVarint($Data, $Pos);
                if ($Key === false) {
                    return false;
                }
                $Field = $Key >> 3;
                switch ($Key & 0x07) {
                    case self::WireVarint:
                        $Value = self::DecodeVarint($Data, $Pos);
                        if ($Value === false) {
                            return false;
                        }
                        break;
                    case self::WireFixed64:
                        if ($Pos + 8 > $Length) {
                            return false;
                        }
                        $Value = substr($Data, $Pos, 8);
                        $Pos += 8;
                        break;
                    case self::WireLengthDelimited:
                        $Size = self::DecodeVarint($Data, $Pos);
                        if (($Size === false) || ($Size < 0) || ($Pos + $Size > $Length)) {
                            return false;
                        }
                        $Value = substr($Data, $Pos, $Size);
                        $Pos += $Size;
                        break;
                    case self::WireFixed32:
                        if ($Pos + 4 > $Length) {
                            return false;
                        }
                        $Value = substr($Data, $Pos, 4);
                        $Pos += 4;
                        break;
                    default:
                        return false;
                }
                $Fields[$Field][] = $Value;
            }
            return $Fields;
        }

        /**
         * Erster Varint-Wert eines Feldes aus DecodeFields, sonst 0.
         *
         * @param array $Fields
         * @param int $Field
         * @return int
         */
        public static function IntValue(array $Fields, int $Field): int
        {
            return (isset($Fields[$Field][0]) && is_int($Fields[$Field][0])) ? $Fields[$Field][0] : 0;
        }

        /**
         * Erster String-Wert eines Feldes aus DecodeFields, sonst ''.
         *
         * @param array $Fields
         * @param int $Field
         * @return string
         */
        public static function StringValue(array $Fields, int $Field): string
        {
            return (isset($Fields[$Field][0]) && is_string($Fields[$Field][0])) ? $Fields[$Field][0] : '';
        }

        private static function DecodeVarint(string $Data, int &$Pos): int|false
        {
            $Result = 0;
            $Length = strlen($Data);
            for ($Shift = 0; $Shift < 64; $Shift += 7) {
                if ($Pos >= $Length) {
                    return false;
                }
                $Byte = ord($Data[$Pos++]);
                $Result |= ($Byte & 0x7F) << $Shift;
                if (($Byte & 0x80) === 0) {
                    return $Result;
                }
            }
            return false;
        }
    }

    /**
     * APPInfoData (Kommando 0xA301 / Antwort 0xA201).
     */
    class AppInfo
    {
        public const Offset = 28800;
        // Intervall in Sekunden für die Abfrage von Signalstärke und Versionen
        public const Interval = 300;

        /**
         * Erzeugt einen APPInfoDataResDTO Request.
         *
         * @param int $Time Unix-Timestamp
         * @return string Protobuf-Daten
         */
        public static function BuildRequest(int $Time): string
        {
            return Protobuf::EncodeBytesField(1, date('Y-m-d H:i:s', $Time)) // time_ymd_hms
                . Protobuf::EncodeVarintField(2, self::Offset)               // offset
                . Protobuf::EncodeVarintField(5, $Time);                      // time
        }

        /**
         * Wertet einen APPInfoDataReqDTO aus.
         *
         * @param string $Data Protobuf-Daten
         * @return array|false
         */
        public static function ParseResponse(string $Data): array|false
        {
            $Fields = Protobuf::DecodeFields($Data);
            if ($Fields === false) {
                return false;
            }
            $DtuInfo = [];
            if (isset($Fields[8][0]) && is_string($Fields[8][0])) {
                $DtuInfo = Protobuf::DecodeFields($Fields[8][0]);
                if ($DtuInfo === false) {
                    return false;
                }
            }
            $PvInfo = [];
            foreach ($Fields[11] ?? [] as $PvData) {
                if (!is_string($PvData)) {
                    continue;
                }
                $Pv = Protobuf::DecodeFields($PvData);
                if ($Pv === false) {
                    return false;
                }
                $PvInfo[] = [
                    'SerialNumber' => (string) Protobuf::IntValue($Pv, 2),
                    'SwVersion'    => Protobuf::IntValue($Pv, 4),
                    'HwVersion'    => Protobuf::IntValue($Pv, 6)
                ];
            }
            return [
                'DtuSerialNumber' => Protobuf::StringValue($Fields, 1),
                'DeviceNumber'    => Protobuf::IntValue($Fields, 3),
                'PvNumber'        => Protobuf::IntValue($Fields, 4),
                'DeviceKind'      => Protobuf::IntValue($DtuInfo, 1),
                'DtuSwVersion'    => Protobuf::IntValue($DtuInfo, 2),
                'DtuHwVersion'    => Protobuf::IntValue($DtuInfo, 3),
                'SignalStrength'  => Protobuf::IntValue($DtuInfo, 9),
                'WifiVersion'     => Protobuf::StringValue($DtuInfo, 11),
                'Dfs'             => Protobuf::IntValue($DtuInfo, 24),
                'Type'            => Protobuf::IntValue($DtuInfo, 26),
                'EncRand'         => Protobuf::StringValue($DtuInfo, 27),
                'PvInfo'          => $PvInfo
            ];
        }

        /**
         * Versionsnummer der DTU (SW und HW), z.B. 267 -> 00.01.11
         *
         * @param int $Version
         * @return string
         */
        public static function FormatDtuVersion(int $Version): string
        {
            return sprintf('%02d.%02d.%02d', intdiv($Version, 4096), intdiv($Version, 256) % 16, $Version % 256);
        }

        /**
         * Software-Version des Wechselrichters, z.B. 10008 -> 01.00.08
         *
         * @param int $Version
         * @return string
         */
        public static function FormatInverterSwVersion(int $Version): string
        {
            return sprintf('%02d.%02d.%02d', intdiv($Version, 10000), intdiv($Version % 10000, 100), $Version % 100);
        }

        /**
         * Hardware-Version des Wechselrichters, z.B. 256 -> 00.04.00
         *
         * @param int $Version
         * @return string
         */
        public static function FormatInverterHwVersion(int $Version): string
        {
            return sprintf('%02d.%02d.%02d', intdiv($Version, 2048), intdiv($Version, 64) % 32, $Version % 64);
        }
    }

    /**
     * Warnliste der Wechselrichter (Kommando 0xA304 WarnResDTO / Antwort 0xA204 WarnReqDTO).
     *
     * Die DTU liefert die Liste seitenweise, package_now ist 0-basiert, package_nub die Anzahl der Seiten.
     * Mit Action 50 (ALARM_LIST) wird die DTU vorher angestoßen, die Warnungen beim Wechselrichter abzufragen.
     * Feldnummern nach ioBroker.hoymiles (src/lib/proto/WarnData.proto, MIT, Copyright Eistee82).
     */
    class WarnData
    {
        // Wartezeit in Sekunden zwischen Action 50 und Abholen der Liste
        public const TriggerDelay = 5;
        // Spätestens nach dieser Zeit in Sekunden die Liste erneut abholen
        public const Interval = 300;
        // Schutz gegen Endlosschleifen bei fehlerhaften Seitenangaben
        public const MaxPackages = 20;

        /**
         * Erzeugt einen WarnResDTO Request für eine Seite der Warnliste.
         *
         * @param int $Time Unix-Timestamp
         * @param int $PackageNow 0-basierte Seite
         * @return string Protobuf-Daten
         */
        public static function BuildRequest(int $Time, int $PackageNow): string
        {
            return Protobuf::EncodeBytesField(1, date('Y-m-d H:i:s', $Time)) // ymd_hms
                . Protobuf::EncodeVarintField(2, $PackageNow)                // package_now
                . Protobuf::EncodeVarintField(4, AppInfo::Offset)            // offset
                . Protobuf::EncodeVarintField(5, $Time);                      // time
        }

        /**
         * Wertet einen WarnReqDTO aus.
         *
         * @param string $Data Protobuf-Daten
         * @return array|false
         */
        public static function ParseResponse(string $Data): array|false
        {
            $Fields = Protobuf::DecodeFields($Data);
            if ($Fields === false) {
                return false;
            }
            $Warnings = [];
            foreach ($Fields[6] ?? [] as $WarnData) {
                if (!is_string($WarnData)) {
                    return false;
                }
                $Warn = Protobuf::DecodeFields($WarnData);
                if ($Warn === false) {
                    return false;
                }
                $Warnings[] = [
                    'SerialNumber' => (string) Protobuf::IntValue($Warn, 1),
                    'Code'         => Protobuf::IntValue($Warn, 2),
                    'Number'       => Protobuf::IntValue($Warn, 3),
                    'StartTime'    => Protobuf::IntValue($Warn, 4),
                    'EndTime'      => Protobuf::IntValue($Warn, 5),
                    'Data1'        => Protobuf::IntValue($Warn, 6),
                    'Data2'        => Protobuf::IntValue($Warn, 7)
                ];
            }
            return [
                'DtuSerialNumber' => Protobuf::StringValue($Fields, 1),
                'Time'            => Protobuf::IntValue($Fields, 2),
                'PackageCount'    => max(Protobuf::IntValue($Fields, 3), 1),
                'PackageNow'      => Protobuf::IntValue($Fields, 4),
                'WarnDevice'      => Protobuf::IntValue($Fields, 5),
                'Warnings'        => $Warnings
            ];
        }
    }
}
