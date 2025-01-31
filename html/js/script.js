let videos_lists = [];
let idx = 0;
let canonical = "";
let chave = canonical;

// retorna um (int) do último index que representa o número de vídeos já foi visto anteriormente
function previousIndex(chave) {
    let hasHistory = localStorage.getItem(chave);
    if (hasHistory != null) {
        return parseInt(hasHistory);
    } else {
        return 0; // primeiro vídeo, então index é 0
    }
}

let prev_idx = previousIndex(chave);
if (prev_idx > 0) {
    idx = prev_idx;
    if (idx <= 5) {
        idx = 0;
    }
    console.log('Now idx is: ' + idx)
}


// seta o valor do index que representa o vídeo atual sendo assistido
function setIndex(chave) {
    if (idx < 0) {
        idx = 0;
    }
    if (idx !== videos_lists.length) {
        localStorage.setItem(chave, idx);
        console.log('setting chave: ' + chave + ' for idx: ' + idx);
    } else {
        localStorage.setItem(chave, '0'); // reset
    }
}
let isSearching = false;
function doSearch(url, term = '') {
    if(isSearching){
        //alert("Busca em andamento");
        return false;
    }

    isSearching = true;
    setTimeout(()=>{ isSearching = false;},2000);
    removeInfo();
    removePagination();
    let top = document.querySelector('.taxon_top');
    if(term){
        term = term.split('/')[0]; // remove barra exemplo termo/2 => termo
        top.innerHTML = "<h2 class='taxon_wait'>Aguarde. Busca em andamento!</h2>";
        document.title = 'Aguarde. Buscando por '+term;
    }
    let t_animation = document.querySelector("#taxon_loading_animation");
    if(t_animation != null){
        t_animation.style.display = "block";
    }

    let allResults = document.querySelector(".allResults");
    allResults.innerHTML = '';
    let toggle = document.querySelector(".taxon_toggle");
    toggle.style.display = "block";
    let container = document.querySelector(".taxon_container");
    container.scrollIntoView();
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                if(t_animation != null){
                    t_animation.style.display = "none";
                }
                top.innerHTML = "<h2>Error: Tente novamente.</h2>";
                throw new Error('Erro ao fazer a solicitação: ' + response.status);
            }
            if(t_animation != null){
                t_animation.style.display = "none";
            }
            isSearching = false;
            return response.json();
        })
        .then(data => {
            if (data.results) {
                if(term){
                    top.innerHTML = "<h2>"+term+"</h2>";
                    document.title = term.charAt(0).toUpperCase() + term.slice(1);
                }
                videos_lists = [];
                data.results.forEach(re => {
                    let result = document.createElement("div");
                    result.classList.add("taxon_result");
                    result.innerHTML = '';
                    result.innerHTML +=
                        `<p><b>Menciona:</b> ${re.subtitle}</p>
                         <p><b>Minuto:</b> ${re.showMinute}</p>`;
                    if(re.channel_name){
                        result.innerHTML += `<p><b>Canal:</b> <span class="channel_name">${re.channel_name}</span></p>`;
                    }
                    result.innerHTML += `
                         <p><b>Vídeo:</b>
                         <a target="_blank" href="${re.youtubeFinalURL}">
                              <img alt="YouTube Logo" width="32" height="32" class="taxon_middle" src="${BASE_URL}/imgs/youtube.png">
                         </a>
                         <a target="_blank" href="${re.youtubeFinalURL}">${re.video_title}</a>
                         </p>
                         <p><a target="_blank" href="${re.youtubeFinalURL}"><img alt="${re.video_title}" class="taxon_thumb" src="https://i.ytimg.com/vi/${re.youtube_video}/mqdefault.jpg"></a></p>
                        `;
                    videos_lists.push({videoId: `${re.youtube_video}`, start: `${re.start_seconds}`});
                    allResults.append(result);
                });
                startYouTubeIframe();
                if (idx !== 0) {
                    goNext();
                }
                document.querySelectorAll(".taxon_content a").forEach(a=>{
                    a.onclick = ()=>{
                        player.pauseVideo();
                        
                }});

                let pagination = document.querySelector(".taxon_pagination");
                pagination.innerHTML = '';
                if (data.pager.previousPage) {
                    previousLink = `<a onclick="doSearch('${data.pager.previousURL}')">&#8592; Página ${data.pager.previousPage}</a>&nbsp; `;
                    pagination.innerHTML += previousLink;
                    if (data.pager.currentPage) {
                        pagination.innerHTML += `<span class='taxon_circle'>${data.pager.currentPage}</span>&nbsp; `;
                    }
                }
                if (data.pager.nextPage) {
                    previousLink = `&nbsp;<a id="link_next_page" onclick="doSearch('${data.pager.nextURL}')">&#8594; Página ${data.pager.nextPage}</a>`;
                    pagination.innerHTML += previousLink;
                }
                if(data.pager.canonical){
                       if(typeof BASE_URL != 'undefined'){
                           let c_page = parseInt(data.pager.currentPage);
                           let channel = data.filter_by?.channel ?? '';
                           let new_url = `${BASE_URL}/${data.query}`;
                           if(c_page > 1){
                               new_url += `/${c_page}`;
                           }
                           if(channel){
                               new_url +='?channel='+channel;
                           }
                           history.pushState({ url: new_url }, '', new_url);
                           console.log(new_url)
                       }else {
                           console.warn('defina BASE_URL no index.html')
                       }
                    }
            } else if (data[0].info) {
                document.title = 'Sem resultados para '+term;
                if(typeof(player) != 'undefined'){
                    player.seekTo(9999999);
                }
                
                let cnt = document.querySelector(".taxon_content");
                if (cnt != null) {
                    let divEle = document.createElement('div');
                    divEle.innerHTML = data[0].info;
                    cnt.prepend(divEle);
                    toggle.style.display = 'none';
                   
                }
                
            }
            if(data.canonical){
                chave = btoa(data.canonical);
                idx = previousIndex(chave);
                if(idx <= 5){
                    idx = 0;
                }
                showAlert();
            }

        })
        .catch(error => {
            if(t_animation != null){
                t_animation.style.display = "none";
            }
            top.innerHTML = "<h2>Error: Tente novamente.</h2>";
            console.error('Ocorreu um erro:', error);
        });
}

function removeInfo() {
    let info = document.querySelector(".taxon_info");
    if (info != null) {
        info.remove();
    }
}

function removePagination() {
    let pg = document.querySelector(".taxon_pagination");
    if (pg != null) {
        pg.innerHTML = '';
    }
}

let playVideoUpTo = 90;

function startYouTubeIframe() {
    if (document.querySelector("#yt_iframe") != null) {
        return false;
    }
    let tag = document.createElement('script');
    tag.id = "yt_iframe";
    tag.src = "https://www.youtube.com/iframe_api";
    let firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
}

let player;
function onYouTubeIframeAPIReady() {
    if (videos_lists.length) {
        let videoCode = videos_lists[idx].videoId;
        player = new YT.Player('taxon_player', {
            height: '360',
            width: '640',
            videoId: videoCode,
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
            }
        });
    }

}


function onPlayerReady(event) {
    event.target.playVideo();

}

let done = false;
let passed_time = 0;
let go_next = false;
let hold = true;
let timer;

function onPlayerStateChange(event) {
    if (event.data === YT.PlayerState.PLAYING && !done) {
        done = true;
        console.log('playing');
        passed_time = 0;
        let goTo = videos_lists[idx].start;
        player.seekTo(goTo);
        if (idx < 0) {
            idx = 0;
        }
        idx++;
        setIndex(chave);
        timer = setInterval(function () {
            if (videos_lists.length) {
                if (player.getPlayerState() === 1) {
                    // is playing
                    passed_time += 1; // every 1 second add 1 seconds
                }
                if (go_next || (passed_time > playVideoUpTo && !hold)) {
                    go_next = false; // if true;
                    passed_time = 0;
                    let videoCode = videos_lists[idx].videoId;
                    let startSeconds = videos_lists[idx].start;
                    console.log('new: ' + idx);
                    player.loadVideoById(videoCode, startSeconds);
                    setIndex(chave);
                    idx++;
                }

                if (idx >= (videos_lists.length - 1)) {
                    let old_idx = idx;
                    localStorage.setItem(chave, 0); // reset
                    idx = old_idx; // to not start to play from first again

                }

            } else {
                // never get here // old code
                clearInterval(timer);
                setTimeout(() => {
                    player.pauseVideo();
                }, (1000 * playVideoUpTo));
            }
        }, 1000);
    }
}

function autoSkip(){
    let check = document.querySelector("#taxon_hold");
    if (check != null) {
        check.addEventListener('change', (event) => {
            hold = !event.target.checked;
        });
    }
}

function goBack() {
    if (idx > 0) {
        console.log('idx>' + idx);
        idx -= 2;
        if (idx < 0) {
            idx = 0;
        }
        console.log('idx>' + idx);
        goNext();
    }
}

function goNext() {
  if(idx >= videos_lists.length  && videos_lists.length > 1){
      let lnp = document.querySelector("#link_next_page");
      if(lnp != null){
          lnp.click();
      }
    return false;
  }
 go_next = true;
}

function toggleHold() {
    hold = !hold;
}

function showAlert(){
    let alertMsg = document.createElement('div');
    alertMsg.id = 'taxon_alert';
    alertMsg.innerHTML = `<p>Você já viu até o vídeo ${idx}</p>
    <p>Deseja continuar de onde parou e ir para o vídeo ${idx}?</p>
    <p>
        <button onClick="closeAlert()">Sim</button>
        <button onClick="resetIndex();">Não</button>
    </p>
    <p class="taxon_medium">Se selecionar <b>não</b> voltará para o primeiro vídeo.</p>`;
    if (idx > 5) {
        document.body.prepend(alertMsg);
    }
}

function closeAlert() {
    document.querySelector('#taxon_alert').remove();
}

function resetIndex() {
    closeAlert();
    idx = 0;
    setIndex(chave);
    passed_time = 0;
    let videoCode = videos_lists[idx].videoId;
    let startSeconds = videos_lists[idx].start;
    player.loadVideoById(videoCode, startSeconds);

}
