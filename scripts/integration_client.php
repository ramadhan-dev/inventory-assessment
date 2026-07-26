#!/usr/bin/env php
<?php

/**
 * Integration Client for Inventory Management API
 * 
 * This script demonstrates how to:
 * 1. Obtain API token (or accept as argument)
 * 2. Fetch all products where category = "finished_goods"
 * 3. For each product, record a stock-out of 10 units to warehouse_id = 1
 * 4. Handle errors gracefully with retry logic
 */

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class IntegrationClient
{
    private string $apiUrl;
    private string $apiToken;
    private Client $httpClient;
    private string $logFile;

    public function __construct(string $apiUrl, string $apiToken)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiToken = $apiToken;
        $this->httpClient = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => "Bearer {$this->apiToken}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);
        $this->logFile = __DIR__ . '/integration_client.log';
    }

    /**
     * Log error with timestamp
     */
    private function logError(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] ERROR: {$message}\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo $logMessage;
    }

    /**
     * Log info with timestamp
     */
    private function logInfo(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] INFO: {$message}\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo $logMessage;
    }

    /**
     * Make API request with retry logic
     */
    private function makeRequest(string $method, string $endpoint, array $data = [], int $maxRetries = 1): ?array
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $maxRetries) {
            try {
                $response = $this->httpClient->request($method, $endpoint, [
                    'json' => $data,
                ]);

                return json_decode($response->getBody()->getContents(), true);
            } catch (RequestException $e) {
                $lastException = $e;
                $statusCode = $e->getCode();

                // Retry on 429 (rate limit) or 500 (server error)
                if (in_array($statusCode, [429, 500]) && $attempt < $maxRetries) {
                    $attempt++;
                    $backoffTime = pow(2, $attempt); // Exponential backoff: 2s, 4s
                    $this->logError("Request failed with status {$statusCode}. Retrying in {$backoffTime}s (attempt {$attempt}/{$maxRetries})");
                    sleep($backoffTime);
                    continue;
                }

                // Log error and return null for other errors or after max retries
                $this->logError("Request failed: {$e->getMessage()}");
                return null;
            } catch (\Exception $e) {
                $this->logError("Unexpected error: {$e->getMessage()}");
                return null;
            }
        }

        return null;
    }

    /**
     * Fetch all products with category = "finished_goods"
     */
    public function getFinishedGoodsProducts(): ?array
    {
        $this->logInfo("Fetching finished goods products...");

        $response = $this->makeRequest('GET', '/api/v1/products', [
            'category' => 'finished_goods',
            'per_page' => 100, // Fetch more per page
        ]);

        if ($response && isset($response['data'])) {
            $this->logInfo("Fetched " . count($response['data']) . " finished goods products");
            return $response['data'];
        }

        $this->logError("Failed to fetch finished goods products");
        return null;
    }

    /**
     * Record stock-out for a product
     */
    public function recordStockOut(array $product, int $warehouseId = 1, int $quantity = 10): bool
    {
        $this->logInfo("Recording stock-out for product SKU: {$product['sku']} ({$product['name']})");

        $response = $this->makeRequest('POST', '/api/v1/stock-movements', [
            'product_sku' => $product['sku'],
            'warehouse_id' => $warehouseId,
            'movement_type' => 'out',
            'quantity' => -$quantity, // Negative for stock-out
            'reference_number' => 'INTEGRATION-' . date('YmdHis'),
            'notes' => 'Automated stock-out via integration client',
            'moved_by' => 'integration_client',
        ]);

        if ($response && isset($response['success']) && $response['success']) {
            $this->logInfo("Successfully recorded stock-out for SKU: {$product['sku']}");
            return true;
        }

        $this->logError("Failed to record stock-out for SKU: {$product['sku']}");
        return false;
    }

    /**
     * Run the integration process
     */
    public function run(): void
    {
        $this->logInfo("Starting integration process...");

        // Fetch finished goods products
        $products = $this->getFinishedGoodsProducts();

        if (!$products || empty($products)) {
            $this->logError("No products found or failed to fetch products");
            return;
        }

        $successCount = 0;
        $failureCount = 0;

        // Record stock-out for each product
        foreach ($products as $product) {
            if ($this->recordStockOut($product)) {
                $successCount++;
            } else {
                $failureCount++;
                // Continue with remaining products even if one fails
            }
        }

        $this->logInfo("Integration process completed. Success: {$successCount}, Failures: {$failureCount}");
    }
}

// Main execution
if ($argc < 3) {
    echo "Usage: php integration_client.php <api_url> <api_token>\n";
    echo "Example: php integration_client.php http://localhost:8000 your-api-token-here\n";
    exit(1);
}

$apiUrl = $argv[1];
$apiToken = $argv[2];

try {
    $client = new IntegrationClient($apiUrl, $apiToken);
    $client->run();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
