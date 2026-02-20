<?php

$databaseUrl = getenv("DATABASE_URL");

if (!$databaseUrl) {
    $databaseUrl = "postgresql://neondb_owner:npg_PefWrcOF68dg@ep-mute-sound-aicn6h3q-pooler.c-4.us-east-1.aws.neon.tech/staff_jobs?sslmode=require&channel_binding=require";
}

$dbparts = parse_url($databaseUrl);
//print_r($dbparts);
//die;

$host = $dbparts["host"];
$port = $dbparts["port"] ?? 5432;
$user = $dbparts["user"];
$pass = $dbparts["pass"];
$dbname = ltrim($dbparts["path"], "/");

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => true
    ]);

    //echo "PostgreSQL Connected Successfully!";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
