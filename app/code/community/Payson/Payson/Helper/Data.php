<?php

class Payson_Payson_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getCountry() {
        $country = array(
            'afghanistan' => 'AF',
            'albania' => 'AL',
            'algeria' => 'DZ',
            'american samoa' => 'AS',
            'andorra' => 'AD',
            'angola' => 'AO',
            'anguilla' => 'AI',
            'antarctica' => 'AQ',
            'antigua and barbuda' => 'AG',
            'argentina' => 'AR',
            'armenia' => 'AM',
            'aruba' => 'AW',
            'australia' => 'AU',
            'austria' => 'AT',
            'azerbaijan' => 'AZ',
            'bahamas' => 'BS',
            'bahrain' => 'BH',
            'bangladesh' => 'BD',
            'barbados' => 'BB',
            'belarus' => 'BY',
            'belgium' => 'BE',
            'belize' => 'BZ',
            'benin' => 'BJ',
            'bermuda' => 'BM',
            'bhutan' => 'BT',
            'bolivia' => 'BO',
            'bosnia and herzegovina' => 'BA',
            'botswana' => 'BW',
            'bouvet island' => 'BV',
            'brazil' => 'BR',
            'british indian ocean territory' => 'IO',
            'brunei darussalam' => 'BN',
            'bulgaria' => 'BG',
            'burkina faso' => 'BF',
            'burundi' => 'BI',
            'cambodia' => 'KH',
            'cameroon' => 'CM',
            'canada' => 'CA',
            'cape verde' => 'CV',
            'cayman islands' => 'KY',
            'central african republic' => 'CF',
            'chad' => 'TD',
            'chile' => 'CL',
            'china' => 'CN',
            'christmas island' => 'CX',
            'cocos (keeling) islands' => 'CC',
            'colombia' => 'CO',
            'comoros' => 'KM',
            'congo' => 'CG',
            'congo, the democratic republic of the' => 'CD',
            'cook islands' => 'CK',
            'costa rica' => 'CR',
            'cote d ivoire' => 'CI',
            'croatia' => 'HR',
            'cuba' => 'CU',
            'cyprus' => 'CY',
            'czech republic' => 'CZ',
            'denmark' => 'DK',
            'djibouti' => 'DJ',
            'dominica' => 'DM',
            'dominican republic' => 'DO',
            'east timor' => 'TP',
            'ecuador' => 'EC',
            'egypt' => 'EG',
            'el salvador' => 'SV',
            'equatorial guinea' => 'GQ',
            'eritrea' => 'ER',
            'estonia' => 'EE',
            'ethiopia' => 'ET',
            'falkland islands (malvinas)' => 'FK',
            'faroe islands' => 'FO',
            'fiji' => 'FJ',
            'finland' => 'FI',
            'france' => 'FR',
            'french guiana' => 'GF',
            'french polynesia' => 'PF',
            'french southern territories' => 'TF',
            'gabon' => 'GA',
            'gambia' => 'GM',
            'georgia' => 'GE',
            'germany' => 'DE',
            'ghana' => 'GH',
            'gibraltar' => 'GI',
            'greece' => 'GR',
            'greenland' => 'GL',
            'grenada' => 'GD',
            'guadeloupe' => 'GP',
            'guam' => 'GU',
            'guatemala' => 'GT',
            'guinea' => 'GN',
            'guinea-bissau' => 'GW',
            'guyana' => 'GY',
            'haiti' => 'HT',
            'heard island and mcdonald islands' => 'HM',
            'holy see (vatican city state)' => 'VA',
            'honduras' => 'HN',
            'hong kong' => 'HK',
            'hungary' => 'HU',
            'iceland' => 'IS',
            'india' => 'IN',
            'indonesia' => 'ID',
            'iran, islamic republic of' => 'IR',
            'iraq' => 'IQ',
            'ireland' => 'IE',
            'israel' => 'IL',
            'italy' => 'IT',
            'jamaica' => 'JM',
            'japan' => 'JP',
            'jordan' => 'JO',
            'kazakstan' => 'KZ',
            'kenya' => 'KE',
            'kiribati' => 'KI',
            'korea democratic peoples republic of' => 'KP',
            'korea republic of' => 'KR',
            'kuwait' => 'KW',
            'kyrgyzstan' => 'KG',
            'lao peoples democratic republic' => 'LA',
            'latvia' => 'LV',
            'lebanon' => 'LB',
            'lesotho' => 'LS',
            'liberia' => 'LR',
            'libyan arab jamahiriya' => 'LY',
            'liechtenstein' => 'LI',
            'lithuania' => 'LT',
            'luxembourg' => 'LU',
            'macau' => 'MO',
            'macedonia, the former yugoslav republic of' => 'MK',
            'madagascar' => 'MG',
            'malawi' => 'MW',
            'malaysia' => 'MY',
            'maldives' => 'MV',
            'mali' => 'ML',
            'malta' => 'MT',
            'marshall islands' => 'MH',
            'martinique' => 'MQ',
            'mauritania' => 'MR',
            'mauritius' => 'MU',
            'mayotte' => 'YT',
            'mexico' => 'MX',
            'micronesia, federated states of' => 'FM',
            'moldova, republic of' => 'MD',
            'monaco' => 'MC',
            'mongolia' => 'MN',
            'montserrat' => 'MS',
            'morocco' => 'MA',
            'mozambique' => 'MZ',
            'myanmar' => 'MM',
            'namibia' => 'NA',
            'nauru' => 'NR',
            'nepal' => 'NP',
            'netherlands' => 'NL',
            'netherlands antilles' => 'AN',
            'new caledonia' => 'NC',
            'new zealand' => 'NZ',
            'nicaragua' => 'NI',
            'niger' => 'NE',
            'nigeria' => 'NG',
            'niue' => 'NU',
            'norfolk island' => 'NF',
            'northern mariana islands' => 'MP',
            'norway' => 'NO',
            'oman' => 'OM',
            'pakistan' => 'PK',
            'palau' => 'PW',
            'palestinian territory, occupied' => 'PS',
            'panama' => 'PA',
            'papua new guinea' => 'PG',
            'paraguay' => 'PY',
            'peru' => 'PE',
            'philippines' => 'PH',
            'pitcairn' => 'PN',
            'poland' => 'PL',
            'portugal' => 'PT',
            'puerto rico' => 'PR',
            'qatar' => 'QA',
            'reunion' => 'RE',
            'romania' => 'RO',
            'russian federation' => 'RU',
            'rwanda' => 'RW',
            'saint helena' => 'SH',
            'saint kitts and nevis' => 'KN',
            'saint lucia' => 'LC',
            'saint pierre and miquelon' => 'PM',
            'saint vincent and the grenadines' => 'VC',
            'samoa' => 'WS',
            'san marino' => 'SM',
            'sao tome and principe' => 'ST',
            'saudi arabia' => 'SA',
            'senegal' => 'SN',
            'seychelles' => 'SC',
            'sierra leone' => 'SL',
            'singapore' => 'SG',
            'slovakia' => 'SK',
            'slovenia' => 'SI',
            'solomon islands' => 'SB',
            'somalia' => 'SO',
            'south africa' => 'ZA',
            'south georgia and the south sandwich islands' => 'GS',
            'spain' => 'ES',
            'sri lanka' => 'LK',
            'sudan' => 'SD',
            'suriname' => 'SR',
            'svalbard and jan mayen' => 'SJ',
            'swaziland' => 'SZ',
            'sweden' => 'SE',
            'switzerland' => 'CH',
            'syrian arab republic' => 'SY',
            'taiwan, province of china' => 'TW',
            'tajikistan' => 'TJ',
            'tanzania, united republic of' => 'TZ',
            'thailand' => 'TH',
            'togo' => 'TG',
            'tokelau' => 'TK',
            'tonga' => 'TO',
            'trinidad and tobago' => 'TT',
            'tunisia' => 'TN',
            'turkey' => 'TR',
            'turkmenistan' => 'TM',
            'turks and caicos islands' => 'TC',
            'tuvalu' => 'TV',
            'uganda' => 'UG',
            'ukraine' => 'UA',
            'united arab emirates' => 'AE',
            'united kingdom' => 'GB',
            'united states' => 'US',
            'united states minor outlying islands' => 'UM',
            'uruguay' => 'UY',
            'uzbekistan' => 'UZ',
            'vanuatu' => 'VU',
            'venezuela' => 'VE',
            'viet nam' => 'VN',
            'virgin islands, british' => 'VG',
            'virgin islands, u.s.' => 'VI',
            'wallis and futuna' => 'WF',
            'western sahara' => 'EH',
            'yemen' => 'YE',
            'yugoslavia' => 'YU',
            'zambia' => 'ZM',
            'zimbabwe' => 'ZW'
        );
        return $country;
    }

}
