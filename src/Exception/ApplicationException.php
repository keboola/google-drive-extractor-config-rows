<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Exception;

use \Exception;
use Keboola\CommonExceptions\ApplicationExceptionInterface;

class ApplicationException extends Exception implements ApplicationExceptionInterface
{
    protected array $data;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, ?array $data = [])
    {
        $this->setData((array) $data);
        parent::__construct($message, $code, $previous);
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
