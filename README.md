# SubSearch
Já imaginou se você pudesse indexar as legendas dos canais do YouTube em uma base de dados e depois poder realizar uma 
busca nesse base?

Já imaginou poder encontrar o momento exato em que uma palavra ou frase foi dita nesses vídeos?

Isto é o que este projeto faz.

![Print - Screenshot](https://raw.githubusercontent.com/EliasPereirah/SubSearch/main/html/imgs/screenshot.png)

# Instalação
- Esse projeto usa o "banco de dados" Typesense 
Para baixar, confira a documentação oficial: https://typesense.org/docs/guide/install-typesense.html#option-2-local-machine-self-hosting

Obs: Se for fazer a instalação diretamente no OS sua chave API pode ser vista após instalação com o comando: `cat /etc/typesense/typesense-server.ini`
- Clone este repositório: `git clone https://github.com/EliasPereirah/SubSearch`
- No terminal execute: `composer update`

# Configuração
- Renomeei `env.example` para `.env` e coloque as informações necessárias
  - Obrigatório: `YOUTUBE_API_KEY` e `VOYAGE_API_KEY` (SendGrid é opcional)
  - Importe o aquivo SQL `subsearch.sql` disponibilizado neste repo em um banco de dados MySQL/MariaDB
  - Já no arquivo `config.php` configure a API_KEY para a constante `TS_API_KEY` e dados de acesso ao MySQL/MariaDB

# API Keys
Obtendo YOUTUBE_API_KEY - Veja como obter uma chave API para YouTube neste vídeo do YouTube :) : https://www.youtube.com/watch?v=uz7dY8qTFJw



Obtendo VOYAGE_API_KEY - Cadastre se na VoyageAI e obtenha sua chave API aqui https://dashboard.voyageai.com/api-keys

Ao cadastrar-se na VoyageAI terá acesso até 200 milhões de tokens gratuitos (único)

Nota: VoyageAI será usada tanto para geração de embeddings permitindo busca vetorial quanto para rerank melhorando a 
qualidade dos resultados da busca.

# Como Usar
- No primeiro uso execute arquivo `dev_use.php` para que o script defina os schemas da collections no TypeSense

- Faça login acessando o diretório /dash via navegador 
   - Dados de login:
     
   - Senha: `password`
     
   - email: `email@localhost`
  
  *No primeiro login você será solicitado a mudar senha e email

- Adicione os canais do YouTube que desejar indexar (basta informar o ID qualquer video do canal)

# Indexando o conteúdo
- Após fazer a etapa acima você deve configurar um serviço cron para periodicamente adicionar os vídeos dos canais 
à base de dados
- Para isso adicione um cronjob que execute `extract_captions.php` de tempos em tempos
- ex: `*/1 * * * * php /path/extract_captions.php` (recomendo que antes execute manualmente para testar se está funcionado adequadamente)

Nota: Serão indexados os vídeos encontrados na collection video_data, novos vídeos que o canal postar não serão 
automaticamente indexados.

Para indexar novos vídeos do canal você poderá acessar /dash novamente e inserir novamente pelo menos um ID do canal 
que desejar indexar, isso vai adicionar novos vídeos do canal à collection video_data para posteriormente serem indexados pelo script `extract_captions.php`

# Recomendações
Deixe apenas o conteúdo da pasta html exposto para web caso vá hospedar o projeto online


