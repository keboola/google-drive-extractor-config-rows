<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Configuration;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getSheets(): array
    {
        return $this->getValue(
            ['parameters', 'sheets'],
            []
        );
    }
}
