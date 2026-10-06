<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/HoymilesWiFi.php';
require_once dirname(__DIR__) . '/libs/HoymilesWarnCodes.php';
eval('declare(strict_types=1);namespace HoymilesWiFiInverter {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/VariableHelper.php') . '}');

/**
 * @method void SetValueBoolean(string $Ident, bool $value)
 * @method void SetValueInteger(string $Ident, int $value)
 * @method void SetValueFloat(string $Ident, float $value)
 * @method void SetValueString(string $Ident, string $value)
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
        $this->SendDebug('Receive', $data->Data, 0);        $this->DecodeData(json_decode($data->Data, true));
        return '';
    }

    public function SetPowerLimit(int $Limit): bool
    {
        if (!$this->HasActiveParent() || (@IPS_GetInstance($this->InstanceID)['ConnectionID'] < 10000)) {
            trigger_error($this->Translate('Instance has no active parent'), E_USER_NOTICE);
            return false;
        }
        if (($Limit < \HoymilesWiFi\Inverter\SetPowerLimit::Min) || ($Limit > \HoymilesWiFi\Inverter\SetPowerLimit::Max)) {
            trigger_error(sprintf($this->Translate('Power limit must be between %d and %d %%.'), \HoymilesWiFi\Inverter\SetPowerLimit::Min, \HoymilesWiFi\Inverter\SetPowerLimit::Max), E_USER_NOTICE);
            return false;
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

    /**
     * Startet den Wechselrichter neu.
     *
     * @return bool true wenn die DTU den Befehl bestätigt hat
     */
    public function RebootInverter(): bool
    {
        if (!$this->HasActiveParent()) {
            trigger_error($this->Translate('Instance has no active parent'), E_USER_NOTICE);
            return false;
        }
        $Number = $this->ReadPropertyInteger(\HoymilesWiFi\Inverter\Property::Number);
        if (($Number < 1) || ($Number > 3)) {
            return false;
        }
        $ret = $this->SendDataToParent(json_encode([
            'DataID'   => \HoymilesWiFi\GUID::DeviceToIo,
            'Function' => 'RebootInverter',
            'Data'     => $Number
        ]));
        if ($ret === '') {
            return false;
        }
        return unserialize($ret);
    }

    /**
     * Liefert die zuletzt von der DTU gelesene Warnliste des Wechselrichters.
     *
     * @return array Liste der Warnungen mit Code, Text, Start- und Endzeit
     */
    public function GetWarnings(): array
    {
        $Warnings = json_decode($this->GetBuffer(\HoymilesWiFi\Inverter\Variables::WarningList), true);
        return is_array($Warnings) ? $Warnings : [];
    }

    /**
     * Bereitet die Warnliste für die Variablen auf.
     *
     * @param array $Warnings
     * @return array Ident => Wert
     */
    private function DecodeWarnings(array $Warnings): array
    {
        $German = str_starts_with(IPS_GetSystemLanguage(), 'de');
        $ActiveTexts = [];
        $Last = null;
        foreach ($Warnings as &$Warning) {
            $Warning['Text'] = \Hoymiles\DTU\WarnCodes::GetText($Warning['Code'], $German);
            $Warning['Active'] = ($Warning['EndTime'] == 0);
            if ($Warning['Active']) {
                $ActiveTexts[$Warning['Code']] = $Warning['Text'];
            }
            if (($Last === null) || ($Warning['StartTime'] >= $Last['StartTime'])) {
                $Last = $Warning;
            }
        }
        unset($Warning);
        $this->SetBuffer(\HoymilesWiFi\Inverter\Variables::WarningList, json_encode($Warnings));
        $Values = [
            \HoymilesWiFi\Inverter\Variables::ActiveWarnings => count(array_filter($Warnings, function ($Warning)
            {
                return $Warning['Active'];
            })),
            \HoymilesWiFi\Inverter\Variables::CurrentWarning => implode(', ', $ActiveTexts)
        ];
        if ($Last !== null) {
            $Values[\HoymilesWiFi\Inverter\Variables::LastWarning] = $Last['Text'] . ' (Code ' . $Last['Code'] . ')';
        }
        return $Values;
    }

    private function DecodeData(array $DataValues): void
    {
        if (isset($DataValues[\HoymilesWiFi\Inverter\Variables::WarningList])) {
            $DataValues = array_merge($DataValues, $this->DecodeWarnings($DataValues[\HoymilesWiFi\Inverter\Variables::WarningList]));
        }
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
            // Proto3 überträgt 0 nicht, ein fehlendes Leistungslimit (z.B. beim Anlaufen des WR) ist nicht 0 %.
            // Ein echtes Limit ist mindestens 2 % (SetPowerLimit::Min).
            if (($Key == \HoymilesWiFi\Inverter\Variables::PowerLimit) && ($Value == 0)) {
                continue;
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
                case VARIABLETYPE_STRING:
                    $this->SetValueString($Key, (string) $Value);
                    break;
            }
        }
    }
}
