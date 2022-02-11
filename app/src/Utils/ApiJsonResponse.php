<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiJsonResponse
{

    const FIELD__STATUS = 'status';
    const FIELD__CONTENT = 'content';
    const FIELD__ERROR_MSG = 'error_msg';
    const FIELD__ERROR_CODE = 'error_code';
    const FIELD__INVALID_FIELD = 'invalid_field';
    const FIELD__ERROR_CONTENT = 'content';

    /**
     * Response Status
     * 
     * @var bool
     */
    private $status;

    /**
     * Response Status Code
     * 
     * @var int
     */
    private $status_code;

    /**
     * Response Content
     *
     * @var array|null
     */
    private $content;

    public function __construct(?array $content, bool $status, int $status_code)
    {
        $this->content = $content;
        $this->status = $status;
        $this->status_code = $status_code;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function getStatusCode(): string
    {
        return $this->status_code;
    }

    public function getContent(): ?array
    {
        return $this->content;
    }

    public static function fromError(string $msg, $status_code = Response::HTTP_INTERNAL_SERVER_ERROR, ?string $invalid_field = null, $content = null) 
    {
        $error_content = [];

        $error_content[self::FIELD__ERROR_MSG] = $msg;
        $error_content[self::FIELD__ERROR_CODE] = $status_code;
        $error_content[self::FIELD__INVALID_FIELD] = $invalid_field;
        $error_content[self::FIELD__ERROR_CONTENT] = $content;

        return (new self($error_content, false, $status_code, $msg));
    }

    public static function fromContent($content, $status_code = Response::HTTP_OK) 
    {
        return (new self($content, true, $status_code));
    }

    public static function error(string $msg, $status_code = Response::HTTP_INTERNAL_SERVER_ERROR, ?string $invalid_field = null, $content = null)
    {
        return self::fromError($msg, $status_code, $invalid_field, $content)->build();
    }

    public static function ok($content = null, $status_code = Response::HTTP_OK)
    {
        return self::fromContent($content, $status_code)->build();
    }

    public function getData() 
    {
        $data = [];

        $data[self::FIELD__STATUS] = $this->status;
        $data[self::FIELD__CONTENT] = $this->content;

        return $data;
    }

    public function build()
    {
        return new JsonResponse($this->getData(), $this->status_code);
    }
}
