<?php

namespace Websyspro\Server\Includes;

use Websyspro\Server\Commons\Collection;
use Websyspro\Server\Includes\Enums\Server\HttpStatus;
use Websyspro\Server\Includes\Exceptions\HttpException;
use function json_encode;
use function sprintf;
use function strlen;

class Response
{
  public function __construct(
    public readonly int $status = 200, 
    public readonly string $body = "", 
    public readonly array $headers = []
  ){}

  public static function json(
    mixed $data, 
    HttpStatus $status = HttpStatus::Ok
  ): Response {
    $envelope = json_encode([
      "success" => $status->value >= 200 && $status->value < 300,
      "content" => $data,
    ]);

    return new Response( $status->value, $envelope, [
      "Content-Type" => "application/json",
    ]);
  }

  public static function text(
    string $text,
    HttpStatus $status = HttpStatus::Ok
  ): Response {
    return new Response( $status->value, $text, [
      "Content-Type" => "text/plain",
    ]);
  }

  public static function html(
    string $html,
    HttpStatus $status = HttpStatus::Ok
  ): Response {
    return new Response( $status->value, $html, [
      "Content-Type" => "text/html; charset=utf-8",
    ]);
  }

  public static function ok(
    mixed $data = null
  ): Response {
    return Response::json(
      $data, HttpStatus::Ok
    );
  }

  public static function created(
    mixed $data = null
  ): Response {
    return Response::json(
      $data, HttpStatus::Created
    );
  }

  public static function accepted(
    mixed $data = null
  ): Response {
    return Response::json(
      $data, HttpStatus::Accepted
    );
  }

  public static function noContent(
  ): Response {
    return new Response(
      HttpStatus::NoContent->value, "", []
    );
  }

  public static function movedPermanently(
    string $location
  ): Response {
    return new Response( HttpStatus::MovedPermanently->value, "", [
      "Location" => $location,
    ]);
  }

  public static function redirect(
    string $location
  ): Response {
    return new Response( 
      HttpStatus::Found->value, "", [ "Location" => $location ]
    );
  }

  public static function notModified(
  ): Response {
    return new Response(
      HttpStatus::NotModified->value, "", []
    );
  }

  public static function badRequest(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Bad Request", HttpStatus::BadRequest
    );
  }

  public static function unauthorized(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Unauthorized", HttpStatus::Unauthorized
    );
  }

  public static function paymentRequired(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Payment Required", HttpStatus::PaymentRequired
    );
  }

  public static function forbidden(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Forbidden", HttpStatus::Forbidden
    );
  }

  public static function notFound(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Not Found", HttpStatus::NotFound
    );
  }

  public static function methodNotAllowed(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Method Not Allowed", HttpStatus::MethodNotAllowed
    );
  }

  public static function conflict(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Conflict", HttpStatus::Conflict
    );
  }

  public static function gone(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Gone", HttpStatus::Gone
    );
  }

  public static function unprocessableContent(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Unprocessable Content", HttpStatus::UnprocessableContent
    );
  }

  public static function tooManyRequests(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Too Many Requests", HttpStatus::TooManyRequests
    );
  }

  public static function internalServerError(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Internal Server Error", HttpStatus::InternalServerError
    );
  }

  public static function HttpException(
    HttpException $httpException
  ): Response {
    return Response::json(
      $httpException->getMessage(),
      $httpException->httpStatus
    );
  }

  public static function notImplemented(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Not Implemented", HttpStatus::NotImplemented
    );
  }

  public static function badGateway(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Bad Gateway", HttpStatus::BadGateway
    );
  }

  public static function serviceUnavailable(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Service Unavailable", HttpStatus::ServiceUnavailable
    );
  }

  public static function gatewayTimeout(
    mixed $message = null
  ): Response {
    return Response::json(
      $message ?? "Gateway Timeout", HttpStatus::GatewayTimeout
    );
  }

  public function withHeader(
    string $key, string $value
  ): Response {
    $this->headers[$key] = $value;
    return $this;
  }

  private function buildConnectionKeepAlive(
    bool $keepAlive
  ): string {
    return sprintf(
      "Connection: %s", $keepAlive
        ? "keep-alive" : "close"
    );
  } 

  private function buildStatusText(
  ): string {
    return sprintf(
      "HTTP/1.1 {$this->status} %s", 
        HttpStatus::tryFrom( $this->status )->name
          ?? "Unknown"
    );
  }

  private function buildContentLength(
  ): string {
    return sprintf(
      "Content-Length: %s",
        strlen($this->body)
    );
  }

  private function buildAddHeader(
    string $key,
    string $value
  ): string {
    return sprintf(
      "%s: %s", $key, $value
    );
  }
  
  private function buildAddBody(
  ): string {
    return sprintf(
      "\r\n%s", $this->body
    );
  }  

  public function build(
    bool $keepAlive,
    Collection $builds = new Collection()
  ): string {
    $builds->add( $this->buildStatusText());
    $builds->add( $this->buildConnectionKeepAlive( $keepAlive ));
    $builds->add( $this->buildContentLength());
    
    foreach ($this->headers as $key => $value) {
      $builds->add(
        $this->buildAddHeader(
          $key,
          $value
        )
      );
    }

    $builds->add( $this->buildAddBody());
    return $builds->join( "\r\n" );
  }
}
