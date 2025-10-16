<?php

declare(strict_types=1);

namespace App\Infrastructure\Search;

use App\Domain\Shared\Search\SearchEngineInterface;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Log;

/**
 * Elasticsearch Client
 * 
 * Wrapper around official Elasticsearch PHP client
 * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html
 */
class ElasticsearchClient implements SearchEngineInterface
{
    private Client $client;
    private string $index;

    public function __construct()
    {
        $host = config('services.elasticsearch.host', 'elasticsearch');
        $port = config('services.elasticsearch.port', '9200');
        
        $this->client = ClientBuilder::create()
            ->setHosts(["http://{$host}:{$port}"])
            ->build();
            
        $this->index = config('services.elasticsearch.index', 'products');
    }

    public function indexDocument(string $id, array $document): bool
    {
        try {
            $this->client->index([
                'index' => $this->index,
                'id' => $id,
                'body' => $document,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Elasticsearch indexing failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function search(array $query): array
    {
        try {
            $response = $this->client->search([
                'index' => $this->index,
                'body' => $query,
            ]);

            return $response['hits']['hits'] ?? [];
        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function deleteDocument(string $id): bool
    {
        try {
            $this->client->delete([
                'index' => $this->index,
                'id' => $id,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Elasticsearch delete failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function createIndex(): bool
    {
        try {
            // Delete if exists
            if ($this->client->indices()->exists(['index' => $this->index])->asBool()) {
                $this->client->indices()->delete(['index' => $this->index]);
            }

            // Create with mapping
            $this->client->indices()->create([
                'index' => $this->index,
                'body' => [
                    'settings' => [
                        'analysis' => [
                            'analyzer' => [
                                'turkish' => [
                                    'type' => 'standard',
                                    'stopwords' => '_turkish_',
                                ],
                            ],
                        ],
                    ],
                    'mappings' => [
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                                'analyzer' => 'turkish',
                            ],
                            'description' => [
                                'type' => 'text',
                                'analyzer' => 'turkish',
                            ],
                            'category' => [
                                'type' => 'keyword',
                            ],
                            'brand' => [
                                'type' => 'keyword',
                            ],
                            'price' => [
                                'type' => 'float',
                            ],
                            'stock' => [
                                'type' => 'integer',
                            ],
                            'created_at' => [
                                'type' => 'date',
                            ],
                        ],
                    ],
                ],
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Elasticsearch index creation failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Bulk index documents (much faster for large datasets)
     */
    public function bulkIndex(array $documents): bool
    {
        try {
            $params = ['body' => []];

            foreach ($documents as $id => $document) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $this->index,
                        '_id' => $id,
                    ],
                ];
                $params['body'][] = $document;
            }

            $this->client->bulk($params);
            return true;
        } catch (\Exception $e) {
            Log::error('Elasticsearch bulk indexing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Also dump for debugging
            dump('Elasticsearch Error: ' . $e->getMessage());
            return false;
        }
    }
}
