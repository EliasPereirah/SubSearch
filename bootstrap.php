<?php
require_once __DIR__."/config.php";
use Symfony\Component\HttpClient\HttplugClient;
require_once __DIR__.'/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
const TS_CONFIG =  [
    'api_key' => TS_API_KEY,
    'nodes' => [
        [
            'host' => TS_HOST,
            'port' => TS_PORT,
            'protocol' => TS_PROTOCOL
        ],
    ],
    'client' => new HttplugClient(),
];

$voyage_api_key = $_ENV['VOYAGE_API_KEY'] ?? '';
$youtube_api_key = $_ENV['YOUTUBE_API_KEY'] ?? '';
define("YOUTUBE_API_KEY", $youtube_api_key);
define("VOYAGE_API_KEY", $voyage_api_key);