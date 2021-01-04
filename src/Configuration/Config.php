<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Configuration;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getFileId(): string
    {
        return $this->getValue(['parameters', 'fileId']);
    }

    public function getFileTitle(): string
    {
        return $this->getValue(['parameters', 'fileTitle']);
    }

    public function getSheetId(): int
    {
        return (int) $this->getValue(['parameters', 'sheetId']);
    }

    public function getSheetTitle(): string
    {
        return $this->getValue(['parameters', 'sheetTitle']);
    }

    public function getHeader(): array
    {
        return $this->getValue(['parameters', 'header']);
    }

    public function getOutputTable(): string
    {
        return $this->getValue(['parameters', 'outputTable']);
    }
}
