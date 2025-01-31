<?php
const PRODUCTION = false;
if(!PRODUCTION){
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

const PUBLIC_DIR = __DIR__."/html";

const SITE_NAME = 'SubSearch';
const BASE_URL = 'http://localhost/SubSearch/html'; // sem barra no final

const SEARCH_ENDPOINT = "http://localhost/SubSearch/html/search_api"; // sem barra no final


const SITE_DESCRIPTION = 'SubSearch: Seu Buscador em Vídeos';
const CSS_VERSION = 'v1.0';
const JS_VERSION = 'v1.0';
const USER_AGENT = "Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Mobile Safari/537.36";
const CHUNK_LENGTH = 260; // valor aproximado do tamanho de cada trecho/chunk da legenda extraída
const EXECUTION_INTERVAL = 1; // Intervalo em minutos entre um processo e outro - Não confundir com $execution_timeout
// Quando um cron chama o script extract_captions.php o código só realizará o processamento caso tenha um intervalo entre
// uma chamada e outra maior que EXECUTION_INTERVAL


const MAX_ATTEMPTS = 7; // Quantas vezes o script vai tentar extrair uma legenda do YouTube


const MAX_YT_HITS = 20;
// quantas vezes ao máximo será feita requisições HTTP no YouTube por cada chamada do script extract_captions.php
// Número maior permite indexação mais rápida, mas corre maior risco de ter o IP bloqueado

const YOUTUBE_COOKIE_FILE_PATH = __DIR__.'/youtube_cookies.txt';
// Obs: Criar e usar uma conta do YouTube especificamente para isso
// Como obter cookies: https://github.com/EliasPereirah/YoutubeSubtitlesDownloader/tree/main?tab=readme-ov-file#chromeedge-extension
const DO_YT_LOGIN = true; // se será feito login com cookies do YouTube
// Pode ser necessário caso o YouTube bloquei o acesso ao IP
// Permitindo o acesso apenas quando logado


## Typesense Configuration

// Muda o valor abaixo com sua chave api TypeSense
const TS_API_KEY = "waWHaoA6qG9PnwNShTZe1uc07XT6NI2iUgFGJg9jlDISoX0P"; // Typesense API key
const TS_PORT = 8108;
const TS_HOST = "localhost";
const TS_PROTOCOL = "http";

const VOYAGE_EMBEDDING_MODEL = "voyage-3-large"; // Não mudar após indexação
const VECTOR_DIMENSION = 1024; // Não mudar após indexação


# MySQL Configuration
const DB_CONFIG = [
    "driver" => "mysql",
    "host" => "localhost",
    "port" => "3306",
    "dbname" => "subsearch",
    "username" => "admin",
    "passwd" => "love",
    "options" => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ]
];



const HYBRID_SEARCH = true; // Para melhores resultados deixar true - fulltext e vector search

const DO_RERANK = true;
const VOYAGE_RERANK_MODEL = "rerank-2-lite";


const USE_CACHE = true; // Usar ou não cache
const CACHE_PATH = __DIR__.'/cache';
const CACHE_LIVE_DAYS = 30;


const ENABLE_EMAIL_LOGGING = true; // Se deseja enviar alerta de erros por e-mail
// Precisa ter as variáveis configuradas em .env
// Para não fazer muitos envios serão enviados no máximo um e-mail a cada 12 horas
// Lembrando que outros erros são registrados na tabela logs do (base MySQL/MariaDB)



