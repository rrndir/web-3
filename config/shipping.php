<?php

return [
    'rajaongkir' => [
        'api_key' => env('RAJAONGKIR_API_KEY'),
        'base_url' => env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'),
        'origin_city_id' => env('RAJAONGKIR_ORIGIN_CITY_ID'),
        'couriers' => [
            'jne' => 'JNE',
            'sicepat' => 'SiCepat',
            'ide' => 'ID Express',
            'sap' => 'SAP Express',
            'jnt' => 'J&T Express',
            'ninja' => 'Ninja Xpress',
            'tiki' => 'TIKI',
            'lion' => 'Lion Parcel',
            'anteraja' => 'AnterAja',
            'pos' => 'Pos Indonesia',
            'ncs' => 'NCS',
            'rex' => 'Rex Express',
            'rpx' => 'RPX',
            'sentral' => 'Sentral Cargo',
            'star' => 'Star Cargo',
            'wahana' => 'Wahana',
            'dse' => 'DSE',
        ],
    ],
];
