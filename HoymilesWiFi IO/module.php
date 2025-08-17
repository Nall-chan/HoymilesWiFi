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

    public function Create(): void
    {
        parent::Create();
        $this->Sequenz = 0;
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
        $this->RegisterPropertyInteger(\HoymilesWiFi\IO\Property::LocationId, 1);
        $this->RegisterPropertyInteger(\HoymilesWiFi\IO\Property::StartVariableId, 1);
        $this->RegisterPropertyInteger(\HoymilesWiFi\IO\Property::StopVariableId, 1);
        $this->RegisterPropertyString(\HoymilesWiFi\IO\Property::DayValue, '""');
        $this->RegisterPropertyString(\HoymilesWiFi\IO\Property::NightValue, '""');
        $this->RegisterAttributeInteger(\HoymilesWiFi\IO\Attribute::LastState, IS_CREATING);
        $this->RegisterTimer(\HoymilesWiFi\IO\Timer::RequestState, 0, 'IPS_RequestAction(' . $this->InstanceID . ',"' . \HoymilesWiFi\IO\Timer::RequestState . '",true);');
    }

    public function Destroy(): void
    {
        parent::Destroy();
    }

    public function ApplyChanges(): void
    {
        $this->UnregisterVariableWatch($this->DayVariableId);
        $this->UnregisterVariableWatch($this->NightVariableId);
        $this->Sequenz = 0;
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
        if ($this->ReadPropertyBoolean(\HoymilesWiFi\IO\Property::Active)) {
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
            } else {
                $this->StartWithLastStateCheck();
            }
        } else {
            $this->SetStatus(IS_INACTIVE);
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
        }
        return true;
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
            case \HoymilesWiFi\IO\Property::LocationId:
                $this->UpdateNightObjectForm($Value);
                return;
            case \HoymilesWiFi\IO\Property::DayValue:
            case \HoymilesWiFi\IO\Property::NightValue:
                $this->UpdateDayNightVariables($Value, $Ident);
                return;
        }
    }

    public function GetConfigurationForm(): string
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->GetStatus() == IS_CREATING) {
            return json_encode($Form);
        }

        $StartVariableId = $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::StartVariableId);
        if ($StartVariableId < 10000) {
            $StartVariableId = false;
        } else {
            if (IPS_VariableExists($StartVariableId)) {
                if ($this->VariableIsTimestamp($StartVariableId)) {
                    $StartVariableId = false;
                }
            } else {
                $StartVariableId = false;
            }
        }
        if ($StartVariableId) {
            $Form['elements'][3]['items'][1]['variableID'] = $StartVariableId;
        } else {
            $Form['elements'][3]['items'][1]['variableID'] = 1;
            $Form['elements'][3]['items'][1]['value'] = '""';
            $Form['elements'][3]['items'][1]['visible'] = false;
        }

        $StopVariableId = $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::StopVariableId);

        if ($StopVariableId < 10000) {
            $StopVariableId = false;
        } else {
            if (IPS_VariableExists($StopVariableId)) {
                if ($this->VariableIsTimestamp($StopVariableId)) {
                    $StopVariableId = false;
                }
            } else {
                $StopVariableId = false;
            }
        }

        if ($StopVariableId) {
            $Form['elements'][4]['items'][1]['variableID'] = $StopVariableId;
        } else {
            $Form['elements'][4]['items'][1]['variableID'] = 1;
            $Form['elements'][4]['items'][1]['value'] = '""';
            $Form['elements'][4]['items'][1]['visible'] = false;
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
                $this->SendDebug('StartInverter Result', $Result->serializeToJsonString(), 0);
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
                $this->SendDebug('Stop Result', $Result->serializeToJsonString(), 0);
                return serialize($Result->getErrCode() == 0);
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
        $Json = $Result->serializeToJsonString();
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

    private function UpdateDayNightVariables(int $VariableId, string $Property): void
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

    private function UpdateNightObjectForm(int $LocationId): void
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
        $this->UpdateFormField('StopVariableId', 'value', IPS_GetObjectIDByIdent('Sunset', $LocationId));
    }

    private function StartWithLastStateCheck()
    {
        $this->SendDebug(__FUNCTION__, 'LastState: ' . $this->ReadAttributeInteger(\HoymilesWiFi\IO\Attribute::LastState), 0);
        if ($this->ReadAttributeInteger(\HoymilesWiFi\IO\Attribute::LastState) != IS_INACTIVE) {
            $this->SetActive();
        }
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
        $Variable = IPS_GetVariable($this->NightVariableId);
        if ($Variable['VariablePresentation']['PRESENTATION'] == VARIABLE_PRESENTATION_LEGACY) {
            if ($Variable['VariablePresentation']['PROFILE'] == '~UnixTimestamp') {
                return true;
            }
        }
        if ($Variable['VariableCustomPresentation']['PRESENTATION'] == VARIABLE_PRESENTATION_LEGACY) {
            if ($Variable['VariableCustomPresentation']['PROFILE'] == '~UnixTimestamp') {
                return true;
            }
        }
        if ($Variable['VariablePresentation']['PRESENTATION'] == VARIABLE_PRESENTATION_DATE_TIME) {
            return true;
        }
        if ($Variable['VariableCustomPresentation']['PRESENTATION'] == VARIABLE_PRESENTATION_DATE_TIME) {
            return true;
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

        foreach ($Inverters as $Inverter) {
            $this->SendDebug('Inverter:' . $Inverter->getVer(), $Inverter->serializeToJsonString(), 0);
            $this->SendDataToChildren(
                json_encode(
                    [
                        'DataID'     => \HoymilesWiFi\GUID::IoToInverter,
                        'Data'       => $Inverter->serializeToJsonString()
                    ]
                )
            );
        }
        foreach ($SolarPorts as $SolarPort) {
            $this->SendDebug('Solar:' . $SolarPort->getPi(), $SolarPort->serializeToJsonString(), 0);
            $this->SendDataToChildren(
                json_encode(
                    [
                        'DataID'     => \HoymilesWiFi\GUID::IoToSolarPort,
                        'Data'       => $SolarPort->serializeToJsonString()
                    ]
                )
            );
        }
        return true;
    }

    private function SendCommand(int $Command, string $RequestBytes): false|string
    {
        $TriggerError = !$this->ReadPropertyBoolean(\HoymilesWiFi\IO\Property::SuppressConnectionError);
        $this->SendDebug('SendCommand', pack('n', $Command), 1);
        $this->SendDebug('RequestBytes', $RequestBytes, 1);
        $CRC16 = pack('n', $this->CRC16($RequestBytes));
        $Len = strlen($RequestBytes) + 10;
        $this->lock(\HoymilesWiFi\IO\Locks::SendSequenz);
        $Sequenz = ++$this->Sequenz;
        $this->SendDebug('SendSequenz', pack('n', $Sequenz), 1);
        $this->unlock(\HoymilesWiFi\IO\Locks::SendSequenz);
        $Content = \Hoymiles\DTU\SendStream::Header . pack('n', $Command) . pack('n', $Sequenz) . $CRC16 . pack('n', $Len) . $RequestBytes;
        $DeviceAddress = 'tcp://' . $this->ReadPropertyString(\HoymilesWiFi\IO\Property::Host) . ':' . $this->ReadPropertyInteger(\HoymilesWiFi\IO\Property::Port);
        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client($DeviceAddress, $errno, $errstr, 5);
        if (!$fp) {
            $this->SendDebug('ERROR (' . $errno . ')', $errstr, 0);
            if ($TriggerError) {
                trigger_error($this->Translate('Error on connect') . '(' . $errno . ') ' . $errstr, E_USER_NOTICE);
                $this->SetStatus(IS_EBASE + 2);
            }
            return false;
        } else {
            $this->SendDebug('Send', $Content, 1);
            for ($fwrite = 0, $written = 0, $max = strlen($Content); $written < $max; $written += $fwrite) {
                $fwrite = @fwrite($fp, substr($Content, $written));
                if ($fwrite === false) {
                    $this->SendDebug('ERROR on write (' . $errno . ')', $errstr, 0);
                    @fclose($fp);
                    if ($TriggerError) {
                        trigger_error($this->Translate('Error on write') . '(' . $errno . ') ' . $errstr, E_USER_NOTICE);
                        $this->SetStatus(IS_EBASE + 2);
                    }
                    return false;
                }
            }
            $Data = '';
            $Data = fread($fp, 8192);
            fclose($fp);
        }
        if (!$Data) {
            $this->SendDebug('ERROR (0)', 'Timeout', 0);
            if ($TriggerError) {
                trigger_error($this->Translate('Timeout'), E_USER_NOTICE);
                $this->SetStatus(IS_EBASE + 2);
            }
            return false;
        }
        $Header = substr($Data, 0, 10);
        $Payload = substr($Data, 10);
        $this->SendDebug('Recv Command', substr($Header, 2, 2), 1);
        $this->SendDebug('Recv Payload', $Payload, 1);
        $this->SendDebug('Recv Sequenz', unpack('n', substr($Header, 4, 2))[1], 0);
        $Len = unpack('n', substr($Header, 8, 2))[1];
        if ($Len != strlen($Payload) + 10) {
            trigger_error($this->Translate('Data has wrong length.'), E_USER_NOTICE);
            return false;
        }
        $CRC16 = pack('n', $this->CRC16($Payload));
        if ($CRC16 != substr($Header, 6, 2)) {
            trigger_error($this->Translate('Invalid checksum.'), E_USER_NOTICE);
            return false;
        }
        return $Payload;
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
