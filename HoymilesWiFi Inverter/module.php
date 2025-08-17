<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/HoymilesWiFi.php';
eval('declare(strict_types=1);namespace HoymilesWiFiInverter {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/VariableHelper.php') . '}');

/**
 * @method void SetValueBoolean(string $Ident, bool $value)
 * @method void SetValueInteger(string $Ident, int $value)
 * @method void SetValueFloat(string $Ident, float $value)
 *
 */
class HoymilesWiFiInverter extends IPSModuleStrict
{
    use \HoymilesWiFiInverter\VariableHelper;

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyInteger(\HoymilesWiFi\Inverter\Property::Number, 1);
    }

    public function ApplyChanges(): void
    {
        $Address = $this->ReadPropertyInteger(\HoymilesWiFi\Inverter\Property::Number);
        $this->SetSummary('Number: ' . (string) $Address);

        if ($Address < 1) {
            $this->SetReceiveDataFilter('.*NOTING.*');
            return;
        }
        $Filter = '.*\\\\"ver\\\\"\:' . $Address . ',.*';
        $this->SetReceiveDataFilter($Filter);

        //Never delete this line!
        parent::ApplyChanges();
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        switch ($Ident) {
            case \HoymilesWiFi\Inverter\Variables::PowerLimit:
                $this->SetPowerLimit((int) $Value);
                return;
        }
        trigger_error($this->Translate('Invalid Ident') . ' :' . $Ident, E_USER_NOTICE);
    }

    public function ReceiveData(string $JSONString): string
    {
        $data = json_decode($JSONString);
        $this->SendDebug('Receive', $data->Data, 0);
        $this->DecodeData(json_decode($data->Data, true));
        return '';
    }

    public function SetPowerLimit(int $Limit): bool
    {
        if (!$this->HasActiveParent() || (@IPS_GetInstance($this->InstanceID)['ConnectionID'] < 10000)) {
            trigger_error($this->Translate('Instance has no active parent'), E_USER_NOTICE);
        }
        $Number = $this->ReadPropertyInteger(\HoymilesWiFi\Inverter\Property::Number);
        if (($Number < 1) || ($Number > 3)) {
            return false;
        }
        $Data = \HoymilesWiFi\Inverter\SetPowerLimit::$DataPrefix[$Number] . ':' . (string) ($Limit * 10) . "\r";
        $this->SendDebug(__FUNCTION__, $Data, 0);
        $ret = $this->SendDataToParent(json_encode([
            'DataID'   => \HoymilesWiFi\GUID::DeviceToIo,
            'Function' => 'SetPowerLimit',
            'Data'     => $Data
        ]));
        $Result = unserialize($ret);
        if ($Result) {
            $this->SetValueInteger(\HoymilesWiFi\Inverter\Variables::PowerLimit, $Limit);
        }
        return $Result;
    }

    public function SetInverterState(bool $Active): bool
    {
        if (!$this->HasActiveParent() || (@IPS_GetInstance($this->InstanceID)['ConnectionID'] < 10000)) {
            trigger_error($this->Translate('Instance has no active parent'), E_USER_NOTICE);
        }
        $Number = $this->ReadPropertyInteger(\HoymilesWiFi\Inverter\Property::Number);
        if (($Number < 1) || ($Number > 3)) {
            return false;
        }
        $ret = $this->SendDataToParent(json_encode([
            'DataID'   => \HoymilesWiFi\GUID::DeviceToIo,
            'Function' => $Active ? 'StartInverter' : 'StopInverter',
            'Data'     => ''
        ]));
        return unserialize($ret);
    }

    private function DecodeData(array $DataValues): void
    {
        foreach ($DataValues as $Key => $Value) {
            if (!array_key_exists($Key, \HoymilesWiFi\Inverter\Variables::$Vars)) {
                continue;
            }
            $Var = \HoymilesWiFi\Inverter\Variables::$Vars[$Key];
            $this->MaintainVariable($Key, $this->Translate($Var[0]), $Var[1], $Var[2], 0, true);
            if (count($Var) > 4) {
                if ($Var[4]) {
                    $this->EnableAction($Key);
                }
            }

            switch ($Var[1]) {
                case VARIABLETYPE_FLOAT:
                    $this->SetValueFloat($Key, $Value * $Var[3]);
                    break;
                case VARIABLETYPE_INTEGER:
                    $this->SetValueInteger($Key, (int) ($Value * $Var[3]));
                    break;
                case VARIABLETYPE_BOOLEAN:
                    $this->SetValueBoolean($Key, (bool) $Value);
                    break;
            }
        }
    }
}
