<?php
$cases = [
    [
        'raw_ids' => [''],
        'payment' => ['2' => ['id_jenis_bayar' => '2', 'cicilan_ke' => '1', 'nominal' => '260000']],
        'kelas' => 'Kelas 1',
        'allowed' => ['1', '2', '3'],
    ],
    [
        'raw_ids' => [' 2 '],
        'payment' => ['2' => ['id_jenis_bayar' => '2']],
        'kelas' => 'I',
        'allowed' => ['1', '2', '3'],
    ],
    [
        'raw_ids' => ['5'],
        'payment' => ['5' => ['id_jenis_bayar' => '5']],
        'kelas' => '6',
        'allowed' => ['4', '5', '6'],
    ],
];
$normalizeKelas = static function ($kelasValue) {
    $kelas = strtoupper(trim((string)$kelasValue));
    $kelas = preg_replace('/\s+/', '', $kelas);
    if ($kelas === null) {
        $kelas = '';
    }
    $romanMap = ['I' => '1', 'II' => '2', 'III' => '3', 'IV' => '4', 'V' => '5', 'VI' => '6'];
    if (isset($romanMap[$kelas])) {
        return $romanMap[$kelas];
    }
    if (preg_match('/([1-6])/', $kelas, $m)) {
        return $m[1];
    }
    return $kelas;
};
foreach ($cases as $index => $case) {
    $normalizedIds = [];
    foreach ($case['raw_ids'] as $value) {
        $clean = trim((string)$value);
        if ($clean !== '' && ctype_digit($clean) && (int)$clean > 0) {
            $normalizedIds[] = (int)$clean;
        }
    }
    if (empty($normalizedIds) && !empty($case['payment'])) {
        foreach ($case['payment'] as $paymentKey => $paymentItem) {
            $paymentKeyStr = trim((string)$paymentKey);
            if ($paymentKeyStr !== '' && ctype_digit($paymentKeyStr) && (int)$paymentKeyStr > 0) {
                $normalizedIds[] = (int)$paymentKeyStr;
                continue;
            }
            if (isset($paymentItem['id_jenis_bayar'])) {
                $paymentIdStr = trim((string)$paymentItem['id_jenis_bayar']);
                if ($paymentIdStr !== '' && ctype_digit($paymentIdStr) && (int)$paymentIdStr > 0) {
                    $normalizedIds[] = (int)$paymentIdStr;
                }
            }
        }
    }
    $normalizedIds = array_values(array_unique($normalizedIds));
    $kelasNormalized = $normalizeKelas($case['kelas']);
    $allowedNormalized = array_map($normalizeKelas, $case['allowed']);
    $kelasMatch = in_array($case['kelas'], $case['allowed'], true) || in_array($kelasNormalized, $allowedNormalized, true);
    echo json_encode([
        'case' => $index + 1,
        'normalized_ids' => $normalizedIds,
        'kelas' => $case['kelas'],
        'kelas_normalized' => $kelasNormalized,
        'allowed_normalized' => $allowedNormalized,
        'kelas_match' => $kelasMatch,
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
?>
