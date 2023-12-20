<?php

namespace staabm\PHPStanTodoBy\utils;

use Version\Exception\InvalidVersionString;
use Version\Version;

final class ReferenceVersionFinder
{
    private const PRE_RELEASE_CHUNK_COUNT = 2;

    private TagFetcher $fetcher;
    private string $referenceVersion;

    public function __construct(string $referenceVersion, TagFetcher $fetcher)
    {
        $this->referenceVersion = $referenceVersion;
        $this->fetcher = $fetcher;
    }

    public function find(?string $workingDirectory): string
    {
        if (in_array($this->referenceVersion, ['nextMajor', 'nextMinor', 'nextPatch'], true)) {
            $latestTagVersion = $this->fetcher->fetchLatestTagVersion($workingDirectory);
            return $this->nextVersion($latestTagVersion);
        }

        // a version string like "1.2.3"
        return $this->referenceVersion;
    }

    // adopted from https://github.com/WyriHaximus/github-action-next-semvers/blob/master/src/Next.php
    private function nextVersion(string $versionString): string
    {
        try {
            $version = Version::fromString($versionString);
        } catch (InvalidVersionString $invalidVersionException) {
            if (count(explode('.', $versionString)) === 3) {
                throw $invalidVersionException;
            }

            // split versionString by '-' (in case it is a pre-release)
            if (strpos($versionString, '-') !== false) {
                [$versionString, $preRelease] = explode('-', $versionString, self::PRE_RELEASE_CHUNK_COUNT);
                $versionString               .= '.0-' . $preRelease;
            } else {
                $versionString .= '.0';
            }

            return self::nextVersion($versionString);
        }

        $wasPreRelease = false;

        // if current version is a pre-release
        if ($version->isPreRelease()) {
            // get current version by removing anything else (e.g., pre-release, build-id, ...)
            $version       = Version::from($version->getMajor(), $version->getMinor(), $version->getPatch());
            $wasPreRelease = true;
        }

        if ($this->referenceVersion === 'nextMajor') {
            return $version->incrementMajor()->toString();
        }
        if ($this->referenceVersion === 'nextMinor') {
            return $version->incrementMinor()->toString();
        }
        if ($this->referenceVersion === 'nextPatch') {
            // check if current version is a pre-release
            if ($wasPreRelease) {
                // use current version (without pre-release)
                return $version->__toString();
            }
            return $version->incrementPatch()->toString();
        }

        throw new \RuntimeException('Invalid reference version: ' . $this->referenceVersion);
    }
}
