<?php
require_once __DIR__ . "/../config.php";
$term = $_GET["term"] ?? '';
$term = str_replace(["`","´"], "", $term); // evita injeção JS já que o termo é passado entre acento grave (`) em script mais abaixo
$term = strip_tags($term);
$term = str_replace(["<",">"], "", $term);
$term = trim($term, "/");
$term = trim($term);

preg_match("/[a-z0-9]\/([0-9]+)$/i", $term, $match_page);
$page = $match_page[1] ?? "";
$page = (int) $page;
if($page < 1){
    $page = "";
}
$term = preg_replace("/\/(.*)/","", $term); // remove tudo depois de barra

$channel_id = $_GET["channel"] ?? "";
$channel_id = strip_tags($channel_id);
$channel_id = preg_replace("/[^a-zA-Z-_0-9]/u", '', $channel_id);
$robots = "index, follow";
if($term !== ""){
    // Se desejar indexação APENAS para a página inicial
    // $robots = "noindex, nofollow";
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title><?= SITE_NAME ?></title>
    <meta name="robots" content="<?= $robots ?>">
    <meta name="description" content="<?= SITE_DESCRIPTION ?>">
    <meta name="viewport" content="width=device-width">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= CSS_VERSION ?>">
    <link rel="canonical" href="<?= BASE_URL ?>"/>
    <link rel="shortcut icon" type="image/png" sizes="96x96" href="<?= BASE_URL ?>/favicon.png">
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= SITE_NAME ?>">
    <meta property="og:description" content="<?= SITE_DESCRIPTION ?>">
    <meta property="og:image" content="<?= BASE_URL ?>/imgs/og_img.jpg">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="<?= BASE_URL ?>">
    <script src="<?= BASE_URL ?>/js/script.js?v=<?= JS_VERSION ?>"></script>
    <script>
        let BASE_URL = "<?= BASE_URL ?>";
    </script>
</head>
<body>
<div class="taxon_mount">
    <div class="taxon_container">
        <div class="taxon_application">
            <form id="taxon" method="get">
                <div class="app_input">
                    <label>
                        <input required="required" type="text" name="term" value="" placeholder="Fazer uma busca">
                    </label>
                    <select id="sl_channel" name="channel">
                         <optgroup id="list_channels" label="Busca por canais">
                             <option value="all">Todos Canais</option>
                         </optgroup>
                    </select>
                    <button class="bg_light_green light_dark">Buscar</button>
                </div>
            </form>
        </div>
        <div class="taxon_content">
            <div id="taxon_loading_animation">
                <div class="taxon_spinner"></div>
            </div>

            <div class="taxon_toggle">
                <div class="taxon_top"></div>
                <div class="taxon_btns">
                    <div class="bg_light_green light_dark prev_next_btn" onclick="goBack();">Anterior</div>
                </div>
                <div class="ytiframe">
                    <div id="taxon_player"></div>
                </div>
                <div class="taxon_btns">
                    <div id="taxon_pause">
                        <span class="taxon_medium">Auto Skip</span>
                        <label class="taxon_switch">
                            <input id="taxon_hold" onclick="autoSkip();" type="checkbox">
                            <span class="taxon_slider taxon_round"></span>
                        </label>
                    </div>
                    <div class="bg_light_green light_dark prev_next_btn" onclick="goNext();">Próximo</div>
                </div>
            </div>
            <div class="allResults">
                <div class="no_search_block">
                    <div class="no-search"><h1><?= SITE_NAME ?></h1></div>
                    <div class="no-search"><?= SITE_DESCRIPTION ?></div>
                </div>
            </div>
        </div>
        <div class="taxon_pagination"></div>
    </div>
</div>
<script>
    let api_url = "<?= SEARCH_ENDPOINT ?>";
    let page = "<?= $page ?>";
    let channel_from_url = "<?= $channel_id ?>";
    document.querySelector("button.bg_light_green").onclick = () => {
        document.querySelector("form#taxon").onsubmit = e => {
            e.preventDefault();
            let input_query = document.querySelector("input[name=term]").value.trim();
            let sl_channel = document.querySelector("select[name='channel']").value.trim();
            if(sl_channel === 'all'){
                sl_channel = '';
            }
            if(channel_from_url){
                sl_channel = channel_from_url;
                // Só irá entrar aqui se uma url for passada diretamente no navegador
                // Não irá sobrescrever channel em select uma vez que o usuário ainda não terá interagido com a página
            }
            let search_endpoint = `${api_url}/${input_query}`;
            if(page){
                search_endpoint += `/${page}`;
            }
            if(sl_channel){
                search_endpoint +=`?channel=${sl_channel}`;
            }
            doSearch(search_endpoint, input_query);
            page = ""; // clean
            channel_from_url = ""; // clean
        }
    }
    let auto_search = `<?= $term ?>`;
    if(auto_search){
        // quando uma página de busca é acessada diretamente
        document.querySelector("input[name=term]").value = auto_search;
        document.querySelector("button.bg_light_green").click(); // inicia busca
    }
</script>
<script>
    let list_channels = document.querySelector("#list_channels");
    let all_options = '';
    fetch('<?= BASE_URL ?>/channels.json')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao carregar lista de autores');
            }
            return response.json();
        })
        .then(data => {
            data.channels.forEach(channel=>{
                let channel_name = channel.name;
                let channel_id = channel.id;
                all_options += `<option value="${channel_id}">${channel_name}</option>`;
            });
            list_channels.innerHTML += all_options;
        })
        .catch(error => {
            console.error('Houve um erro:', error);
        });
</script>
</body>
</html>