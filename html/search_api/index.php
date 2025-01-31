<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
use App\TypeSearch;
require_once __DIR__ . "/../../bootstrap.php";
$TypeSearch = new TypeSearch();
$SmartAction = new App\SmartAction();
$Rerank = new App\VoyageReranker(VOYAGE_API_KEY);
$Logger = new App\Logger();
$Cache = new App\Cache();
$start = time();
$max_results = 100;
$search_url = BASE_URL."/search_api";
$input = $_GET['term'] ?? '';
$channel_id = $_GET['channel'] ?? '';
$channel_id = $SmartAction->safeChannel($channel_id);
$channel_url_part = "";
if ($channel_id) {
    $channel_url_part = "?channel=" . rawurlencode($channel_id);
}
$term = $SmartAction->getTerm($input);
$term = strip_tags($term);
$term = str_replace(["<",">"], "", $term);
$encoded_term = rawurlencode($term);
$nextPage = $SmartAction->getNextPage($input); // retorna no máximo o número 101
$current_page = $nextPage - 1;
$canonical = "$search_url/$term";
$pager = [];
if ($current_page > 1) {
    $previous_page = $current_page - 1;
    $canonical .= "/$current_page";
    if ($previous_page > 1) {
        $pager['previousURL'] = "$search_url/$term/$previous_page{$channel_url_part}";
    } else {
        // primeira página - página 1
        $pager['previousURL'] = "$search_url/$term{$channel_url_part}";
    }
    $pager['previousPage'] = $previous_page;

}
// limita a paginação até 100
if ($nextPage <= 100) {
    $pager['nextPage'] = $nextPage;
    $pager['nextURL'] = "$search_url/$term/$nextPage{$channel_url_part}";
}
$canonical .= "{$channel_url_part}";
$pager['canonical'] = $canonical;

$pager['currentPage'] = $current_page;
$can_do_cache = USE_CACHE;

$cache_id = "$term/$channel_id/$current_page";
if (empty($term)) {
    $obj = ['message' => "Nenhum termo de busca foi recebido!"];
    exit(json_encode($obj));
}

if (USE_CACHE) {
    if ($cache_cnt = $Cache->getCache($cache_id)) {
        $SmartAction->addStats($term, 1);
        exit($cache_cnt);
    }
}

try {
    $response = $TypeSearch->search($term, HYBRID_SEARCH, $channel_id, $current_page);

} catch (Exception|\Http\Client\Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    $erros = ['msg' => "Houve um erro. Tente novamente!", 'error' => true];
    echo json_encode($erros);
    $Logger->register($e->getMessage());
    exit();
}
$can_do_cache = $TypeSearch->canDoCache(); // se hybrid search estiver ativado, mas acontecer erro a busca for realizada
// apenas com full text retornará false, ou seja, para não fazer cache


$hits = $response['results'][0]['hits'] ?? $response['hits'] ?? [];
// a key results só é retornando quando a busca é feita com hybrid = true

$found_results = $response['found'] ?? $response['results'][0]['found'] ?? 0;
$all_results = [];
$delay = 8; // volta 8 segundo no vídeo para dar maior contexto.
$idx = 0;
$reranked_results = [];
$all_results_text = [];
foreach ($hits as $item) {
    $all_results_text[] = $item['document']['sub'];
}

if(DO_RERANK && !empty($hits)){
    try {
        $indexes = $Rerank->rerank($all_results_text, $term, VOYAGE_RERANK_MODEL);
        foreach ($indexes as $item) {
            $the_hit = $hits[$item['index']];
            $the_hit['rerank_score'] = $item['relevance_score'];
            $the_hit['before_rerank_position'] = $item['index'];
            $reranked_results[] = $the_hit;

        }
        $hits = $reranked_results;
    } catch (Exception $e) {
        $Logger->register("Rerank error: ".$e->getMessage());
        $can_do_cache = false;
    }
}

foreach ($hits as $item) {
    $doc = $item['document'];
    $time_component = $SmartAction->getTimeComponents($doc['ts']);
    $start_seconds = $SmartAction->timeToSeconds($time_component[0], $time_component[1], $time_component[2]);
    $start_seconds -= $delay;
    $video_title = $doc['video_data']['title'];
    if ($start_seconds < 0) {
        $start_seconds = 0;
    }
    $subtitle_part = $doc['sub'];
    $highlight_snippet = $item['highlight']['sub']['snippet'] ?? ''; // nem sempre vai haver highlight

    $subtitle_part = $SmartAction->doHighlight($subtitle_part, $highlight_snippet);

    $channel_name = $doc['video_data']['channel_name'];
    $showMinute = $time_component[3];
    $all_results[$idx]['showMinute'] = $showMinute;
    $all_results[$idx]['start_seconds'] = $start_seconds;
    $all_results[$idx]['subtitle'] = $subtitle_part;
    $all_results[$idx]['channel_name'] = $channel_name;
    $all_results[$idx]['video_title'] = $video_title;
    $video_id = $doc['video_data']['video_id'];
    $all_results[$idx]['youtube_video'] = $video_id;
    $all_results[$idx]['youtubeFinalURL'] = "https://youtube.com/watch/?v=$video_id&t={$start_seconds}s";

    if(isset($item['rerank_score'])){
        $all_results[$idx]['rerank_score'] = $item['rerank_score'];
        $all_results[$idx]['before_rerank_position'] = $item['before_rerank_position'];

    }
    if(isset($item['vector_distance'])){
        $all_results[$idx]['vector_distance'] = $item['vector_distance'];
    }
    $idx++;

}

$data = [];
if ($found_results == 0) {
    $safe_term = strip_tags($term);
    $search_tip = '';
    if ($channel_id) {
        $search_tip = "<p>Experimente buscar em todos os canais!</p>";
    }
    $data[]['info'] = "<div class='taxon_info'><div class='e404'><h3>Sem resultados para <b>$safe_term</b></h3></div>$search_tip</div>";
} else {
    if ($found_results <= ($max_results * $current_page)) {
        unset($pager['nextPage']);
        unset($pager['nextURL']);
    }
    $data['pager'] = $pager;
    $data['results'] = $all_results;
    $data['found_results'] = $found_results;
    $data['total_results_this_page'] = count($all_results);
    $data['canonical'] = $canonical;
    $data['query'] = $term;
    if($channel_id){
        $data['filter_by'] = ['channel' => $channel_id];
    }

}
$data['error'] = false;
$content = json_encode($data);
if ($can_do_cache) {
    $Cache->saveCache($cache_id, $content);
}
$SmartAction->addStats($term, 0);
echo $content;