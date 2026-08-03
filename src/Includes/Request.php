<?php

namespace Websyspro\Server\Includes;

use Websyspro\Server\Includes\Enums\RequestMethod;
use function explode;
use function parse_str;
use function json_decode;
use function str_contains;
use function strtolower;
use function strtoupper;
use function trim;
use function parse_url;
use function preg_split;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function substr;
use function strpos;
use function strlen;
use function rtrim;
use function strip_tags;
use function filter_var;
use function is_string;
use function is_array;
use function is_object;
use function array_map;
use function sprintf;
use const PHP_URL_PATH;
use const PHP_URL_QUERY;
use const FILTER_SANITIZE_SPECIAL_CHARS;

class Request
{
	public readonly string $method;
	public readonly string $path;
	public readonly string $rawBody;
	public readonly array  $headers;
	public readonly array  $query;
	public array           $params;
	public readonly mixed  $body;
	public readonly array  $files;

	public function __construct(
		array $parsed,
		array $params = [],
		array $query  = []
	){
		$parts = explode(" ", trim($parsed["firstLine"]));

		$this->method = strtoupper(
			$parts[0] ?? RequestMethod::GET->value
		);

		$fullPath = $parsed["firstLine"] !== ""
			? (explode(" ", $parsed["firstLine"])[1] ?? "/")
			: "/";

		$this->path = parse_url($fullPath, PHP_URL_PATH) ?? "/";

		$this->headers = $this->sanitizeHeaders($parsed["headers"]);
		$this->rawBody = $parsed["body"];
		$this->params  = $this->sanitizeArray($params);

		$queryString = parse_url($fullPath, PHP_URL_QUERY) ?? "";
		parse_str($queryString, $query);
		$this->query = $this->sanitizeArray($query);

		$contentType = strtolower($this->headers["content-type"] ?? "");

		// Modo Apache: multipart já foi processado pelo PHP internamente.
		// php://input fica vazio — recebe $_POST e $_FILES via $parsed.
		if (isset($parsed["apachePost"])) {
			$this->body  = $this->sanitizeArray($parsed["apachePost"]);
			$this->files = $this->normalizeApacheFiles($parsed["apacheFiles"] ?? []);
		} elseif (str_contains($contentType, "application/json")) {
			$decoded    = json_decode($this->rawBody);
			$this->body = $this->sanitizeValue($decoded);
			$this->files = [];
		} elseif (str_contains($contentType, "application/x-www-form-urlencoded")) {
			$body = [];
			parse_str($this->rawBody, $body);
			$this->body  = $this->sanitizeArray($body);
			$this->files = [];
		} elseif (str_contains($contentType, "multipart/form-data")) {
			[$body, $files] = $this->parseMultipart($this->rawBody, $contentType);
			$this->body  = $this->sanitizeArray($body);
			$this->files = $files;
		} else {
			$this->body  = null;
			$this->files = [];
		}
	}

	// -------------------------------------------------------------------------

	private function sanitizeArray(
		array $data
	): array {
		return array_map(fn($value) => $this->sanitizeValue($value), $data);
	}

	private function sanitizeHeaders(
		array $headers
	): array {
		$clean = [];
		foreach ($headers as $key => $value) {
			$cleanKey         = preg_replace("#[^\w\-]#", "", $key);
			$clean[$cleanKey] = $this->sanitizeString($value);
		}
		return $clean;
	}

	private function sanitizeValue(
		mixed $value
	): mixed {
		if (is_string($value)) return $this->sanitizeString($value);
		if (is_array($value))  return $this->sanitizeArray($value);

		if (is_object($value)) {
			foreach ($value as $key => $val) {
				$value->$key = $this->sanitizeValue($val);
			}
			return $value;
		}

		return $value;
	}

	private function sanitizeString(
		string $value
	): string {
		$value = preg_replace("#\0#", "", $value);
		$value = strip_tags($value);
		$value = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
		return trim($value);
	}

	private function normalizeApacheFiles(
		array $files
	): array {
		$normalized = [];
		foreach ($files as $field => $file) {
			$normalized[$field] = [
				'name'    => $file['name'],
				'type'    => $file['type'],
				'content' => file_get_contents($file['tmp_name']),
				'size'    => $file['size'],
			];
		}
		return $normalized;
	}

	public function setParams(array $params): Request
	{
		$this->params = $this->sanitizeArray($params);
		return $this;
	}

	private function parseMultipart(
		string $body,
		string $contentType,
		array  $fields = [],
		array  $files  = []
	): array {
		preg_match("#boundary=(.+)$#", $contentType, $matches);
		if (empty($matches[1])) {
			return [$fields, $files];
		}

		$boundary = sprintf("--%s", trim($matches[1]));
		$parts    = preg_split("/" . preg_quote($boundary, "/") . "/", $body);

		array_shift($parts);
		array_pop($parts);

		foreach ($parts as $part) {
			if (empty(trim($part))) continue;

			$headerEnd   = strpos($part, "\r\n\r\n");
			$partHeaders = substr($part, 0, $headerEnd);
			$partBody    = rtrim(substr($part, $headerEnd + 4), "\r\n");

			preg_match("#name=\"([^\"]+)\"#",              $partHeaders, $nameMatch);
			preg_match("#filename=\"([^\"]*)\"#",           $partHeaders, $fileMatch);
			preg_match("#Content-Type:\s*([^\r\n]+)#i",    $partHeaders, $typeMatch);

			$fieldName = $nameMatch[1] ?? null;
			if ($fieldName === null) continue;

			if (!empty($fileMatch[1])) {
				$files[$fieldName] = [
					"name"    => $fileMatch[1],
					"type"    => trim($typeMatch[1] ?? "application/octet-stream"),
					"content" => $partBody,
					"size"    => strlen($partBody),
				];
			} else {
				$fields[$fieldName] = $partBody;
			}
		}

		return [$fields, $files];
	}
}
