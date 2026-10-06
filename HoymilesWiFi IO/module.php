<?php

declare(strict_types=1);

$AutoLoader = new AutoLoaderHoymilesWiFi('Google\Protobuf');
$AutoLoader->register();

class AutoLoaderHoymilesWiFi
{
    private $namespace;

    public function __construct($namespace = null)
    {
        $this->namespace = $namespace;
    }

    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass($className)
    {
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

require_once dirname(__DIR__) . '/libs/HoymilesWiFi.php';
require_once dirname(__DIR__) . '/libs/HoymilesCrypt.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/RealDataResDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/RealDataReqDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/CommandReqDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/CommandResDTO.php';
/*
require_once dirname(__DIR__) . '/libs/Hoymiles/GetConfigReqDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/GetConfigResDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/InfoDataReqDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/InfoDataResDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/NetworkInfoReqDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/NetworkInfoResDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/WarnReqDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/WarnResDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/CommandStatusReqDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/CommandStatusResDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/DevConfigFetchResDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/DevConfigFetchReqDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/EventDataReqDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/EventDataResDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/HBReqDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/HBResDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/WWVDataReqDTO.php';
require_once dirname(__DIR__) . '/libs/Hoymiles/WWVDataResDTO.php';
 */
eval('declare(strict_types=1);namespace HoymilesIO {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/DebugHelper.php') . '}');
eval('declare(strict_types=1);namespace HoymilesIO {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/BufferHelper.php') . '}');
eval('declare(strict_types=1);namespace HoymilesIO {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/SemaphoreHelper.php') . '}');

/**
 * @property int $Sequenz
 * @property bool $EncryptionChecked
 * @property string $EncRand
 * @property int $LastAppInfo
 * @property array $InverterSerials
 * @property array $WarnNumbers
 * @property int $WarnTrigger
 * @property int $LastWarnData
 * @property int $NbrOfInverter
 * @property int $NbrOfSolarPort
 * @property int $DayVariableId
 * @property bool $DayVariableIsTimeStamp
 * @property int $NightVariableId
 * @property bool $NightVariableIsTimeStamp
 *
 * @method bool lock(string $ident)
 * @method void unlock(string $ident)
 * @method bool SendDebug(string $Message, mixed $Data, int $Format)
 */
class HoymilesWiFiIO extends IPSModuleStrict
{
    use \HoymilesIO\DebugHelper;
    use \HoymilesIO\Semaphore;
    use \HoymilesIO\BufferHelper;

    /**
     * Create
     *
     * @return void
     */
    public function Create(): void
    {
        parent::Create();
        $this->Sequenz = 0;
        $this->EncryptionChecked = false;
        $this->EncRand = '';
        $this->LastAppInfo = 0;
        $this->InverterSerials = [];
        $this->WarnNumbers = [];
        $this->WarnTrigger = 0;
        $this->LastWarnData = 0;
        $this->NbrOfInverter = 0;
        $this->NbrOfSolarPort = 0;
        $this->DayVariableId = 1;
        $this->DayVariableIsTimeStamp = false;
        $this->NightVariableId = 1;
        $this->NightVariableIsTimeStamp = false;
        $this->RegisterPropertyBoolean(\HoymilesWiFi\IO\Property::Active, false);
        $this->RegisterPropertyString(\HoymilesWiFi\IO\Property::Host, '');
        $this->RegisterPropertyInteger(\HoymilesWiFi\IO\Property::Port, 10081);
        $this->RegisterPropertyInteger(\HoymilesWiFi\IO\Property::RequestInterval, 60);
        $this->RegisterPropertyBoolean(\HoymilesWiFi\IO\Property::SuppressConnectionError, true);
        $this->RegisterPropertyInteger(\HoymilesWiFi\IO\Property::WatchdogType, \HoymilesWiFi\io\WatchdogType::NONE);
        $this->RegisterPropertyInteger(\HoymilesWiFi\IO\Property::LocationId, 1);
        $this->RegisterPropertyInteger(\HoymilesWiFi\IO\Property::StartVariableId, 1);
        $this->RegisterPropertyInteger(\HoymilesWiFi\IO\Property::StopVariableId, 1);
        $this->RegisterPropertyString(\HoymilesWiFi\IO\Property::DayValue, '""');
        $this->RegisterPropertyString(\HoymilesWiFi\IO\Property::NightValue, '""');
        $this->RegisterPropertyInteger(\HoymilesWiFi\IO\Property::WatchdogInterval, 0);
        $this->RegisterPropertyString(\HoymilesWiFi\IO\Property::WatchdogCondition, '');
        $this->RegisterAttributeInteger(\HoymilesWiFi\IO\Attribute::LastState, IS_CREATING);
        $this->RegisterTimer(\HoymilesWiFi\IO\Timer::Watchdog, 0, 'IPS_RequestAction(' . $this->InstanceID . ',"' . \HoymilesWiFi\IO\Timer::Watchdog . '",true);');
        $this->RegisterTimer(\HoymilesWiFi\IO\Timer::RequestState, 0, 'IPS_RequestAction(' . $this->InstanceID . ',"' . \HoymilesWiFi\IO\Timer::RequestState . '",true);');
    }

    /**
     * Migrate
     *
     * @param  string $JSONData
     * @return string
     */
    public function Migrate(string $JSONData): string
    {
        $Data = json_decode($JSONData);
        if (!property_exists($Data->configuration, \HoymilesWiFi\IO\Property::WatchdogType)) {
            $Data->configuration->WatchdogType = \HoymilesWiFi\IO\WatchdogType::NONE;
            if (($Data->configuration->StartVariableId > 1) && ($Data->configuration->StopVariableId > 1)) {
                $Data->configuration->WatchdogType = \HoymilesWiFi\IO\WatchdogType::TIME_OR_VALUES;
            }
        }
        return json_encode($Data);
    }

    /**
     * ApplyChanges
     *
     * @return void
     */
    public function ApplyChanges(): void
    {
        $this->UnregisterVariableWatch($this->DayVariableId);
        $this->UnregisterVariableWatch($this->NightVariableId);
        $this->SetTimerInterval(\HoymilesWiFi\IO\Timer::RequestState, 0);
        $this->SetTimerInterval(\HoymilesWiFi\IO\Timer::Watchdog, 0);
        $this->Sequenz = 0;
        $this->EncryptionChecked = false;
        $this->EncRand = '';
        $this->LastAppInfo = 0;
        $this->InverterSerials = [];
        $this->WarnNumbers = [];
        $this->WarnTrigger = 0;
        $this->LastWarnData = 0;
        $this->DayVariableIsTimeStamp = false;
        $this->NightVariableIsTimeStamp = false;
        $this->DayVariableId = 1;
        $this->NightVariableId = 1;
        parent::ApplyChanges();
        $this->SetSummary($this->ReadPropertyString(\HoymilesWiFi\IO\Property::Host));
        // Wenn Kernel nicht bereit, dann warten... KR_READY kommt ja gleich
        if (IPS_GetKernelRunlevel() != KR_READY) {
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            return;
        }
        if ($this->ReadPropertyString(\HoymilesWiFi\IO\Property::Host) == '') {
            $this->SetStatus(IS_INACTIVE);
            return;
        }
        if (!$this->ReadPropertyBoolean(\HoymilesWiFi\IO\Property::Active)) {
            $this->SetStatus(IS_INACTIVE);
            return;
        }
        $WatchdogType = $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::WatchdogType);
        switch ($WatchdogType) {
            case \HoymilesWiFi\IO\WatchdogType::NONE:
                $this->SetActive();
                break;
            case \HoymilesWiFi\IO\WatchdogType::TIME_OR_VALUES:
                $this->DayVariableId = $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::StartVariableId);
                $this->NightVariableId = $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::StopVariableId);
                $this->RegisterVariableWatch($this->DayVariableId);
                $this->RegisterVariableWatch($this->NightVariableId);
                if (($this->DayVariableId > 1) && ($this->DayVariableId > 1)) {
                    $this->SendDebug(__FUNCTION__, 'Day & Night are set', 0);
                    if (!IPS_VariableExists($this->NightVariableId)) {
                        $this->SendDebug(__FUNCTION__, 'Night INVALID', 0);
                        $this->SetStatus(IS_EBASE + 1);
                        return;
                    }
                    if (!IPS_VariableExists($this->DayVariableId)) {
                        $this->SendDebug(__FUNCTION__, 'Day INVALID', 0);
                        $this->SetStatus(IS_EBASE + 1);
                        return;
                    }
                    $this->NightVariableIsTimeStamp = $this->VariableIsTimestamp($this->NightVariableId);
                    $this->DayVariableIsTimeStamp = $this->VariableIsTimestamp($this->DayVariableId);
                    if ($this->DayVariableIsTimeStamp) {
                        $this->DayNightCheck(GetValue($this->DayVariableId), GetValue($this->NightVariableId));
                    } else {
                        if (!$this->DayCheck(GetValue($this->DayVariableId))) {
                            $this->StartWithLastStateCheck();
                        }
                    }
                    return;
                }
                $this->SetStatus(IS_EBASE + 1);
                break;
            default:
                if ($this->CheckCondition()) {
                    $this->SetActive();
                } else {
                    $this->SetInActive();
                }
                break;
        }
    }

    public function SetActive(): bool
    {
        if ($this->ReadPropertyString(\HoymilesWiFi\IO\Property::Host) == '') {
            return false;
        }
        if (!$this->ReadPropertyBoolean(\HoymilesWiFi\IO\Property::Active)) {
            return false;
        }
        if ($this->RealDataResDTO()) {
            $this->SetStatus(IS_ACTIVE);
            return true;
        }
        return false;
    }

    public function SetInactive(): bool
    {
        $this->SetStatus(IS_INACTIVE);
        return true;
    }

    /**
     * Nachrichten aus der Nachrichtenschlange verarbeiten.
     *
     * @param int       $TimeStamp
     * @param int       $SenderID
     * @param int       $Message
     * @param array|int $Data
     */
    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->UnregisterMessage(0, IPS_KERNELSTARTED);
                $this->KernelReady();
                break;
            case VM_UPDATE:
                if ($SenderID == $this->DayVariableId) {
                    if ($this->DayVariableIsTimeStamp) {
                        $this->DayNightCheck($Data[0], GetValue($this->NightVariableId));
                    } else {
                        $this->DayCheck($Data[0]);
                    }
                }
                if ($SenderID == $this->NightVariableId) {
                    if ($this->NightVariableIsTimeStamp) {
                        $this->DayNightCheck(GetValue($this->DayVariableId), $Data[0]);
                    } else {
                        $this->NightCheck($Data[0]);
                    }
                }
                break;
            case VM_DELETE:
                if ($SenderID == $this->DayVariableId) {
                    $this->UnregisterVariableWatch($this->DayVariableId);
                    $this->DayVariableId = 1;
                }
                if ($SenderID == $this->NightVariableId) {
                    $this->UnregisterVariableWatch($this->NightVariableId);
                    $this->NightVariableId = 1;
                }
                break;
        }
    }

    /**
     * Interne Funktion des SDK.
     */
    public function RequestAction(string $Ident, mixed $Value): void
    {
        switch ($Ident) {
            case \HoymilesWiFi\IO\Timer::RequestState:
                $this->RequestState();
                return;
            case \HoymilesWiFi\IO\Timer::Watchdog:
                if ($this->CheckCondition()) {
                    $this->SetTimerInterval(\HoymilesWiFi\IO\Timer::Watchdog, 0);
                    $this->SetActive();
                }
                return;
            case \HoymilesWiFi\IO\Property::WatchdogType:
                $this->FormUpdateByWatchdogType($Value);
                return;
            case \HoymilesWiFi\IO\Property::LocationId:
                $this->FormUpdateBySelectLocationControl($Value);
                return;
            case \HoymilesWiFi\IO\Property::DayValue:
            case \HoymilesWiFi\IO\Property::NightValue:
                $this->FormUpdateByDayOrNightVariable($Value, $Ident);
                return;
        }
    }

    public function GetConfigurationForm(): string
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->GetStatus() == IS_CREATING) {
            return json_encode($Form);
        }
        $WatchdogType = $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::WatchdogType);
        switch ($WatchdogType) {
            case \HoymilesWiFi\IO\WatchdogType::NONE:
                break;
            case \HoymilesWiFi\IO\WatchdogType::TIME_OR_VALUES:
                $Form['elements'][2]['items'][0]['items'][1]['visible'] = true;
                $StartVariableId = $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::StartVariableId);
                $Form['elements'][2]['items'][0]['items'][1]['popup']['items'][1]['items'][1]['variableID'] = $StartVariableId;
                if (IPS_VariableExists($StartVariableId) && !$this->VariableIsTimestamp($StartVariableId)) {
                    $Form['elements'][2]['items'][0]['items'][1]['popup']['items'][1]['items'][1]['visible'] = true;
                }
                $StopVariableId = $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::StopVariableId);
                $Form['elements'][2]['items'][0]['items'][1]['popup']['items'][2]['items'][1]['variableID'] = $StopVariableId;
                if (IPS_VariableExists($StopVariableId) && !$this->VariableIsTimestamp($StopVariableId)) {
                    $Form['elements'][2]['items'][0]['items'][1]['popup']['items'][2]['items'][1]['visible'] = true;
                }
                break;
            case \HoymilesWiFi\IO\WatchdogType::PING:
                $Form['elements'][2]['items'][0]['items'][2]['visible'] = true;
                break;
            case \HoymilesWiFi\IO\WatchdogType::CONDITION:
                $Form['elements'][2]['items'][0]['items'][2]['visible'] = true;
                $Form['elements'][2]['items'][0]['items'][2]['popup']['items'][0]['items'][1]['visible'] = true;

                break;
        }
        $this->SendDebug('FORM', json_encode($Form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);
        return json_encode($Form);
    }

    public function RequestState(): bool
    {
        if ($this->GetStatus() != IS_ACTIVE) {
            trigger_error($this->Translate('Instance is not active.'), E_USER_NOTICE);
            return false;
        }
        return $this->RealDataResDTO();
    }

    public function ForwardData($JSONString): string
    {
        $Data = json_decode($JSONString, true);
        switch ($Data['Function']) {
            case 'ListDevices':
                return serialize(
                    [
                        \HoymilesWiFi\ConfigArray::NbrOfInverter  => $this->NbrOfInverter,
                        \HoymilesWiFi\ConfigArray::NbrOfSolarPort => $this->NbrOfSolarPort
                    ]
                );
            case 'SetPowerLimit':
                $Request = new \Hoymiles\CommandResDTO();
                $Request->setTime(time());
                $Request->setTid(time());
                $Request->setPackageNub(1);
                $Request->setAction(\HoymilesWiFi\Inverter\Actions::LIMIT_POWER);
                $Request->setData($Data['Data']);
                $RequestBytes = $Request->serializeToString();
                $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::CommandResDTO, $RequestBytes);
                if (!$ResultStream) {
                    return serialize(false);
                }
                $Result = new \Hoymiles\CommandReqDTO();
                $Result->mergeFromString($ResultStream);
                return serialize($Result->getErrCode() == 0);
            case 'StartInverter':
                $Request = new \Hoymiles\CommandResDTO();
                $Request->setTime(time());
                $Request->setTid(time());
                $Request->setPackageNub(1);
                $Request->setAction(\HoymilesWiFi\Inverter\Actions::MI_START);
                $RequestBytes = $Request->serializeToString();
                $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::CommandResDTO, $RequestBytes);
                if (!$ResultStream) {
                    return serialize(false);
                }
                $Result = new \Hoymiles\CommandReqDTO();
                $Result->mergeFromString($ResultStream);
                $this->SendDebug('StartInverter Result', $Result->serializeToJsonString(\Google\Protobuf\PrintOptions::EMIT_DEFAULTS), 0);
                return serialize($Result->getErrCode() == 0);
            case 'StopInverter':
                $Request = new \Hoymiles\CommandResDTO();
                $Request->setTime(time());
                $Request->setTid(time());
                $Request->setPackageNub(1);
                $Request->setAction(\HoymilesWiFi\Inverter\Actions::MI_SHUTDOWN);
                $RequestBytes = $Request->serializeToString();
                $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::CommandResDTO, $RequestBytes);
                if (!$ResultStream) {
                    return serialize(false);
                }
                $Result = new \Hoymiles\CommandReqDTO();
                $Result->mergeFromString($ResultStream);
                $this->SendDebug('Stop Result', $Result->serializeToJsonString(\Google\Protobuf\PrintOptions::EMIT_DEFAULTS), 0);
                return serialize($Result->getErrCode() == 0);
            case 'RebootDTU':
                $Request = new \Hoymiles\CommandResDTO();
                $Request->setTid(time());
                $Request->setPackageNub(1);
                $Request->setAction(\HoymilesWiFi\Inverter\Actions::DTU_REBOOT);
                return serialize($this->SendCloudCommand($Request, 'RebootDTU'));
            case 'RebootInverter':
                $InverterSerials = $this->InverterSerials ?: [];
                $Number = (int) $Data['Data'];
                if (!isset($InverterSerials[$Number])) {
                    trigger_error($this->Translate('Serial number of inverter is not known yet.'), E_USER_NOTICE);
                    return serialize(false);
                }
                $Request = new \Hoymiles\CommandResDTO();
                $Request->setTid(time());
                $Request->setPackageNub(1);
                $Request->setDevKind(\HoymilesWiFi\Inverter\DeviceKind::DTU);
                $Request->setAction(\HoymilesWiFi\Inverter\Actions::INV_REBOOT);
                $Request->setMiToSn([(int) $InverterSerials[$Number]]);
                return serialize($this->SendCloudCommand($Request, 'RebootInverter'));
        }
        return '';
    }

    /*
        protected function Test(): bool
        {
        // WWVDataResDTO commando unbekannt
        // EventDataResDTO commando unbekannt
     */
    /*
        $Request = new \Hoymiles\EventDataResDTO();
        $Request->setTime(time());
        $Request->setOffset(28800);
        //$Request->setYmdHmsStart(date('Y-m-d H:i:s', time()-1841976410));//-60*60*24*7));
        $RequestBytes = $Request->serializeToString();
        $ResultStream = $this->SendCommand(0xA301, $RequestBytes);
        if (!$ResultStream) {
            return false;
        }
        $Result = new \Hoymiles\EventDataReqDTO();
        $Result->mergeFromString($ResultStream);
     */
    /*
        $Request = new \Hoymiles\DevConfigFetchResDTO();
        $RequestBytes = $Request->serializeToString();
        $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::DevConfigFetchResDTO, $RequestBytes);
        if (!$ResultStream) {
            return false;
        }
        $Result = new \Hoymiles\DevConfigFetchReqDTO();
        $Result->mergeFromString($ResultStream);
     */
    /*
        $Request = new \Hoymiles\CommandStatusResDTO();
        $Request->setPackageNow(1);
        $Request->setTime(time());
        $Request->setAction(8);
        $RequestBytes = $Request->serializeToString();
        $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::CommandStatusResDTO, $RequestBytes);
        if (!$ResultStream) {
            return false;
        }
        $Result = new \Hoymiles\CommandStatusReqDTO();
        $Result->mergeFromString($ResultStream);
     */
    /*
    $Request = new \Hoymiles\CommandResDTO();
    $Request->setTime(time());
    $Request->setTid(time());
    $Request->setPackageNub(1);
    $Request->setAction(8);
    $Request->setData("A:800\r");
    $RequestBytes = $Request->serializeToString();
    $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::CommandResDTO, $RequestBytes);
    if (!$ResultStream) {
        return false;
    }
    $Result = new \Hoymiles\CommandReqDTO();
    $Result->mergeFromString($ResultStream);
     */
    /*
        $Request = new \Hoymiles\WarnResDTO();
        $RequestBytes = $Request->serializeToString();
        $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::WarnResDTO, $RequestBytes);
        if (!$ResultStream) {
            return false;
        }
        $Result = new \Hoymiles\WarnReqDTO();
        $Result->mergeFromString($ResultStream);
     */
    /*
        $Request = new \Hoymiles\NetworkInfoResDTO();
        $RequestBytes = $Request->serializeToString();
        $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::NetworkInfoResDTO, $RequestBytes);
        if (!$ResultStream) {
            return false;
        }
        $Result = new \Hoymiles\NetworkInfoReqDTO();
        $Result->mergeFromString($ResultStream);
     */
    /*
        $Request = new \Hoymiles\InfoDataResDTO();
        $RequestBytes = $Request->serializeToString();
        $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::InfoDataResDTO, $RequestBytes);
        if (!$ResultStream) {
            return false;
        }
        $Result = new \Hoymiles\InfoDataReqDTO();
        $Result->mergeFromString($ResultStream);
     */
    /*
        $Request = new \Hoymiles\GetConfigResDTO();
        $RequestBytes = $Request->serializeToString();
        $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::GetConfig, $RequestBytes);
        if (!$ResultStream) {
            return false;
        }
        $Result = new \Hoymiles\GetConfigReqDTO(); // rssi in -db ?
        $Result->mergeFromString($ResultStream);
     */

    /*
        $Json = $Result->serializeToJsonString(\Google\Protobuf\PrintOptions::EMIT_DEFAULTS);
        $this->SendDebug('TEST', $Json, 0);
        $this->SendDebug('TEST', json_decode($Json, true), 0);
     */
    /*
        return true;
        }
     */

    /**
     * Wird ausgeführt wenn der Kernel hochgefahren wurde.
     */
    protected function KernelReady(): void
    {
        $this->ApplyChanges();
        $this->StartWithLastStateCheck();
    }

    protected function SetStatus(int $NewState): bool
    {
        $this->SendDebug(__FUNCTION__, $NewState, 0);
        switch ($NewState) {
            case IS_ACTIVE:
                $this->SetTimerInterval(\HoymilesWiFi\IO\Timer::RequestState, $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::RequestInterval) * 1000);
                $this->WriteAttributeInteger(\HoymilesWiFi\IO\Attribute::LastState, IS_ACTIVE);
                $this->SendDebug(__FUNCTION__, 'LastState: ' . IS_ACTIVE, 0);
                break;
            case IS_INACTIVE:
                $this->WriteAttributeInteger(\HoymilesWiFi\IO\Attribute::LastState, IS_INACTIVE);
                $this->SendDebug(__FUNCTION__, 'LastState: ' . IS_INACTIVE, 0);
                // And deactivate timer
                // No break. Add additional comment above this line if intentional
            default:
                $this->SetTimerInterval(\HoymilesWiFi\IO\Timer::RequestState, 0);
                if ($this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::WatchdogType) >= \HoymilesWiFi\IO\WatchdogType::PING) {
                    $this->SetTimerInterval(\HoymilesWiFi\IO\Timer::Watchdog, $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::WatchdogInterval) * 1000);
                }
                break;
        }
        parent::SetStatus($NewState);
        return true;
    }
    /**
     * Registriert eine Überwachung einer Variable.
     *
     * @param int $VarId IPS-ID der Variable.
     */
    protected function RegisterVariableWatch(int $VarId): void
    {
        if ($VarId < 9999) {
            return;
        }
        if (IPS_VariableExists($VarId)) {
            $this->SendDebug('RegisterVariableWatch', $VarId, 0);
            $this->RegisterMessage($VarId, VM_DELETE);
            $this->RegisterMessage($VarId, VM_UPDATE);
            $this->RegisterReference($VarId);
        }
    }
    private function FormUpdateByWatchdogType(int $WatchdogType): void
    {
        switch ($WatchdogType) {
            case \HoymilesWiFi\IO\WatchdogType::NONE:
                $this->UpdateFormField('TimePopup', 'visible', false);
                $this->UpdateFormField('ConditionPopup', 'visible', false);
                break;
            case \HoymilesWiFi\IO\WatchdogType::TIME_OR_VALUES:
                $this->UpdateFormField('TimePopup', 'visible', true);
                $this->UpdateFormField('ConditionPopup', 'visible', false);
                break;
            case \HoymilesWiFi\IO\WatchdogType::PING:
                $this->UpdateFormField('TimePopup', 'visible', false);
                $this->UpdateFormField('ConditionPopup', 'visible', true);
                $this->UpdateFormField('WatchdogCondition', 'visible', false);
                break;
            case \HoymilesWiFi\IO\WatchdogType::CONDITION:
                $this->UpdateFormField('TimePopup', 'visible', false);
                $this->UpdateFormField('ConditionPopup', 'visible', true);
                $this->UpdateFormField('WatchdogCondition', 'visible', true);
                break;

        }
    }
    private function FormUpdateByDayOrNightVariable(int $VariableId, string $Property): void
    {
        if ($VariableId < 10000) {
            $this->UpdateFormField($Property, 'variableID', 1);
            $this->UpdateFormField($Property, 'visible', false);
            $this->UpdateFormField($Property, 'value', '""');
            return;
        }
        if (!IPS_VariableExists($VariableId)) {
            $this->UpdateFormField($Property, 'variableID', 1);
            $this->UpdateFormField($Property, 'visible', false);
            $this->UpdateFormField($Property, 'value', '""');
            return;
        }
        switch (IPS_GetVariable($VariableId)['VariableType']) {
            case VARIABLETYPE_INTEGER:
                if ($this->VariableIsTimestamp($VariableId)) {
                    $this->UpdateFormField($Property, 'variableID', 1);
                    $this->UpdateFormField($Property, 'visible', false);
                    $this->UpdateFormField($Property, 'value', '""');
                } else {
                    $this->UpdateFormField($Property, 'variableID', $VariableId);
                    $this->UpdateFormField($Property, 'visible', true);
                }
                break;
            default:
                $this->UpdateFormField($Property, 'variableID', $VariableId);
                $this->UpdateFormField($Property, 'visible', true);
                break;
        }
    }

    private function FormUpdateBySelectLocationControl(int $LocationId): void
    {
        if ($LocationId < 10000) {
            $this->UpdateFormField('StartVariableId', 'value', 0);
            $this->UpdateFormField('StopVariableId', 'value', 0);
            return;
        }
        if (!IPS_InstanceExists($LocationId)) {
            $this->UpdateFormField('StartVariableId', 'value', 0);
            $this->UpdateFormField('StopVariableId', 'value', 0);
            return;
        }
        if (IPS_GetInstance($LocationId)['ModuleInfo']['ModuleID'] != \HoymilesWiFi\GUID::LocationControl) {
            $this->UpdateFormField('StartVariableId', 'value', 0);
            $this->UpdateFormField('StopVariableId', 'value', 0);
            return;
        }
        $this->UpdateFormField('StartVariableId', 'value', IPS_GetObjectIDByIdent('Sunrise', $LocationId));
        $this->UpdateFormField('DayValue', 'visible', false);
        $this->UpdateFormField('DayValue', 'value', '""');
        $this->UpdateFormField('StopVariableId', 'value', IPS_GetObjectIDByIdent('Sunset', $LocationId));
        $this->UpdateFormField('NightValue', 'visible', false);
        $this->UpdateFormField('NightValue', 'value', '""');
    }

    private function StartWithLastStateCheck()
    {
        $this->SendDebug(__FUNCTION__, 'LastState: ' . $this->ReadAttributeInteger(\HoymilesWiFi\IO\Attribute::LastState), 0);
        if ($this->ReadAttributeInteger(\HoymilesWiFi\IO\Attribute::LastState) != IS_INACTIVE) {
            $this->SetActive();
        }
    }
    private function CheckCondition(): bool
    {
        switch ($this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::WatchdogType)) {
            case \HoymilesWiFi\IO\WatchdogType::PING:
                $Result = @Sys_Ping($this->ReadPropertyString(\HoymilesWiFi\IO\Property::Host), 500);
                $this->SendDebug('Pinging', $Result, 0);
                return $Result;
            case \HoymilesWiFi\IO\WatchdogType::CONDITION:
                $Result = IPS_IsConditionPassing($this->ReadPropertyString(\HoymilesWiFi\IO\Property::WatchdogCondition));
                $this->SendDebug('CheckCondition', $Result, 0);
                return $Result;
        }
        return true;
    }

    private function DayNightCheck(mixed $ValueDay, mixed $ValueNight): void
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $this->SendDebug('ValueDay', $ValueDay, 0);
        $this->SendDebug('ValueNight', $ValueNight, 0);
        $this->SendDebug(__FUNCTION__, 'actual Timestamp:' . time(), 0);
        if ((int) $ValueDay > (time() - 2)) {
            $this->SendDebug('ValueDay is greater', '', 0);
            if ((int) $ValueDay > (int) $ValueNight) { // Und ValueNight liegt vor (nächsten) Tag
                $this->SendDebug('ValueNight is smaller then ValueDay', '', 0);
                $this->SetActive();
                return;
            }
        }
        $this->SetInactive();
    }

    private function DayCheck(mixed $Value): bool
    {
        $this->SendDebug(__FUNCTION__, $Value, 0);
        $TargetValue = json_decode($this->ReadPropertyString(\HoymilesWiFi\IO\Property::DayValue));
        $this->SendDebug(__FUNCTION__, 'TargetValue:' . $TargetValue, 0);
        if ($Value == $TargetValue) {
            $this->SetActive();
            return true;
        }
        return false;
    }
    private function NightCheck(mixed $Value): void
    {
        $this->SendDebug(__FUNCTION__, $Value, 0);
        $TargetValue = json_decode($this->ReadPropertyString(\HoymilesWiFi\IO\Property::NightValue));
        $this->SendDebug(__FUNCTION__, 'TargetValue:' . $TargetValue, 0);
        if ($Value == $TargetValue) {
            $this->SetInactive();
        }
    }

    private function VariableIsTimestamp(int $VariableId): bool
    {
        $Variable = IPS_GetVariable($VariableId);
        if (isset($Variable['VariableCustomPresentation']['PRESENTATION'])) {
            if ($Variable['VariableCustomPresentation']['PRESENTATION'] == VARIABLE_PRESENTATION_DATE_TIME) {
                return true;
            }
            if ($Variable['VariableCustomPresentation']['PRESENTATION'] == VARIABLE_PRESENTATION_LEGACY) {
                if ($Variable['VariableCustomPresentation']['PROFILE'] == '~UnixTimestamp') {
                    return true;
                }
            }
        }

        if (isset($Variable['VariablePresentation']['PRESENTATION'])) {
            if ($Variable['VariablePresentation']['PRESENTATION'] == VARIABLE_PRESENTATION_DATE_TIME) {
                return true;
            }
            if ($Variable['VariablePresentation']['PRESENTATION'] == VARIABLE_PRESENTATION_LEGACY) {
                $Variable['VariablePresentation']['PROFILE'] = $Variable['VariablePresentation']['PROFILE'] ?? '';
                if ($Variable['VariablePresentation']['PROFILE'] == '~UnixTimestamp') {
                    return true;
                }
            }
        }

        return false;
    }
    private function RealDataResDTO(): bool
    {
        $Request = new \Hoymiles\RealDataResDTO();
        $Request->setYmdHms(date('Y-m-d H:i:s', time()));
        $Request->setTime(time());
        $Request->setOft(28800);
        $RequestBytes = $Request->serializeToString();
        $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::RealDataResDTO, $RequestBytes);
        if (!$ResultStream) {
            return false;
        }

        $Result = new \Hoymiles\RealDataReqDTO();
        $Result->mergeFromString($ResultStream);

        $DTU = json_encode([
            'sn'            => $Result->getSn(),
            'time'          => $Result->getTime(),
            'pvCurrentPower'=> $Result->getPvCurrentPower(),
            'pvDailyYield'  => $Result->getPvDailyYield()
        ]);
        $this->SendDebug('DTU', $DTU, 0);
        $this->SendDataToChildren(
            json_encode(
                [
                    'DataID'     => \HoymilesWiFi\GUID::IoToDTU,
                    'Data'       => $DTU
                ]
            )
        );
        /** @var \Hoymiles\InverterState[] $Inverters */
        $Inverters = $Result->getInverterState();
        /** @var \Hoymiles\PortState[] $SolarPorts */
        $SolarPorts = $Result->getPortState();

        $this->NbrOfInverter = count($Inverters);
        $this->NbrOfSolarPort = count($SolarPorts);

        // Zuordnung Nummer -> Seriennummer für AppInfo und Befehle an einzelne Wechselrichter
        $InverterSerials = [];
        foreach ($Inverters as $Inverter) {
            $InverterSerials[$Inverter->getVer()] = (string) $Inverter->getSn();
        }
        $this->InverterSerials = $InverterSerials;

        foreach ($Inverters as $Inverter) {
            $this->SendDebug('Inverter:' . $Inverter->getVer(), $Inverter->serializeToJsonString(\Google\Protobuf\PrintOptions::EMIT_DEFAULTS), 0);
            $this->SendDataToChildren(
                json_encode(
                    [
                        'DataID'     => \HoymilesWiFi\GUID::IoToInverter,
                        'Data'       => $Inverter->serializeToJsonString(\Google\Protobuf\PrintOptions::EMIT_DEFAULTS)
                    ]
                )
            );
        }
        foreach ($SolarPorts as $SolarPort) {
            $this->SendDebug('Solar:' . $SolarPort->getPi(), $SolarPort->serializeToJsonString(\Google\Protobuf\PrintOptions::EMIT_DEFAULTS), 0);
            $this->SendDataToChildren(
                json_encode(
                    [
                        'DataID'     => \HoymilesWiFi\GUID::IoToSolarPort,
                        'Data'       => $SolarPort->serializeToJsonString(\Google\Protobuf\PrintOptions::EMIT_DEFAULTS)
                    ]
                )
            );
        }
        if ((time() - (int) $this->LastAppInfo) >= \Hoymiles\DTU\AppInfo::Interval) {
            $AppInfo = $this->RequestAppInfo();
            if ($AppInfo) {
                $this->ForwardAppInfo($AppInfo);
            }
        }
        $WarnNumbers = [];
        foreach ($Inverters as $Inverter) {
            $WarnNumbers[$Inverter->getVer()] = $Inverter->getWnum();
        }
        $this->CheckWarnings($WarnNumbers);
        return true;
    }

    /**
     * Stößt bei geänderter Anzahl Warnungen (oder zyklisch) die Abfrage der Warnliste an
     * und holt die Liste beim nächsten Abruf ab.
     *
     * @param array $WarnNumbers Nummer des Wechselrichters => wnum
     * @return void
     */
    private function CheckWarnings(array $WarnNumbers): void
    {
        $Now = time();
        if ($this->WarnTrigger > 0) {
            if (($Now - $this->WarnTrigger) < \Hoymiles\DTU\WarnData::TriggerDelay) {
                return;
            }
            $this->WarnTrigger = 0;
            $Warnings = $this->RequestWarnData();
            if ($Warnings !== false) {
                $this->ForwardWarnings($Warnings);
            }
            return;
        }
        if (($WarnNumbers == $this->WarnNumbers) && (($Now - $this->LastWarnData) < \Hoymiles\DTU\WarnData::Interval)) {
            return;
        }
        // Auch bei Fehlern erst nach Interval oder neuer Warnung erneut versuchen
        $this->WarnNumbers = $WarnNumbers;
        $this->LastWarnData = $Now;
        $Request = new \Hoymiles\CommandResDTO();
        $Request->setTime($Now);
        $Request->setTid($Now);
        $Request->setPackageNub(1);
        $Request->setAction(\HoymilesWiFi\Inverter\Actions::ALARM_LIST);
        $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::CommandResDTO, $Request->serializeToString(), true, true);
        if ($ResultStream === false) {
            return;
        }
        $Result = new \Hoymiles\CommandReqDTO();
        $Result->mergeFromString($ResultStream);
        $this->SendDebug('AlarmList Result', $Result->serializeToJsonString(\Google\Protobuf\PrintOptions::EMIT_DEFAULTS), 0);
        $this->WarnTrigger = $Now;
    }

    /**
     * Holt alle Seiten der Warnliste ab.
     *
     * @return array|false Liste der Warnungen
     */
    private function RequestWarnData(): array|false
    {
        $Warnings = [];
        $Package = 0;
        do {
            $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::WarnResDTO, \Hoymiles\DTU\WarnData::BuildRequest(time(), $Package), true, true);
            if ($ResultStream === false) {
                return false;
            }
            $Result = \Hoymiles\DTU\WarnData::ParseResponse($ResultStream);
            if ($Result === false) {
                $this->SendDebug('WarnData', 'Invalid data', 0);
                return false;
            }
            $this->SendDebug('WarnData', 'Package: ' . ($Result['PackageNow'] + 1) . '/' . $Result['PackageCount'] . ' WarnDevice: ' . $Result['WarnDevice'] . ' Entries: ' . count($Result['Warnings']), 0);
            foreach ($Result['Warnings'] as $Warning) {
                $this->SendDebug('Warning', json_encode($Warning), 0);
            }
            $Warnings = array_merge($Warnings, $Result['Warnings']);
            $Package++;
        } while (($Package < $Result['PackageCount']) && ($Package < \Hoymiles\DTU\WarnData::MaxPackages));
        return $Warnings;
    }

    /**
     * Verteilt die Warnliste an die Inverter-Instanzen.
     *
     * @param array $Warnings
     * @return void
     */
    private function ForwardWarnings(array $Warnings): void
    {
        $InverterSerials = $this->InverterSerials ?: [];
        foreach ($InverterSerials as $Number => $SerialNumber) {
            $InverterWarnings = array_values(array_filter($Warnings, function ($Warning) use ($SerialNumber)
            {
                return $Warning['SerialNumber'] === $SerialNumber;
            }));
            // "ver" muss vor weiteren Feldern stehen, die Inverter-Instanz filtert auf "ver":Nummer,
            $Inverter = json_encode([
                'sn'                                          => $SerialNumber,
                'ver'                                         => $Number,
                \HoymilesWiFi\Inverter\Variables::WarningList => $InverterWarnings
            ]);
            $this->SendDebug('Inverter Warnings:' . $Number, $Inverter, 0);
            $this->SendDataToChildren(
                json_encode(
                    [
                        'DataID'     => \HoymilesWiFi\GUID::IoToInverter,
                        'Data'       => $Inverter
                    ]
                )
            );
        }
    }

    /**
     * Fragt APPInfoData ab.
     *
     * @return array|false|null Daten, false bei Übertragungsfehler, null bei unbekanntem Format
     */
    private function RequestAppInfo(): array|false|null
    {
        $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::InfoDataResDTO, \Hoymiles\DTU\AppInfo::BuildRequest(time()));
        if ($ResultStream === false) {
            return false;
        }
        $this->LastAppInfo = time();
        $AppInfo = \Hoymiles\DTU\AppInfo::ParseResponse($ResultStream);
        if ($AppInfo === false) {
            $this->SendDebug('AppInfo', 'Invalid data', 0);
            return null;
        }
        $DebugInfo = $AppInfo;
        // Schlüsselmaterial nicht im Debug ausgeben
        $DebugInfo['EncRand'] = $AppInfo['EncRand'] === '' ? '' : '*** (' . strlen($AppInfo['EncRand']) . ' bytes)';
        $DebugInfo['Dfs'] = sprintf('0x%X', $AppInfo['Dfs']);
        $DebugInfo['PvInfo'] = json_encode($AppInfo['PvInfo']);
        $this->SendDebug('AppInfo', $DebugInfo, 0);
        return $AppInfo;
    }

    /**
     * Sendet Signalstärke und Versionen an DTU- und Inverter-Instanzen.
     *
     * @param array $AppInfo
     * @return void
     */
    private function ForwardAppInfo(array $AppInfo): void
    {
        $DTU = json_encode([
            \HoymilesWiFi\DTU\Variables::SignalStrength  => $AppInfo['SignalStrength'],
            \HoymilesWiFi\DTU\Variables::SoftwareVersion => 'V' . \Hoymiles\DTU\AppInfo::FormatDtuVersion($AppInfo['DtuSwVersion']),
            \HoymilesWiFi\DTU\Variables::HardwareVersion => 'H' . \Hoymiles\DTU\AppInfo::FormatDtuVersion($AppInfo['DtuHwVersion']),
            \HoymilesWiFi\DTU\Variables::WifiVersion     => $AppInfo['WifiVersion']
        ]);
        $this->SendDebug('DTU Info', $DTU, 0);
        $this->SendDataToChildren(
            json_encode(
                [
                    'DataID'     => \HoymilesWiFi\GUID::IoToDTU,
                    'Data'       => $DTU
                ]
            )
        );
        $InverterSerials = $this->InverterSerials ?: [];
        foreach ($AppInfo['PvInfo'] as $PvInfo) {
            $Number = array_search($PvInfo['SerialNumber'], $InverterSerials, true);
            if ($Number === false) {
                // Wechselrichter noch nicht aus RealData bekannt, beim nächsten Abruf erneut versuchen
                $this->SendDebug('Inverter Info', 'Unknown serial number ' . $PvInfo['SerialNumber'], 0);
                $this->LastAppInfo = 0;
                continue;
            }
            // "ver" muss vor weiteren Feldern stehen, die Inverter-Instanz filtert auf "ver":Nummer,
            $Inverter = json_encode([
                'sn'                                              => $PvInfo['SerialNumber'],
                'ver'                                             => $Number,
                \HoymilesWiFi\Inverter\Variables::SoftwareVersion => 'V' . \Hoymiles\DTU\AppInfo::FormatInverterSwVersion($PvInfo['SwVersion']),
                \HoymilesWiFi\Inverter\Variables::HardwareVersion => 'H' . \Hoymiles\DTU\AppInfo::FormatInverterHwVersion($PvInfo['HwVersion'])
            ]);
            $this->SendDebug('Inverter Info:' . $Number, $Inverter, 0);
            $this->SendDataToChildren(
                json_encode(
                    [
                        'DataID'     => \HoymilesWiFi\GUID::IoToInverter,
                        'Data'       => $Inverter
                    ]
                )
            );
        }
    }

    /**
     * Sendet einen Befehl per CloudCommandResDTO (wie hoymiles-wifi).
     *
     * @param \Hoymiles\CommandResDTO $Request
     * @param string $DebugName
     * @return bool true wenn die DTU den Befehl ohne Fehler bestätigt
     */
    private function SendCloudCommand(\Hoymiles\CommandResDTO $Request, string $DebugName): bool
    {
        $ResultStream = $this->SendCommand(\Hoymiles\DTU\Commands::CloudCommandResDTO, $Request->serializeToString());
        if ($ResultStream === false) {
            return false;
        }
        $Result = new \Hoymiles\CommandReqDTO();
        $Result->mergeFromString($ResultStream);
        $this->SendDebug($DebugName . ' Result', $Result->serializeToJsonString(\Google\Protobuf\PrintOptions::EMIT_DEFAULTS), 0);
        if ($Result->getErrCode() != 0) {
            trigger_error($this->Translate('DTU rejected the command. Error code: ') . $Result->getErrCode(), E_USER_NOTICE);
            return false;
        }
        return true;
    }

    /**
     * Fragt APPInfoData ab und ermittelt, ob die DTU verschlüsselt kommuniziert.
     *
     * @return bool true wenn der Zustand ermittelt wurde
     */
    private function CheckEncryption(): bool
    {
        $this->SendDebug(__FUNCTION__, 'Request AppInfo', 0);
        $AppInfo = $this->RequestAppInfo();
        if ($AppInfo === false) {
            return false;
        }
        if ($AppInfo === null) {
            // Unbekanntes Format, dann wie bisher unverschlüsselt arbeiten. Eine verschlüsselte Antwort führt später zur erneuten Prüfung.
            $this->SendDebug(__FUNCTION__, 'Invalid AppInfo, assume DTU is not encrypted', 0);
            $this->EncRand = '';
            $this->EncryptionChecked = true;
            return true;
        }
        // Versionen und Signalstärke beim nächsten RealData weiterleiten, dann sind die Seriennummern bekannt
        $this->LastAppInfo = 0;
        if (!\Hoymiles\DTU\Encryption::IsEncryptedDtu($AppInfo['Dfs'])) {
            $this->SendDebug(__FUNCTION__, 'DTU is not encrypted', 0);
            $this->EncRand = '';
            $this->EncryptionChecked = true;
            return true;
        }
        $this->SendDebug(__FUNCTION__, 'DTU is encrypted', 0);
        if (strlen($AppInfo['EncRand']) != \Hoymiles\DTU\Encryption::EncRandLength) {
            trigger_error($this->Translate('DTU uses encryption, but no valid key was received.'), E_USER_NOTICE);
            return false;
        }
        if (!\Hoymiles\DTU\Encryption::IsAvailable()) {
            trigger_error($this->Translate('DTU uses encryption, but PHP does not support AES-128-GCM.'), E_USER_NOTICE);
            return false;
        }
        $this->EncRand = $AppInfo['EncRand'];
        $this->EncryptionChecked = true;
        return true;
    }

    private function SendCommand(int $Command, string $RequestBytes, bool $AllowRetry = true, bool $Quiet = false): false|string
    {
        $TriggerError = !$this->ReadPropertyBoolean(\HoymilesWiFi\IO\Property::SuppressConnectionError);
        $EncryptCommand = \Hoymiles\DTU\Encryption::IsEncryptedCommand($Command);
        if ($EncryptCommand && !$this->EncryptionChecked) {
            if (!$this->CheckEncryption()) {
                return false;
            }
        }
        $EncRand = $EncryptCommand ? $this->EncRand : '';
        $PlainRequestBytes = $RequestBytes;
        $this->SendDebug('SendCommand', pack('n', $Command), 1);
        $this->SendDebug('RequestBytes', $RequestBytes, 1);
        $this->lock(\HoymilesWiFi\IO\Locks::SendSequenz);
        $Sequenz = ($this->Sequenz + 1) & 0xFFFF;
        $this->Sequenz = $Sequenz;
        $this->unlock(\HoymilesWiFi\IO\Locks::SendSequenz);
        $this->SendDebug('SendSequenz', pack('n', $Sequenz), 1);
        if ($EncRand !== '') {
            $RequestBytes = \Hoymiles\DTU\Encryption::Encrypt($EncRand, $Command, $Sequenz, $RequestBytes);
            if ($RequestBytes === false) {
                $this->DataError($this->Translate('Error on encrypt data.'), $Quiet);
                return false;
            }
            $this->SendDebug('RequestBytes encrypted', $RequestBytes, 1);
            // CRC und Länge im Header ohne Auth-Tag
            $CRC16 = pack('n', $this->CRC16(substr($RequestBytes, 0, -\Hoymiles\DTU\Encryption::TagLength)));
            $Len = strlen($RequestBytes) - \Hoymiles\DTU\Encryption::TagLength + 10;
        } else {
            $CRC16 = pack('n', $this->CRC16($RequestBytes));
            $Len = strlen($RequestBytes) + 10;
        }
        $Content = \Hoymiles\DTU\SendStream::Header . pack('n', $Command) . pack('n', $Sequenz) . $CRC16 . pack('n', $Len) . $RequestBytes;
        $DeviceAddress = 'tcp://' . $this->ReadPropertyString(\HoymilesWiFi\IO\Property::Host) . ':' . $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::Port);
        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client($DeviceAddress, $errno, $errstr, 5);
        if (!$fp) {
            $this->SendDebug('ERROR (' . $errno . ')', $errstr, 0);
            $this->ConnectionError($this->Translate('Error on connect') . '(' . $errno . ') ' . $errstr, $TriggerError, $Quiet);
            return false;
        } else {
            $this->SendDebug('Send', $Content, 1);
            for ($fwrite = 0, $written = 0, $max = strlen($Content); $written < $max; $written += $fwrite) {
                $fwrite = @fwrite($fp, substr($Content, $written));
                if ($fwrite === false) {
                    $this->SendDebug('ERROR on write (' . $errno . ')', $errstr, 0);
                    @fclose($fp);
                    $this->ConnectionError($this->Translate('Error on write') . '(' . $errno . ') ' . $errstr, $TriggerError, $Quiet);
                    return false;
                }
            }
            $Data = $this->ReadFrame($fp, $EncRand !== '');
            fclose($fp);
        }
        if (!$Data) {
            $this->SendDebug('ERROR (0)', 'Timeout', 0);
            $this->ConnectionError($this->Translate('Timeout'), $TriggerError, $Quiet);
            return false;
        }
        if (strlen($Data) < 10) {
            $this->SendDebug('Recv', $Data, 1);
            $this->DataError($this->Translate('Data has wrong length.'), $Quiet);
            return false;
        }
        $Header = substr($Data, 0, 10);
        $Payload = substr($Data, 10);
        $RecvCommand = unpack('n', substr($Header, 2, 2))[1];
        $RecvSequenz = unpack('n', substr($Header, 4, 2))[1];
        $Len = unpack('n', substr($Header, 8, 2))[1];
        $EncryptedResponse = ($EncRand !== '') && \Hoymiles\DTU\Encryption::IsEncryptedCommand($RecvCommand);
        $ExpectedLength = $Len + ($EncryptedResponse ? \Hoymiles\DTU\Encryption::TagLength : 0);
        // Antworten mit Schlüsselmaterial nur ohne Nutzdaten ausgeben
        $MaskPayload = in_array($RecvCommand, \Hoymiles\DTU\Encryption::SensitiveResponses, true);
        $this->SendDebug('Recv', $MaskPayload ? $Header : $Data, 1);
        $this->SendDebug('Recv Command', substr($Header, 2, 2), 1);
        $this->SendDebug('Recv Sequenz', $RecvSequenz, 0);
        $this->SendDebug('Recv Length', 'Header: ' . $Len . ' Expected: ' . $ExpectedLength . ' Received: ' . strlen($Data) . ' Encrypted: ' . ($EncryptedResponse ? 'yes' : 'no'), 0);
        $this->SendDebug('Recv Payload', $MaskPayload ? '*** masked ***' : $Payload, $MaskPayload ? 0 : 1);
        if (strlen($Data) != $ExpectedLength) {
            // Antwort ist verschlüsselt, obwohl die DTU bisher als unverschlüsselt erkannt wurde (z.B. nach Firmware-Update)
            if (($EncRand === '') && $EncryptCommand && (strlen($Data) == $Len + \Hoymiles\DTU\Encryption::TagLength)) {
                $this->SendDebug('Encryption', 'Response looks encrypted, check encryption again', 0);
                $this->EncryptionChecked = false;
                if ($AllowRetry && $this->CheckEncryption() && ($this->EncRand !== '')) {
                    return $this->SendCommand($Command, $PlainRequestBytes, false, $Quiet);
                }
            }
            $this->DataError($this->Translate('Data has wrong length.'), $Quiet);
            return false;
        }
        // CRC immer ohne Auth-Tag
        $CRC16 = pack('n', $this->CRC16(substr($Data, 10, $Len - 10)));
        if ($CRC16 != substr($Header, 6, 2)) {
            $this->DataError($this->Translate('Invalid checksum.'), $Quiet);
            return false;
        }
        if ($EncryptedResponse) {
            $Payload = \Hoymiles\DTU\Encryption::Decrypt($EncRand, $RecvCommand, $RecvSequenz, $Payload);
            if ($Payload === false) {
                $this->SendDebug('Decrypt', 'failed', 0);
                // Schlüssel könnte sich geändert haben, beim nächsten Request neu ermitteln
                $this->EncryptionChecked = false;
                $this->DataError($this->Translate('Error on decrypt data.'), $Quiet);
                return false;
            }
            $this->SendDebug('Recv Payload decrypted', $Payload, 1);
        }
        return $Payload;
    }

    /**
     * Verbindungsfehler melden und Status setzen.
     * Bei $Quiet (optionale Abfragen wie die Warnliste) nur Debug, kein Statuswechsel.
     *
     * @param string $Message
     * @param bool $TriggerError
     * @param bool $Quiet
     * @return void
     */
    private function ConnectionError(string $Message, bool $TriggerError, bool $Quiet): void
    {
        if ($Quiet) {
            $this->SendDebug('ERROR (quiet)', $Message, 0);
            return;
        }
        if ($TriggerError) {
            trigger_error($Message, E_USER_NOTICE);
        }
        if ($this->CheckCondition()) {
            $this->SetStatus(IS_EBASE + 2);
        } else {
            $this->SetInactive();
        }
    }

    /**
     * Fehler in den empfangenen Daten melden, bei $Quiet nur im Debug.
     *
     * @param string $Message
     * @param bool $Quiet
     * @return void
     */
    private function DataError(string $Message, bool $Quiet): void
    {
        if ($Quiet) {
            $this->SendDebug('ERROR (quiet)', $Message, 0);
            return;
        }
        trigger_error($Message, E_USER_NOTICE);
    }

    /**
     * Liest einen kompletten Frame anhand der Länge im Header.
     *
     * @param resource $fp
     * @param bool $Encrypted true wenn ein Auth-Tag erwartet wird
     * @return string Empfangene Daten (ggf. unvollständig)
     */
    private function ReadFrame($fp, bool $Encrypted): string
    {
        stream_set_timeout($fp, 5);
        $Data = '';
        $Expected = 10;
        $HeaderParsed = false;
        while (strlen($Data) < $Expected) {
            $Chunk = @fread($fp, 8192);
            if (($Chunk === false) || ($Chunk === '')) {
                break;
            }
            $Data .= $Chunk;
            if (!$HeaderParsed && (strlen($Data) >= 10)) {
                $HeaderParsed = true;
                $RecvCommand = unpack('n', substr($Data, 2, 2))[1];
                $Expected = unpack('n', substr($Data, 8, 2))[1];
                if ($Encrypted && \Hoymiles\DTU\Encryption::IsEncryptedCommand($RecvCommand)) {
                    $Expected += \Hoymiles\DTU\Encryption::TagLength;
                }
            }
        }
        if (!$Encrypted && $HeaderParsed && !feof($fp)) {
            // Nicht angekündigte Restdaten (z.B. Auth-Tag einer unerwartet verschlüsselten Antwort) noch abholen
            stream_set_timeout($fp, 0, 200000);
            $Chunk = @fread($fp, 8192);
            if (is_string($Chunk)) {
                $Data .= $Chunk;
            }
        }
        return $Data;
    }

    private function CRC16(string $string): int
    {
        $crc = 0xffff;
        $polynom = 0x8005;
        for ($i = 0; $i < strlen($string); $i++) {
            $c = ord(self::reverseChar($string[$i]));
            $crc ^= ($c << 8);
            for ($j = 0; $j < 8; ++$j) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) & 0xffff) ^ $polynom;
                } else {
                    $crc = ($crc << 1) & 0xffff;
                }
            }
        }
        $ret = pack('cc', $crc & 0xff, ($crc >> 8) & 0xff);
        $ret = self::reverseString($ret);
        $arr = unpack('vshort', $ret);
        $crc = $arr['short'];
        return $crc;
    }

    private static function reverseString($str)
    {
        $m = 0;
        $n = strlen($str) - 1;
        while ($m <= $n) {
            if ($m == $n) {
                $str[$m] = self::reverseChar($str[$m]);
                break;
            }
            $ord1 = self::reverseChar($str[$m]);
            $ord2 = self::reverseChar($str[$n]);
            $str[$m] = $ord2;
            $str[$n] = $ord1;
            $m++;
            $n--;
        }
        return $str;
    }

    private static function reverseChar($char)
    {
        $byte = ord($char);
        $tmp = 0;
        for ($i = 0; $i < 8; ++$i) {
            if ($byte & (1 << $i)) {
                $tmp |= (1 << (7 - $i));
            }
        }
        return chr($tmp);
    }
    /**
     * Desregistriert eine Überwachung einer Variable.
     *
     * @param int $VarId IPS-ID der Variable.
     */
    private function UnregisterVariableWatch(int $VarId): void
    {
        if ($VarId < 9999) {
            return;
        }
        $this->SendDebug('UnregisterVariableWatch', $VarId, 0);
        $this->UnregisterMessage($VarId, VM_DELETE);
        $this->UnregisterMessage($VarId, VM_UPDATE);
        $this->UnregisterReference($VarId);
    }
}
