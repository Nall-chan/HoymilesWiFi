<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/HoymilesWiFi.php';
eval('declare(strict_types=1);namespace HoymilesWiFiSolarPort {?>' . file_get_contents(dirname(__DIR__) . '/libs/helper/VariableHelper.php') . '}');

/**
 * @method void SetValueFloat(string $Ident, float $value)
 */
class HoymilesWiFiSolarPort extends IPSModuleStrict
{
    use \HoymilesWiFiSolarPort\VariableHelper;

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyInteger(\HoymilesWiFi\SolarPort\Property::Port, 1);
    }

    public function ApplyChanges(): void
    {
        $Address = $this->ReadPropertyInteger(\HoymilesWiFi\SolarPort\Property::Port);
        $this->SetSummary('Port: ' . (string) $Address);

        if ($Address < 1) {
            $this->SetReceiveDataFilter('.*NOTING.*');
            return;
        }
        $Filter = '.*\\\\"pi\\\\"\:' . $Address . ',.*';
        $this->SetReceiveDataFilter($Filter);

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

    private function DecodeData(array $DataValues): void
    {
        foreach ($DataValues as $Key => $Value) {
            if (!array_key_exists($Key, \HoymilesWiFi\SolarPort\Variables::$Vars)) {
                continue;
            }
            $Var = \HoymilesWiFi\SolarPort\Variables::$Vars[$Key];
            $this->MaintainVariable($Key, $this->Translate($Var[0]), $Var[1], $Var[2], 0, true);

            switch ($Var[1]) {
                case VARIABLETYPE_FLOAT:
                    $this->SetValueFloat($Key, $Value * $Var[3]);
                    break;
            }
        }
    }
}
