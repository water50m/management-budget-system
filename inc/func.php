<?php
// inc/func.php

function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // ข้ามบรรทัดที่เป็น Comment (#)
        if (strpos(trim($line), '#') === 0) continue;

        // แยก Key และ Value ด้วยเครื่องหมาย =
        list($name, $value) = explode('=', $line, 2);
        
        $name = trim($name);
        $value = trim($value);

        // นำค่าไปใส่ใน $_ENV และ putenv เพื่อให้ดึงไปใช้ง่ายๆ
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
        }
    }
    return true;
}