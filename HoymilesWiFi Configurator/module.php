<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/HoymilesWiFi.php';
eval('declare(strict_types=1);namespace HoymilesWiFiConfigurator {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/DebugHelper.php') . '}');

/**
 * HoymilesWiFiConfigurator
 *
 * @method bool SendDebug(string $Message, mixed $Data, int $Format)
 */
class HoymilesWiFiConfigurator extends IPSModuleStrict
{
    use \HoymilesWiFiConfigurator\DebugHelper;

    public function Create(): void
    {
        parent::Create();
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
        $this->SetReceiveDataFilter('.*"NOTHING".*');
    }

    public function GetConfigurationForm(): string
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->GetStatus() == IS_CREATING) {
            return json_encode($Form);
        }
        if (!$this->HasActiveParent() || (@IPS_GetInstance($this->InstanceID)['ConnectionID'] < 10000)) {
            $Form['actions'][1]['visible'] = true;
            $Form['actions'][1]['popup']['items'][0]['caption'] = 'Instance has no active parent.';
        }

        list($NbrOfInverter, $NbrOfSolarPort) = $this->GetDevicesFromDTU();

        $DTU = $this->GetDTUConfigValues();
        $Inverters = $this->GetDevicesConfigValues('Inverter', \HoymilesWiFi\GUID::Inverter, \HoymilesWiFi\Inverter\Property::Number, $NbrOfInverter);
        $SolarPorts = $this->GetDevicesConfigValues('Solar string', \HoymilesWiFi\GUID::SolarPort, \HoymilesWiFi\SolarPort\Property::Port, $NbrOfSolarPort);
        $Form['actions'][0]['values'] = array_merge($DTU, $Inverters, $SolarPorts);
        $this->SendDebug('FORM', json_encode($Form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);
        return json_encode($Form);
    }

    private function GetDevicesFromDTU(): array
    {
        if (!$this->HasActiveParent() || (@IPS_GetInstance($this->InstanceID)['ConnectionID'] < 10000)) {
            return [0, 0];
        }
        $ret = $this->SendDataToParent(json_encode([
            'DataID'   => \HoymilesWiFi\GUID::DeviceToIo,
            'Function' => 'ListDevices',
        ]));
        $Devices = unserialize($ret);
        $this->SendDebug('DTU Devices', $Devices, 0);
        return [
            $Devices[\HoymilesWiFi\ConfigArray::NbrOfInverter],
            $Devices[\HoymilesWiFi\ConfigArray::NbrOfSolarPort]
        ];
    }

    private function GetDTUConfigValues(): array
    {
        $FoundDevices = [];
        $IO = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $InstanceIDList = $this->GetInstanceList(\HoymilesWiFi\GUID::DTU, $IO);
        $this->SendDebug('DTU Instances', $InstanceIDList, 0);
        $InstanceIDDevice = (count($InstanceIDList) ? array_shift($InstanceIDList) : false);
        $Device = [
            'Type'        => 'DTU',
            'Index'       => ''
        ];
        if ($InstanceIDDevice !== false) {
            $Device['instanceID'] = $InstanceIDDevice;
            $Device['name'] = IPS_GetName($InstanceIDDevice);
            $Device['Location'] = stristr(IPS_GetLocation($InstanceIDDevice), IPS_GetName($InstanceIDDevice), true);
        } else {
            $Device['instanceID'] = 0;
            $Device['name'] = $this->Translate(IPS_GetModule(\HoymilesWiFi\GUID::DTU)['ModuleName']);
            $Device['Location'] = '';
        }
        $Device['create'] = [
            'moduleID'      => \HoymilesWiFi\GUID::DTU,
            'configuration' => new stdClass()
        ];
        $FoundDevices[] = $Device;

        foreach ($InstanceIDList as $InstanceID) {
            $FoundDevices[] = [
                'Type'        => 'DTU',
                'Index'       => '',
                'instanceID'  => $InstanceID,
                'name'        => IPS_GetName($InstanceID),
                'Location'    => stristr(IPS_GetLocation($InstanceID), IPS_GetName($InstanceID), true)
            ];
        }
        return $FoundDevices;
    }

    private function GetDevicesConfigValues(string $Name, string $GUID, string $PropertyName, int $Numbers): array
    {
        $FoundDevices = [];
        $IO = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $InstanceIDList = $this->GetInstanceList($GUID, $IO, $PropertyName);
        $this->SendDebug($Name . ' Instances', $InstanceIDList, 0);
        for ($i = 1; $i <= $Numbers; $i++) {
            $InstanceIDDevice = array_search($i, $InstanceIDList);
            $Device = [
                'Type'        => $Name,
                'Index'       => $i
            ];
            if ($InstanceIDDevice !== false) {
                $Device['instanceID'] = $InstanceIDDevice;
                $Device['name'] = IPS_GetName($InstanceIDDevice);
                $Device['Location'] = stristr(IPS_GetLocation($InstanceIDDevice), IPS_GetName($InstanceIDDevice), true);
                unset($InstanceIDList[$InstanceIDDevice]);
            } else {
                $Device['instanceID'] = 0;
                $Device['name'] = $this->Translate(IPS_GetModule($GUID)['ModuleName']) . ' (' . $i . ')';
                $Device['Location'] = '';
            }
            $Device['create'] = [
                'moduleID'      => $GUID,
                'configuration' => [$PropertyName => $i]
            ];
            $FoundDevices[] = $Device;
        }

        foreach ($InstanceIDList as $InstanceID => $DeviceAddress) {
            $FoundDevices[] = [
                'Type'        => $Name,
                'Index'       => $DeviceAddress,
                'instanceID'  => $InstanceID,
                'name'        => IPS_GetName($InstanceID),
                'Location'    => stristr(IPS_GetLocation($InstanceID), IPS_GetName($InstanceID), true)
            ];
        }
        return $FoundDevices;
    }

    private function GetInstanceList(string $GUID, int $Parent, string $ConfigParam = ''): array
    {
        $InstanceIDList = [];
        foreach (IPS_GetInstanceListByModuleID($GUID) as $InstanceID) {
            if (IPS_GetInstance($InstanceID)['ConnectionID'] == $Parent) {
                $InstanceIDList[] = $InstanceID;
            }
        }
        if ($ConfigParam != '') {
            $InstanceIDList = array_flip(array_values($InstanceIDList));
            array_walk($InstanceIDList, [$this, 'GetConfigParam'], $ConfigParam);
        }
        return $InstanceIDList;
    }

    private function GetConfigParam(&$item1, int $InstanceID, string $ConfigParam): void
    {
        $item1 = IPS_GetProperty($InstanceID, $ConfigParam);
    }
}
