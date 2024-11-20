

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

if (!$USER->IsAdmin()) {
    die("Доступ запрещен.");
}

use Bitrix\Main\Loader;

Loader::includeModule('iblock');

$IBLOCK_ID = 2;
$CSV_FILE = $_SERVER['DOCUMENT_ROOT'] . "/upload/vacancy.csv"; 


if (!file_exists($CSV_FILE)) {
    die("Файл $CSV_FILE не найден.");
}


$arProps = [];
$rsProps = CIBlockProperty::GetList([], ['IBLOCK_ID' => $IBLOCK_ID]);
while ($prop = $rsProps->Fetch()) {
    if ($prop['PROPERTY_TYPE'] == 'L') {
        $arProps[$prop['CODE']] = $prop;
        $arProps[$prop['CODE']]['VALUES'] = [];

        $rsPropEnum = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $prop['ID']]);
        while ($enum = $rsPropEnum->Fetch()) {
            $arProps[$prop['CODE']]['VALUES'][$enum['VALUE']] = $enum['ID'];
        }
    }
}


if (($handle = fopen($CSV_FILE, "r")) !== false) {
    $row = 1;
    $el = new CIBlockElement;

    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        if ($row == 1) {
            $row++;
            continue; 
        }

        $PROP = [
            'OFFICE' => $data[0],
            'LOCATION' => $data[1],
            'REQUIRE' => $data[3],
            'DUTY' => $data[4],
            'CONDITIONS' => $data[5],
            'SALARY_VALUE' => $data[6],
            'SALARY_TYPE' => $data[7],
            'TYPE' => $data[8],
            'ACTIVITY' => $data[9] === 'Да' ? 'Y' : 'N',
            'SCHEDULE' => $data[10],
            'FIELD' => $data[11],
            'EMAIL' => $data[12],
        ];

            // обработка свойств типа "Список"
        foreach ($PROP as $key => &$value) {
            if (isset($arProps[$key])) {
                $value = trim($value);
                if (!array_key_exists($value, $arProps[$key]['VALUES'])) {
               // добавляет новое значение в список если его нет
                    $enum = new CIBlockPropertyEnum;
                    $newEnumId = $enum->Add([
                        'PROPERTY_ID' => $arProps[$key]['ID'],
                        'VALUE' => $value,
                    ]);
                    $arProps[$key]['VALUES'][$value] = $newEnumId;
                }
                $value = $arProps[$key]['VALUES'][$value];
            }
        }

        $arLoadProductArray = [
            "IBLOCK_ID" => $IBLOCK_ID,
            "PROPERTY_VALUES" => $PROP,
            "NAME" => $data[2], 
            "ACTIVE" => "Y",    
        ];

     
        if ($PRODUCT_ID = $el->Add($arLoadProductArray)) {
            echo "Добавлен элемент с ID: $PRODUCT_ID<br>";
        } else {
            echo "Ошибка добавления: " . $el->LAST_ERROR . "<br>";
        }

        $row++;
    }
    fclose($handle);
}
