<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/HoymilesWiFi.php';
eval('declare(strict_types=1);namespace HoymilesWiFiDTU {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/VariableHelper.php') . '}');

/**
 * @method void SetValueInteger(string $Ident, int $value)
 * @method void SetValueFloat(string $Ident, float $value)
 * @method void SetValueString(string $Ident, string $value)
 */
class HoymilesWiFiDTU extends IPSModuleStrict
{
    use \HoymilesWiFiDTU\VariableHelper;

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();
    }

    public function ApplyChanges(): void
    {
        //Never delete this line!
        parent::ApplyChanges();
    }

    public function ReceiveData(string $JSONString): string
    {
        $data = json_decode($JSONString);
        $this->SendDebug('Receive', $data->Data, 0);
        $this->DecodeData(json_decode($data->Data, true));
        return '';
    }

    /**
     * Startet die DTU neu.
     *
     * @return bool true wenn die DTU den Befehl bestätigt hat
     */
    public function RebootDTU(): bool
    {
        if (!$this->HasActiveParent()) {
            trigger_error($this->Translate('Instance has no active parent'), E_USER_NOTICE);
            return false;
        }
        $ret = $this->SendDataToParent(json_encode([
            'DataID'   => \HoymilesWiFi\GUID::DeviceToIo,
            'Function' => 'RebootDTU',
            'Data'     => ''
        ]));
        // Bei einem Fehler im IO liefert SendDataToParent false
        if (!is_string($ret) || ($ret === '')) {
            return false;
        }
        return unserialize($ret);
    }

    private function DecodeData(array $DataValues): void
    {
        foreach ($DataValues as $Key => $Value) {
            if ($Key == \HoymilesWiFi\DTU\Variables::SerialNumber) {
                $this->SetSummary($Value);
            }
            if (!array_key_exists($Key, \HoymilesWiFi\DTU\Variables::$Vars)) {
                continue;
            }
            $Var = \HoymilesWiFi\DTU\Variables::$Vars[$Key];
            $this->MaintainVariable($Key, $this->Translate($Var[0]), $Var[1], $Var[2], 0, true);
            switch ($Var[1]) {
                case VARIABLETYPE_INTEGER:
                    $this->SetValueInteger($Key, $Value);
                    break;
                case VARIABLETYPE_FLOAT:
                    $this->SetValueFloat($Key, $Value * $Var[3]);
                    break;
                case VARIABLETYPE_STRING:
                    $this->SetValueString($Key, (string) $Value);
                    break;
            }
        }
    }
}
