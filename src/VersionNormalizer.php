<?php

namespace staabm\PHPStanTodoBy;

use Composer\Semver\VersionParser;

final class VersionNormalizer
{
    private VersionParser $versionParser;

    public function __construct()
    {
        $this->versionParser = new VersionParser();
    }

    public function normalize(string $version): string
    {
        return $this->versionParser->normalize($version);
    }
}
