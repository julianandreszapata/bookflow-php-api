<?php

declare(strict_types=1);

use BookFlow\Application\LibraryService;
use BookFlow\Domain\BusinessRuleException;
use BookFlow\Infrastructure\JsonFileLibraryRepository;

require dirname(__DIR__) . '/vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$repository = new JsonFileLibraryRepository(dirname(__DIR__) . '/storage/bookflow.json');
$service = new LibraryService($repository);
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$body = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($body)) $body = [];

try {
    if ($method === 'GET' && $path === '/api/health') respond(200, ['status' => 'ok', 'service' => 'bookflow-php-api']);
    if ($method === 'GET' && $path === '/api/books') respond(200, $service->listBooks());
    if ($method === 'POST' && $path === '/api/books') respond(201, $service->createBook($body));
    if ($method === 'GET' && $path === '/api/loans') respond(200, $service->listLoans());
    if ($method === 'POST' && $path === '/api/loans') respond(201, $service->createLoan($body));
    if ($method === 'PATCH' && preg_match('#^/api/loans/([a-f0-9]+)/return$#i', $path, $matches)) {
        respond(200, $service->returnLoan($matches[1]));
    }
    respond(404, ['error' => 'Route not found.']);
} catch (BusinessRuleException $exception) {
    respond(400, ['error' => $exception->getMessage()]);
} catch (OutOfBoundsException $exception) {
    respond(404, ['error' => $exception->getMessage()]);
} catch (Throwable $exception) {
    error_log((string)$exception);
    respond(500, ['error' => 'Unexpected server error.']);
}

function respond(int $status, mixed $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}
