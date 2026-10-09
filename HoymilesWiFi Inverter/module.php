<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/HoymilesWiFi.php';
require_once dirname(__DIR__) . '/libs/HoymilesWarnCodes.php';
eval('declare(strict_types=1);namespace HoymilesWiFiInverter {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/VariableHelper.php') . '}');
eval('declare(strict_types=1);namespace HoymilesWiFiInverter {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/BufferHelper.php') . '}');

/**
 * @property array $WarningList
 * @property int $LinkMissing
 * @property int $PowerLimitMismatch
 *
 * @method void SetValueBoolean(string $Ident, bool $value)
 * @method void SetValueInteger(string $Ident, int $value)
 * @method void SetValueFloat(string $Ident, float $value)
 * @method void SetValueString(string $Ident, string $value)
 *
 */
class HoymilesWiFiInverter extends IPSModuleStrict
{
    use \HoymilesWiFiInverter\VariableHelper;
    use \HoymilesWiFiInverter\BufferHelper;

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();
        $this->WarningList = [];
        $this->LinkMissing = 0;
        $this->RegisterPropertyInteger(\HoymilesWiFi\Inverter\Property::Number, 1);
        $this->RegisterPropertyBoolean(\HoymilesWiFi\Inverter\Property::EnablePowerLimitWatt, false);
        // Bleibt über Neustarts erhalten, weil die DTU das Echo des letzten Limit-Befehls weiter meldet
        $this->RegisterAttributeString(\HoymilesWiFi\Inverter\Attribute::PowerLimitKind, \HoymilesWiFi\Inverter\PowerLimitKind::Unknown);
        $this->RegisterAttributeInteger(\HoymilesWiFi\Inverter\Attribute::PowerLimitSent, 0);
        $this->RegisterAttributeBoolean(\HoymilesWiFi\Inverter\Attribute::PowerLimitConfirmed, false);
        $this->RegisterAttributeInteger(\HoymilesWiFi\Inverter\Attribute::RatedPower, 0);
        $this->PowerLimitMismatch = 0;
    }

    public function ApplyChanges(): void
    {
        $this->WarningList = [];
        $this->LinkMissing = 0;
        $this->PowerLimitMismatch = 0;
        $Address = $this->ReadPropertyInteger(\HoymilesWiFi\Inverter\Property::Number);
        $this->SetSummary('Number: ' . (string) $Address);
        // Variable sofort anlegen, die DTU meldet das Watt-Limit erst nach dem ersten Setzen
        $this->MaintainPowerLimitWatt();

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
            case \HoymilesWiFi\Inverter\Variables::PowerLimitWatt:
                $this->SetPowerLimitWatt((float) $Value);
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
        // Bei einem Fehler im IO liefert SendDataToParent false
        if (!is_string($ret) || ($ret === '')) {
            return false;
        }
        $Result = unserialize($ret);
        if ($Result) {
            $this->RememberPowerLimit(\HoymilesWiFi\Inverter\PowerLimitKind::Percent, $Limit * 10);
            // Ein Limit in % hebt das Limit in Watt auf
            $this->ResetPowerLimitWatt();
            $this->SetValueInteger(\HoymilesWiFi\Inverter\Variables::PowerLimit, $Limit);
        }
        return $Result;
    }

    /**
     * Setzt ein Laufzeit-Leistungslimit in Watt (Action 211, nur HMS-W-2T-Familie).
     * Wird nicht gespeichert und geht beim Neustart des Inverters verloren, danach gilt wieder das Limit in Prozent.
     *
     * @param float $Watt Limit in W (0.1 bis 3276.7)
     * @return bool true wenn die DTU den Befehl bestätigt hat
     */
    public function SetPowerLimitWatt(float $Watt): bool
    {
        if (!$this->HasActiveParent() || (@IPS_GetInstance($this->InstanceID)['ConnectionID'] < 10000)) {
            trigger_error($this->Translate('Instance has no active parent'), E_USER_NOTICE);
            return false;
        }
        if (($Watt < \HoymilesWiFi\Inverter\SetPowerLimitWatt::Min) || ($Watt > \HoymilesWiFi\Inverter\SetPowerLimitWatt::Max)) {
            trigger_error(sprintf($this->Translate('Power limit must be between %.1f and %.1f W.'), \HoymilesWiFi\Inverter\SetPowerLimitWatt::Min, \HoymilesWiFi\Inverter\SetPowerLimitWatt::Max), E_USER_NOTICE);
            return false;
        }
        $Raw = (int) round($Watt * 10);
        $Data = sprintf(\HoymilesWiFi\Inverter\SetPowerLimitWatt::DataFormat, $Raw);
        $this->SendDebug(__FUNCTION__, $Data, 0);
        $ret = $this->SendDataToParent(json_encode([
            'DataID'   => \HoymilesWiFi\GUID::DeviceToIo,
            'Function' => 'SetPowerLimitWatt',
            'Data'     => $Data
        ]));
        // Bei einem Fehler im IO liefert SendDataToParent false
        if (!is_string($ret) || ($ret === '')) {
            return false;
        }
        $Result = unserialize($ret);
        if ($Result) {
            $this->RememberPowerLimit(\HoymilesWiFi\Inverter\PowerLimitKind::Watt, $Raw);
            if ($this->ReadPropertyBoolean(\HoymilesWiFi\Inverter\Property::EnablePowerLimitWatt)) {
                $this->SetValueFloat(\HoymilesWiFi\Inverter\Variables::PowerLimitWatt, round($Watt, 1));
            }
        }
        return $Result;
    }

    public function SetInverterState(bool $Active): bool
    {
        if (!$this->HasActiveParent() || (@IPS_GetInstance($this->InstanceID)['ConnectionID'] < 10000)) {
            trigger_error($this->Translate('Instance has no active parent'), E_USER_NOTICE);
            return false;
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
        // Bei einem Fehler im IO liefert SendDataToParent false
        if (!is_string($ret) || ($ret === '')) {
            return false;
        }
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
        // Bei einem Fehler im IO liefert SendDataToParent false
        if (!is_string($ret) || ($ret === '')) {
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
        return $this->WarningList;
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
        $this->WarningList = $Warnings;
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

    /**
     * Legt die Variable für das Watt-Limit an, wenn sie aktiviert ist. Maximum des Sliders ist die Nennleistung, falls bekannt.
     *
     * @return void
     */
    private function MaintainPowerLimitWatt(): void
    {
        if (!$this->ReadPropertyBoolean(\HoymilesWiFi\Inverter\Property::EnablePowerLimitWatt)) {
            return;
        }
        $Var = \HoymilesWiFi\Inverter\Variables::$Vars[\HoymilesWiFi\Inverter\Variables::PowerLimitWatt];
        $RatedPower = $this->ReadAttributeInteger(\HoymilesWiFi\Inverter\Attribute::RatedPower);
        if ($RatedPower > 0) {
            $Var[2]['MAX'] = $RatedPower;
        }
        $this->MaintainVariable(\HoymilesWiFi\Inverter\Variables::PowerLimitWatt, $this->Translate($Var[0]), $Var[1], $Var[2], 0, true);
        $this->EnableAction(\HoymilesWiFi\Inverter\Variables::PowerLimitWatt);
    }

    /**
     * Kein Limit in Watt aktiv: Variable auf die Nennleistung (ohne Begrenzung) bzw. das Maximum des Sliders setzen.
     *
     * @return void
     */
    private function ResetPowerLimitWatt(): void
    {
        if (!$this->ReadPropertyBoolean(\HoymilesWiFi\Inverter\Property::EnablePowerLimitWatt)) {
            return;
        }
        $RatedPower = $this->ReadAttributeInteger(\HoymilesWiFi\Inverter\Attribute::RatedPower);
        if ($RatedPower <= 0) {
            $RatedPower = \HoymilesWiFi\Inverter\Variables::$Vars[\HoymilesWiFi\Inverter\Variables::PowerLimitWatt][2]['MAX'];
        }
        $this->SetValueFloat(\HoymilesWiFi\Inverter\Variables::PowerLimitWatt, (float) $RatedPower);
    }

    /**
     * Merkt sich das aus Symcon gesendete Limit, um dessen Echo von Änderungen aus der App zu unterscheiden.
     *
     * @param string $Kind PowerLimitKind
     * @param int $Raw Rohwert (0.1 % bzw. 0.1 W)
     * @return void
     */
    private function RememberPowerLimit(string $Kind, int $Raw): void
    {
        $this->WriteAttributeString(\HoymilesWiFi\Inverter\Attribute::PowerLimitKind, $Kind);
        $this->WriteAttributeInteger(\HoymilesWiFi\Inverter\Attribute::PowerLimitSent, $Raw);
        $this->WriteAttributeBoolean(\HoymilesWiFi\Inverter\Attribute::PowerLimitConfirmed, false);
        $this->PowerLimitMismatch = 0;
    }

    /**
     * pLim ist das Echo des zuletzt gesendeten Limit-Befehls: nach Action 8 in 0.1 %, nach Action 211 in 0.1 W.
     * Die DTU meldet nicht, welcher Befehl es war. Weicht das Echo vom zuletzt aus Symcon gesendeten Wert ab,
     * wurde das Limit außerhalb geändert (App, live geprüft: die App setzt immer ein Limit in %).
     *
     * @param array $DataValues
     * @return array DataValues mit pLim oder pLimW
     */
    private function RoutePowerLimitEcho(array $DataValues): array
    {
        $Echo = $DataValues[\HoymilesWiFi\Inverter\Variables::PowerLimit];
        // Fehlt (proto3: 0), wird in DecodeData übersprungen
        if ($Echo == 0) {
            return $DataValues;
        }
        $Kind = $this->ReadAttributeString(\HoymilesWiFi\Inverter\Attribute::PowerLimitKind);
        $Sent = $this->ReadAttributeInteger(\HoymilesWiFi\Inverter\Attribute::PowerLimitSent);
        // Ohne gesendeten Rohwert (z.B. Stand vor 1.26) ist die Zuordnung nicht prüfbar
        if ($Sent == 0) {
            $Kind = \HoymilesWiFi\Inverter\PowerLimitKind::Unknown;
        }
        if ($Kind == \HoymilesWiFi\Inverter\PowerLimitKind::Lost) {
            if ($Echo == $Sent) {
                $this->SendDebug('PowerLimit', 'Echo ' . $Echo . ' is the lost runtime limit, ignored', 0);
                unset($DataValues[\HoymilesWiFi\Inverter\Variables::PowerLimit]);
                return $DataValues;
            }
            $this->SendDebug('PowerLimit', 'Echo ' . $Echo . ' differs from lost runtime limit, changed outside Symcon, assume percent', 0);
            $this->RememberPowerLimit(\HoymilesWiFi\Inverter\PowerLimitKind::Unknown, 0);
            $Kind = \HoymilesWiFi\Inverter\PowerLimitKind::Unknown;
        }
        if (($Kind == \HoymilesWiFi\Inverter\PowerLimitKind::Percent) || ($Kind == \HoymilesWiFi\Inverter\PowerLimitKind::Watt)) {
            if ($Echo == $Sent) {
                $this->WriteAttributeBoolean(\HoymilesWiFi\Inverter\Attribute::PowerLimitConfirmed, true);
                $this->PowerLimitMismatch = 0;
            } else {
                if (!$this->ReadAttributeBoolean(\HoymilesWiFi\Inverter\Attribute::PowerLimitConfirmed)) {
                    // Direkt nach dem Senden kann die DTU noch den alten Wert melden
                    $Mismatch = $this->PowerLimitMismatch + 1;
                    $this->PowerLimitMismatch = $Mismatch;
                    if ($Mismatch < \HoymilesWiFi\Inverter\PowerLimitKind::UnconfirmedLimit) {
                        $this->SendDebug('PowerLimit', 'Echo ' . $Echo . ' not yet ' . $Sent . ' (' . $Mismatch . '/' . \HoymilesWiFi\Inverter\PowerLimitKind::UnconfirmedLimit . ')', 0);
                        unset($DataValues[\HoymilesWiFi\Inverter\Variables::PowerLimit]);
                        return $DataValues;
                    }
                }
                $this->SendDebug('PowerLimit', 'Echo ' . $Echo . ' differs from sent ' . $Sent . ', changed outside Symcon, assume percent', 0);
                $this->RememberPowerLimit(\HoymilesWiFi\Inverter\PowerLimitKind::Unknown, 0);
                $Kind = \HoymilesWiFi\Inverter\PowerLimitKind::Unknown;
                $this->ResetPowerLimitWatt();
            }
        }
        if ($Kind == \HoymilesWiFi\Inverter\PowerLimitKind::Watt) {
            unset($DataValues[\HoymilesWiFi\Inverter\Variables::PowerLimit]);
            if ($this->ReadPropertyBoolean(\HoymilesWiFi\Inverter\Property::EnablePowerLimitWatt)) {
                $DataValues[\HoymilesWiFi\Inverter\Variables::PowerLimitWatt] = $Echo;
            }
            return $DataValues;
        }
        // Mehr als 100 % kann nur ein Watt-Limit sein, das außerhalb von Symcon gesetzt wurde
        if ($Echo > \HoymilesWiFi\Inverter\SetPowerLimit::Max * 10) {
            $this->SendDebug('PowerLimit', 'Echo ' . $Echo . ' is not a percentage, ignored', 0);
            unset($DataValues[\HoymilesWiFi\Inverter\Variables::PowerLimit]);
        }
        return $DataValues;
    }

    private function DecodeData(array $DataValues): void
    {
        if (isset($DataValues[\HoymilesWiFi\Inverter\Variables::WarningList])) {
            $DataValues = array_merge($DataValues, $this->DecodeWarnings($DataValues[\HoymilesWiFi\Inverter\Variables::WarningList]));
        }
        // Nach einem Neustart des WR gilt kein Limit in Watt mehr, die DTU meldet den alten Wert aber weiter
        if (!empty($DataValues[\HoymilesWiFi\Inverter\Variables::Restarted])) {
            if ($this->ReadAttributeString(\HoymilesWiFi\Inverter\Attribute::PowerLimitKind) == \HoymilesWiFi\Inverter\PowerLimitKind::Watt) {
                $this->SendDebug('Restarted', 'Runtime power limit is lost', 0);
                $this->WriteAttributeString(\HoymilesWiFi\Inverter\Attribute::PowerLimitKind, \HoymilesWiFi\Inverter\PowerLimitKind::Lost);
            }
            $this->ResetPowerLimitWatt();
        }
        if (isset($DataValues[\HoymilesWiFi\Inverter\Variables::PowerLimit])) {
            $DataValues = $this->RoutePowerLimitEcho($DataValues);
        }
        if (isset($DataValues[\HoymilesWiFi\Inverter\Variables::RatedPower])) {
            $RatedPower = (int) $DataValues[\HoymilesWiFi\Inverter\Variables::RatedPower];
            if (($RatedPower > 0) && ($RatedPower != $this->ReadAttributeInteger(\HoymilesWiFi\Inverter\Attribute::RatedPower))) {
                $this->SendDebug('RatedPower', $RatedPower . ' W', 0);
                $this->WriteAttributeInteger(\HoymilesWiFi\Inverter\Attribute::RatedPower, $RatedPower);
                $this->MaintainPowerLimitWatt();
            }
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
            // Ein echtes Limit ist mindestens 2 % (SetPowerLimit::Min) bzw. 0.1 W.
            if ((($Key == \HoymilesWiFi\Inverter\Variables::PowerLimit) || ($Key == \HoymilesWiFi\Inverter\Variables::PowerLimitWatt)) && ($Value == 0)) {
                continue;
            }
            // Auch link fehlt bei manchen DTUs in einzelnen Antworten, erst nach mehreren Abfragen in Folge als Alarm werten.
            if ($Key == \HoymilesWiFi\Inverter\Variables::Link) {
                if ($Value == 0) {
                    $Missing = $this->LinkMissing + 1;
                    $this->LinkMissing = $Missing;
                    if ($Missing < \HoymilesWiFi\Inverter\Variables::LinkMissingLimit) {
                        $this->SendDebug('Link', 'missing ' . $Missing . '/' . \HoymilesWiFi\Inverter\Variables::LinkMissingLimit, 0);
                        continue;
                    }
                } else {
                    $this->LinkMissing = 0;
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
                case VARIABLETYPE_STRING:
                    $this->SetValueString($Key, (string) $Value);
                    break;
            }
        }
    }
}
