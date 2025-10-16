<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\Commands;

use App\Domain\Shared\Search\SearchEngineInterface;
use App\Infrastructure\Eloquent\Product;
use Illuminate\Console\Command;

class ElasticsearchIndexCommand extends Command
{
    protected $signature = 'elasticsearch:index
                            {--recreate : Drop and recreate the index}';

    protected $description = 'Index all products in Elasticsearch';

    public function __construct(
        private readonly SearchEngineInterface $elasticsearch
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('recreate')) {
            $this->info('Creating Elasticsearch index...');
            $this->elasticsearch->createIndex();
        }

        $this->info('Indexing products using bulk API...');

        $totalProducts = Product::count();
        $bar = $this->output->createProgressBar($totalProducts);
        $bar->start();

        Product::chunk(500, function ($products) use ($bar) {
            $documents = [];

            foreach ($products as $product) {
                $documents[$product->id] = [
                    'name' => $product->name,
                    'description' => $product->description,
                    'category' => $product->category,
                    'brand' => $product->brand,
                    'price' => (float)$product->price,
                    'stock' => $product->stock,
                    'created_at' => $product->created_at?->toIso8601String(),
                ];
            }

            // Single bulk request for 500 products
            $result = $this->elasticsearch->bulkIndex($documents);
            if (!$result) {
                $this->error('Bulk indexing failed! Check logs.');
                return self::FAILURE;
            }
            $bar->advance(count($documents));
        });

        $bar->finish();
        $this->newLine();
        $this->info("{$totalProducts} products indexed successfully using bulk API!");

        return self::SUCCESS;
    }
}
