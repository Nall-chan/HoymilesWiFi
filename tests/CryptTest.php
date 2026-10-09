<?php

declare(strict_types=1);

use Hoymiles\DTU\AppInfo;
use Hoymiles\DTU\Encryption;
use Hoymiles\DTU\PartNumber;
use Hoymiles\DTU\Protobuf;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/../libs/HoymilesCrypt.php';

class CryptTest extends TestCase
{
    private const EncRand = '000102030405060708090a0b0c0d0e0f';

    // Referenzwert erzeugt mit hoymiles-wifi (crypt_util.crypt_data)
    private const ReferenceCipher = '7838f6dd0f19167acc0a04418ca946580b39129c24c0f24ee0597a936609';

    public function testEncryptMatchesReference(): void
    {
        $this->assertTrue(Encryption::IsAvailable());
        $Cipher = Encryption::Encrypt(hex2bin(self::EncRand), 0xA311, 0x1234, 'hello protobuf');
        $this->assertSame(self::ReferenceCipher, bin2hex($Cipher));
    }

    public function testDecrypt(): void
    {
        $this->assertSame('hello protobuf', Encryption::Decrypt(hex2bin(self::EncRand), 0xA311, 0x1234, hex2bin(self::ReferenceCipher)));
        // falsche Sequenz -> Auth-Tag ungültig
        $this->assertFalse(Encryption::Decrypt(hex2bin(self::EncRand), 0xA311, 0x1235, hex2bin(self::ReferenceCipher)));
    }

    public function testNotEncryptedCommands(): void
    {
        $this->assertFalse(Encryption::IsEncryptedCommand(0xA301));
        $this->assertFalse(Encryption::IsEncryptedCommand(0xA201));
        $this->assertTrue(Encryption::IsEncryptedCommand(0xA311));
        $this->assertTrue(Encryption::IsEncryptedCommand(0xA211));
    }

    public function testAppInfoRequest(): void
    {
        // Gleicher Aufbau wie der APPInfoDataResDTO Request von hoymiles-wifi
        $this->assertSame(
            '0a13323032332d31312d31342032323a31333a32301080e1012880e2cfaa06',
            bin2hex(AppInfo::BuildRequest(1700000000))
        );
    }

    public function testAppInfoResponse(): void
    {
        $DtuInfo = Protobuf::EncodeVarintField(2, 4660)
            . Protobuf::EncodeVarintField(24, (1 << 25) | 3)
            . Protobuf::EncodeBytesField(27, hex2bin(self::EncRand));
        $Data = Protobuf::EncodeBytesField(1, '4143A0123456')
            . Protobuf::EncodeVarintField(2, 1700000000)
            . Protobuf::EncodeBytesField(8, $DtuInfo);
        $Info = AppInfo::ParseResponse($Data);
        $this->assertIsArray($Info);
        $this->assertSame('4143A0123456', $Info['DtuSerialNumber']);
        $this->assertSame(4660, $Info['DtuSwVersion']);
        $this->assertTrue(Encryption::IsEncryptedDtu($Info['Dfs']));
        $this->assertSame(self::EncRand, bin2hex($Info['EncRand']));
        $this->assertFalse(Encryption::IsEncryptedDtu(3));
        // abgeschnittene Daten
        $this->assertFalse(AppInfo::ParseResponse(substr($Data, 0, -3)));
    }

    public function testAppInfoPvInfo(): void
    {
        // Rohwerte live HMS-800W-2T (09.10.2026), Felder 3, 7, 8 unbekannt
        $Pv = Protobuf::EncodeVarintField(2, 22000000000001)
            . Protobuf::EncodeVarintField(3, 101)
            . Protobuf::EncodeVarintField(4, 10008)
            . Protobuf::EncodeVarintField(5, 0x10214201)
            . Protobuf::EncodeVarintField(6, 256)
            . Protobuf::EncodeVarintField(7, 768)
            . Protobuf::EncodeVarintField(8, 8193);
        $Info = AppInfo::ParseResponse(Protobuf::EncodeBytesField(11, $Pv));
        $this->assertIsArray($Info);
        $this->assertSame('22000000000001', $Info['PvInfo'][0]['SerialNumber']);
        $this->assertSame('01.00.08', AppInfo::FormatInverterSwVersion($Info['PvInfo'][0]['SwVersion']));
        $this->assertSame(0x10214201, $Info['PvInfo'][0]['PartNumber']);
        $this->assertSame('0x10214201', PartNumber::Format($Info['PvInfo'][0]['PartNumber']));
    }

    public function testPartNumber(): void
    {
        // live belegt
        $this->assertSame(['Model' => 'HMS-800W-2T', 'RatedPower' => 800], PartNumber::Decode(0x10214201));
        // Schema aus OpenDTU (Funk-Modelle)
        $this->assertSame(600, PartNumber::Decode(0x10211101)['RatedPower']);
        $this->assertSame(350, PartNumber::Decode(0x10202101)['RatedPower']);
        $this->assertSame('HMS-2000W-4T', PartNumber::Decode(0x10227101)['Model']);
        // unbekannt
        $this->assertNull(PartNumber::Decode(0));
        $this->assertNull(PartNumber::Decode(0x10323101));
        $this->assertNull(PartNumber::Decode(0x10216101));
    }
}
