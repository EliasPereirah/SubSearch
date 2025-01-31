<?php
namespace App;
use Exception;

class VoyageReranker
{
    private string $apiKey;
    private string $apiUrl = 'https://api.voyageai.com/v1/rerank';

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
     * @param array $documents The documents to be reranked as a list of strings
     * @param string $query The query as a string
     * @param string $model Name of the model. Recommended options: rerank-2, rerank-2-lite (01/2025)
     * @param bool $returnDocs Whether to return the documents in the response. Defaults to false.
     * @return array Return an array with the embeddings
     * @throws Exception On invalid input or API error
     */
    public function rerank(array $documents, string $query, string $model, bool $returnDocs = false):array
    {
        if (empty($documents)) {
            throw new \Exception('Param \$documents cannot be empty');
        }

        $post_data = ['documents' => $documents, 'query' => $query, 'model' => $model, 'return_documents' => $returnDocs];

        // Make API request
        $response = $this->makeRequest($post_data)['data'] ?? null;
        if(!is_array($response)) {
            throw new \Exception('VoyageAI API returned invalid data');
        }

        return $response;
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
     * Set  API URL
     * @param string $url New API URL
     * @return void
     */
    public function setApiUrl(string $url): void
    {
        $this->apiUrl = $url;
    }
}
