<?php

declare(strict_types=1);

use Hoymiles\DTU\Protobuf;
use Hoymiles\DTU\WarnCodes;
use Hoymiles\DTU\WarnData;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/../libs/HoymilesCrypt.php';
include_once __DIR__ . '/../libs/HoymilesWarnCodes.php';

class WarnDataTest extends TestCase
{
    public function testRequest(): void
    {
        $Fields = Protobuf::DecodeFields(WarnData::BuildRequest(1700000000, 2));
        $this->assertSame(date('Y-m-d H:i:s', 1700000000), $Fields[1][0]);
        $this->assertSame(2, $Fields[2][0]);
        $this->assertSame(28800, $Fields[4][0]);
        $this->assertSame(1700000000, $Fields[5][0]);
    }

    public function testEmptyResponse(): void
    {
        // Antwort einer DTU (V00.01.11) ohne Einträge
        $Result = WarnData::ParseResponse(hex2bin('0a0c34313433393233373332343610f9ad94d606280f'));
        $this->assertIsArray($Result);
        $this->assertSame('414392373246', $Result['DtuSerialNumber']);
        $this->assertSame(1791301369, $Result['Time']);
        $this->assertSame(1, $Result['PackageCount']);
        $this->assertSame(0, $Result['PackageNow']);
        $this->assertSame(15, $Result['WarnDevice']);
        $this->assertSame([], $Result['Warnings']);
    }

    public function testResponseWithWarnings(): void
    {
        $Warn1 = Protobuf::EncodeVarintField(1, 0x116491234567)
            . Protobuf::EncodeVarintField(2, 121)
            . Protobuf::EncodeVarintField(3, 7)
            . Protobuf::EncodeVarintField(4, 1791280000)
            . Protobuf::EncodeVarintField(5, 1791281000)
            . Protobuf::EncodeVarintField(6, -5);
        $Warn2 = Protobuf::EncodeVarintField(1, 0x116491234567)
            . Protobuf::EncodeVarintField(2, 38)
            . Protobuf::EncodeVarintField(4, 1791300000);
        $Data = Protobuf::EncodeBytesField(1, '414392373246')
            . Protobuf::EncodeVarintField(3, 2)
            . Protobuf::EncodeVarintField(4, 1)
            . Protobuf::EncodeBytesField(6, $Warn1)
            . Protobuf::EncodeBytesField(6, $Warn2);
        $Result = WarnData::ParseResponse($Data);
        $this->assertIsArray($Result);
        $this->assertSame(2, $Result['PackageCount']);
        $this->assertSame(1, $Result['PackageNow']);
        $this->assertCount(2, $Result['Warnings']);
        $this->assertSame((string) 0x116491234567, $Result['Warnings'][0]['SerialNumber']);
        $this->assertSame(121, $Result['Warnings'][0]['Code']);
        $this->assertSame(7, $Result['Warnings'][0]['Number']);
        $this->assertSame(1791281000, $Result['Warnings'][0]['EndTime']);
        $this->assertSame(-5, $Result['Warnings'][0]['Data1']);
        $this->assertSame(0, $Result['Warnings'][1]['EndTime']);
        // abgeschnittene Daten
        $this->assertFalse(WarnData::ParseResponse(substr($Data, 0, -3)));
    }

    public function testCodes(): void
    {
        $this->assertSame('Übertemperaturschutz', WarnCodes::GetText(121, true));
        $this->assertSame('Over temperature protection', WarnCodes::GetText(121, false));
        $this->assertSame('Unbekannter Code 99999', WarnCodes::GetText(99999, true));
    }
}
