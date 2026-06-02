<?php

function nova_clean(?string $value): string
{
    return trim((string) $value);
}

function nova_upper(?string $value): string
{
    $value = trim((string) $value);
    return $value === '' ? '' : mb_strtoupper($value, 'UTF-8');
}

function nova_country(?string $value): string
{
    return nova_upper($value);
}

function nova_country_key(?string $value): string
{
    $value = nova_country($value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return strtoupper((string) $value);
}

function nova_clean_identifier(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/', '', $value);
    return strtolower((string) $value);
}

function nova_redirect_back(): void
{
    header('Location: /nova1/modules/students/create.php');
    exit;
}

function nova_valid_ecuador_id(string $cedula): bool
{
    if (!preg_match('/^\d{10}$/', $cedula)) {
        return false;
    }

    $province = (int) substr($cedula, 0, 2);
    $third    = (int) substr($cedula, 2, 1);

    if ($province < 1 || $province > 24) {
        return false;
    }

    if ($third >= 6) {
        return false;
    }

    $digits = array_map('intval', str_split($cedula));
    $sum = 0;

    for ($i = 0; $i < 9; $i++) {
        $value = $digits[$i];

        if ($i % 2 === 0) {
            $value *= 2;
            if ($value > 9) {
                $value -= 9;
            }
        }

        $sum += $value;
    }

    $verifier = (10 - ($sum % 10)) % 10;

    return $verifier === $digits[9];
}

function nova_get_name_by_id(mysqli $conn, string $table, int $id): string
{
    if ($id <= 0) {
        return '';
    }

    $allowed = ['occupations', 'provinces', 'cantons'];

    if (!in_array($table, $allowed, true)) {
        return '';
    }

    $sql = "SELECT name FROM {$table} WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return '';
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    $name = '';

    if ($row = $result->fetch_assoc()) {
        $name = (string) $row['name'];
    }

    $stmt->close();

    return $name;
}
