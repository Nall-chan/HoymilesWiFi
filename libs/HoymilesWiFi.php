<?php

declare(strict_types=1);

namespace HoymilesWiFi{
    class GUID
    {
        public const IO = '{5972AA13-358F-A088-CEBD-207C289C9395}';
        public const Configurator = '{4062635D-2680-4A39-C364-05EB8B196DA9}';
        public const DTU = '{BB414362-B36F-81C5-2701-E968A29F58AD}';
        public const Inverter = '{52D8E128-5588-B496-4BE5-14E8EFD737B8}';
        public const SolarPort = '{65B18475-D1B7-825C-5958-5300C1100845}';
        public const IoToDTU = '{8EFD9E53-0198-9BA0-1B24-EB2E88D754EB}';
        public const IoToInverter = '{442F969D-D3B8-B949-EC0E-4B7BCC3C7853}';
        public const IoToSolarPort = '{85CA6AAE-6EE0-BE1F-FA4D-F0A3B569E3F7}';
        public const IoToConfigurator = '{CEE3BF9B-2A23-4433-ACAB-6E22F10D743F}';
        public const DeviceToIo = '{2651EA6C-47E9-BA79-7410-382895EC8244}';
        public const LocationControl = '{45E97A63-F870-408A-B259-2933F7EABF74}';
    }

    class ConfigArray
    {
        public const NbrOfInverter = 'NbrOfInverter';
        public const NbrOfSolarPort = 'NbrOfSolarPort';
    }
}

namespace HoymilesWiFi\IO{
    class Property
    {
        public const Active = 'Open';
        public const Host = 'Host';
        public const Port = 'Port';
        public const RequestInterval = 'RequestInterval';
        public const SuppressConnectionError = 'SuppressConnectionError';
        public const WatchdogType = 'WatchdogType';
        public const LocationId = 'LocationId';
        public const StartVariableId = 'StartVariableId';
        public const StopVariableId = 'StopVariableId';
        public const DayValue = 'DayValue';
        public const NightValue = 'NightValue';
        public const WatchdogInterval = 'WatchdogInterval';
        public const WatchdogCondition = 'WatchdogCondition';
    }
    class WatchdogType
    {
        public const NONE = 0;
        public const TIME_OR_VALUES = 1;
        public const PING = 2;
        public const CONDITION = 3;

    }
    class Attribute
    {
        public const LastState = 'LastState';
    }

    class Timer
    {
        public const RequestState = 'RequestState';
        public const Watchdog = 'Watchdog';
    }

    class InstanceStatus
    {
        public const TimeoutError = IS_EBASE + 2;
    }

    class Locks
    {
        public const SendSequenz = 'SendSequenz';
        public const ReplyDeviceFrames = 'ReplyDeviceFrames';
    }
}

namespace HoymilesWiFi\Inverter{

    class Actions
    {
        public const DTU_REBOOT = 1;
        public const MI_REBOOT = 3;
        public const MI_START = 6;
        public const MI_SHUTDOWN = 7;
        public const LIMIT_POWER = 8;
        public const PERFORMANCE_DATA_MODE = 33;
        public const ALARM_LIST = 50;
        public const GW_REBOOT = 4096;
        public const INV_START = 8193;
        public const INV_SHUTDOWN = 8194;
        public const INV_REBOOT = 8195;
    }
    class Property
    {
        public const Number = 'Number';
    }

    class Variables
    {
        public const Voltage = 'v'; // 0.1 V
        public const Frequenz = 'freq'; // 0.01 Hz
        public const Power = 'p'; // 0.1 W
        public const Current = 'i'; // 0.01 A
        public const PowerFactor = 'pf'; // 0.1 Pf
        public const Temp = 'temp'; // 0.1 °C
        public const Link = 'link'; // bool ?
        public const PowerLimit = 'pLim'; // 0.1 %
        public static $Vars = [
            self::Voltage     => [
                'Voltage',
                VARIABLETYPE_FLOAT,
                [
                    'ICON'                => 'bolt',
                    'DECIMAL_SEPARATOR'   => 'Client',
                    'COLOR'               => -1,
                    'MIN'                 => 0,
                    'DIGITS'              => 1,
                    'MAX'                 => 0,
                    'PRESENTATION'        => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'INTERVALS'           => '[]',
                    'INTERVALS_ACTIVE'    => false,
                    'PERCENTAGE'          => false,
                    'PREFIX'              => '',
                    'SUFFIX'              => ' V',
                    'THOUSANDS_SEPARATOR' => 'Client',
                    'USAGE_TYPE'          => 0
                ],
                0.1
            ],
            self::Frequenz    => [
                'Frequenz',
                VARIABLETYPE_FLOAT,
                [
                    'ICON'                => 'Electricity',
                    'DECIMAL_SEPARATOR'   => 'Client',
                    'COLOR'               => -1,
                    'MIN'                 => 0,
                    'DIGITS'              => 2,
                    'MAX'                 => 0,
                    'PRESENTATION'        => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'INTERVALS'           => '[]',
                    'INTERVALS_ACTIVE'    => false,
                    'PERCENTAGE'          => false,
                    'PREFIX'              => '',
                    'SUFFIX'              => ' Hz',
                    'THOUSANDS_SEPARATOR' => '',
                    'USAGE_TYPE'          => 0,
                ],
                0.01
            ],
            self::Power       => [
                'Power',
                VARIABLETYPE_FLOAT,
                [
                    'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'TEMPLATE'     => VARIABLE_TEMPLATE_VALUE_PRESENTATION_POWER
                ],
                0.1
            ],
            self::Current     => [
                'Current',
                VARIABLETYPE_FLOAT,
                [
                    'ICON'                => 'bolt',
                    'DECIMAL_SEPARATOR'   => 'Client',
                    'COLOR'               => -1,
                    'MIN'                 => 0,
                    'DIGITS'              => 2,
                    'MAX'                 => 0,
                    'PRESENTATION'        => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'INTERVALS'           => '[]',
                    'INTERVALS_ACTIVE'    => false,
                    'PERCENTAGE'          => false,
                    'PREFIX'              => '',
                    'SUFFIX'              => ' A',
                    'THOUSANDS_SEPARATOR' => 'Client',
                    'USAGE_TYPE'          => 0
                ],
                0.01
            ],
            self::PowerFactor => [
                'Power factor',
                VARIABLETYPE_FLOAT,
                [
                    'MIN'              => 0,
                    'DIGITS'           => 1,
                    'MULTILINE'        => false,
                    'ICON'             => 'Gauge',
                    'MAX'              => 100,
                    'PRESENTATION'     => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'INTERVALS'        => '[]',
                    'INTERVALS_ACTIVE' => false,
                    'PERCENTAGE'       => true,
                    'PREFIX'           => '',
                    'SUFFIX'           => ' %',
                    'USAGE_TYPE'       => 0
                ],
                0.1
            ],
            self::Temp        => [
                'Temperature',
                VARIABLETYPE_FLOAT,
                [
                    'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'TEMPLATE'     => VARIABLE_TEMPLATE_VALUE_PRESENTATION_ROOM_TEMPERATURE
                ],
                0.1
            ],
            self::Link        => [
                'Link',
                VARIABLETYPE_BOOLEAN,
                [
                    'MIN'              => 0,
                    'DIGITS'           => 0,
                    'MULTILINE'        => false,
                    'ICON'             => 'Warning',
                    'INTERVALS_ACTIVE' => true,
                    'MAX'              => 1,
                    'PRESENTATION'     => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'PERCENTAGE'       => false,
                    'OPTIONS'          => '[{"Value":false,"Caption":"Alarm","IconActive":false,"IconValue":"","ColorActive":true,"ColorValue":16711680},{"Value":true,"Caption":"OK","IconActive":false,"IconValue":"","ColorActive":true,"ColorValue":-1}]',
                    'PREFIX'           => '',
                    'SUFFIX'           => '',
                    'USAGE_TYPE'       => 0,
                ]
            ],
            self::PowerLimit  => [
                'Power Limit',
                VARIABLETYPE_INTEGER,
                [
                    'MIN'              => 0,
                    'DIGITS'           => 0,
                    'CUSTOM_GRADIENT'  => '[]',
                    'GRADIENT_TYPE'    => 0,
                    'ICON'             => 'Intensity',
                    'MAX'              => 100,
                    'PRESENTATION'     => VARIABLE_PRESENTATION_SLIDER,
                    'INTERVALS'        => '[]',
                    'INTERVALS_ACTIVE' => false,
                    'PERCENTAGE'       => true,
                    'PREFIX'           => '',
                    'STEP_SIZE'        => 1,
                    'SUFFIX'           => ' %',
                    'USAGE_TYPE'       => 2,
                ],
                0.1,
                true
            ],
        ];
    }

    class SetPowerLimit
    {
        public static $DataPrefix = [
            '',
            'A',
            'B',
            'C'
        ];
    }
}

namespace HoymilesWiFi\SolarPort{
    class Property
    {
        public const Port = 'Port';
    }

    class Variables
    {
        public const Voltage = 'v'; // 0.1 V
        public const Current = 'i'; // 0.01 A
        public const Power = 'p'; // 0.1 W
        public const EnergyTotal = 'et'; // Wh
        public const EnergyDaily = 'ed'; // Wh

        public static $Vars = [
            self::Voltage => [
                'Voltage',
                VARIABLETYPE_FLOAT,
                [
                    'ICON'                => 'bolt',
                    'DECIMAL_SEPARATOR'   => 'Client',
                    'COLOR'               => -1,
                    'MIN'                 => 0,
                    'DIGITS'              => 1,
                    'MAX'                 => 0,
                    'PRESENTATION'        => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'INTERVALS'           => '[]',
                    'INTERVALS_ACTIVE'    => false,
                    'PERCENTAGE'          => false,
                    'PREFIX'              => '',
                    'SUFFIX'              => ' V',
                    'THOUSANDS_SEPARATOR' => 'Client',
                    'USAGE_TYPE'          => 0
                ],
                0.1
            ],
            self::Current => [
                'Current',
                VARIABLETYPE_FLOAT,
                [
                    'ICON'                => 'bolt',
                    'DECIMAL_SEPARATOR'   => 'Client',
                    'COLOR'               => -1,
                    'MIN'                 => 0,
                    'DIGITS'              => 2,
                    'MAX'                 => 0,
                    'PRESENTATION'        => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'INTERVALS'           => '[]',
                    'INTERVALS_ACTIVE'    => false,
                    'PERCENTAGE'          => false,
                    'PREFIX'              => '',
                    'SUFFIX'              => ' A',
                    'THOUSANDS_SEPARATOR' => 'Client',
                    'USAGE_TYPE'          => 0
                ],
                0.01
            ],
            self::Power => [
                'Power',
                VARIABLETYPE_FLOAT,
                [
                    'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'TEMPLATE'     => VARIABLE_TEMPLATE_VALUE_PRESENTATION_POWER
                ],
                0.1
            ],
            self::EnergyTotal => [
                'Energy total',
                VARIABLETYPE_FLOAT,
                [
                    'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'TEMPLATE'     => VARIABLE_TEMPLATE_VALUE_PRESENTATION_ENERGY
                ],
                1
            ],
            self::EnergyDaily => [
                'Energy daily',
                VARIABLETYPE_FLOAT,
                [
                    'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'TEMPLATE'     => VARIABLE_TEMPLATE_VALUE_PRESENTATION_ENERGY
                ],
                1
            ]
        ];
    }
}

namespace HoymilesWiFi\DTU{
    class Variables
    {
        public const SerialNumber = 'sn';
        public const Time = 'time'; // 0.01 A
        public const CurrentPower = 'pvCurrentPower'; // W 0.1
        public const DailyYield = 'pvDailyYield'; // Wh

        public static $Vars = [
            self::Time         => [
                'Time',
                VARIABLETYPE_INTEGER,
                [
                    'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
                    'TEMPLATE'     => VARIABLE_TEMPLATE_DATE_TIME,
                ]
            ],
            self::CurrentPower => [
                'Power',
                VARIABLETYPE_FLOAT,
                [
                    'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'TEMPLATE'     => VARIABLE_TEMPLATE_VALUE_PRESENTATION_POWER
                ],
                0.1
            ],
            self::DailyYield   => [
                'Energy daily',
                VARIABLETYPE_FLOAT,
                [
                    'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                    'TEMPLATE'     => VARIABLE_TEMPLATE_VALUE_PRESENTATION_ENERGY
                ],
                1
            ]

        ];
    }
}

namespace Hoymiles\DTU{
    class SendStream
    {
        public const Header = 'HM';
    }

    class Commands
    {
        // InfoDataResDTO -> wifi SignalStrength
        public const InfoDataResDTO = 0xA301; //Response: A201 InfoDataReqDTO

        //HBResDTO
        public const HBResDTO = 0xA302; // Response: A202 HBReqDTO

        //public const RegisterResDTO = 0x8302; // oder auch 0xA302
        //public const StorageDataRes = 0x8303; // oder auch 0xA303

        // WarnResDTO
        public const WarnResDTO = 0xA304; // Response: A204 WarnReqDTO

        // CommandResDTO //powerlimit
        public const CommandResDTO = 0xA305; // Response: A205 CommandReqDTO

        // CommandStatusResDTO
        public const CommandStatusResDTO = 0xA306; // Response: A206 CommandStatusReqDTO

        // DevConfigFetchResDTO
        public const DevConfigFetchResDTO = 0xA307; // Response: A207 DevConfigFetchReqDTO

        // DevConfigPutResDTO
        public const DevConfigPutResDTO = 0xA308; // Response: A208 DevConfigPutReqDTO

        // GetConfigResDTO
        public const GetConfigResDTO = 0xA309; // Response: A209 GetConfigReqDTO

        // noch testen
        // SetConfigResDTO
        public const SetConfigResDTO = 0xA310; // Response: A210 SetConfigReqDTO

        // RealDataResDTO
        public const RealDataResDTO = 0xA311; // Response A211 RealDataReqDTO

        public const GPSTResDTO = 0xA312;
        public const AutoSearch = 0xA313;

        // NetworkInfoResDTO
        public const NetworkInfoResDTO = 0xA314; // Response: A214 NetworkInfoReqDTO

        public const AppGetHistPowerRes = 0xA315; // Response: AppGetHistPowerReq
        public const AppGetHistEDRes = 0xA316; // Response: AppGetHistEDReq
    }
}