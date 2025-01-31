<?php

namespace App;

use Exception;

class VoyageAI
{
    private string $apiKey;
    private string $apiUrl = 'https://api.voyageai.com/v1/embeddings';

    /**
     * VoyageAI constructor
     *
     * @param string $apiKey Your VoyageAI API key
     */
    public function __construct(string $apiKey = VOYAGE_API_KEY)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Generate embeddings for given text(s)
     *
     * @param string|array $documents Single text string or array of strings
     * @param string $model Model name (e.g., 'voyage-3-large', 'voyage-3', 'voyage-3-lite')
     * @param string $task Task type (document or query): For transparency, the following prompts are prepended to your
     * input. For query, the prompt is "Represent the query for retrieving supporting documents".
     * For document, the prompt is "Represent the document for retrieval"
     * @param array $options Additional options for the API
     * @return array Return an array with the embeddings
     * @throws Exception On invalid input or API error
     */
    public function createEmbeddings(string|array $documents, string $model, string $task = 'document', array $options = []):array
    {
        if (empty($documents)) {
            throw new \Exception('Param \$documents cannot be empty');
        }
        $options['input_type'] = $task;
        if(empty($options['output_dimension'])){
            $options['output_dimension'] = VECTOR_DIMENSION;
        }
        $all_embeddings = [];
        if (is_array($documents)) {
            $total_documents = count($documents);
            $max = 128;
            while ($total_documents > $max) {
                // Por padrão a API aceita no máximo 128 documentos em batch
                // Caso a function embed receba um array com mais de 128 documentos será enviado em partes até que todos
                // os documentos seja tornado em embeddings
                $doc = array_slice($documents, 0, $max);
                $start = time();
                $new_embeddings = $this->createEmbeddings($doc, $model, $task, $options);
                $duration = time() - $start;
                echo "Voyage levou $duration segundos<br>";
                $all_embeddings = array_merge($all_embeddings, $new_embeddings);
                $documents = array_slice($documents, $max);
                $total_documents = count($documents);
            }
        }


        // Prepare request data
        $data = array_merge([
            'input' => $documents,
            'model' => $model
        ], $options);

        // Make API request
        $data = $this->makeRequest($data)['data'] ?? null;
        if(!is_array($data)) {
            throw new \Exception('VoyageAI API returned invalid data');
        }
        foreach ($data as $item) {
            $all_embeddings[] = $item['embedding'];
        }
        return $all_embeddings;
    }


    /**
     * Make HTTP request to VoyageAI API
     *
     * @param array $data Request payload
     * @return array|null Response data or null on error
     * @throws Exception On API error
     */
    private function makeRequest(array $data): ?array
    {
        $ch = curl_init($this->apiUrl);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($statusCode !== 200) {
            throw new \Exception('API request failed with status code: ' . $statusCode);
        }

        return json_decode($response, true);
    }

    /**
     * Set custom API URL (for testing or different endpoints)
     *
     * @param string $url New API URL
     * @return void
     */
    public function setApiUrl(string $url): void
    {
        $this->apiUrl = $url;
    }
}
