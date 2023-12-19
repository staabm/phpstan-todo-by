<?php

namespace staabm\PHPStanTodoBy;

use Version\Exception\InvalidVersionString;
use Version\Version;

final class ReferenceVersionFinder
{
    private TagFetcher $fetcher;
    private string $referenceVersion;

    private VersionNormalizer $versionNormalizer;

    public function __construct(string $referenceVersion, TagFetcher $fetcher)
    {
        $this->referenceVersion = $referenceVersion;
        $this->fetcher = $fetcher;
        $this->versionNormalizer = new VersionNormalizer();
    }
    public function find(?string $workingDirectory): string
    {
        if (in_array($this->referenceVersion, ['nextMajor', 'nextMinor', 'nextPatch'], true)) {
            $latestTagVersion = $this->fetcher->fetchLatestTagVersion($workingDirectory);

            $normalized = $this->versionNormalizer->normalize($latestTagVersion);
            // composer/semver versions have 4 parts, but Version\Version only accepts 3.
            $normalized = preg_replace('/\.0$/', '', $normalized);
            if ($normalized === null) {
                throw new \RuntimeException('Could not normalize version: ' . $latestTagVersion);
            }

            $version = Version::fromString($normalized);

            if ($this->referenceVersion === 'nextMajor') {
                return $version->incrementMajor()->toString();
            }
            if ($this->referenceVersion === 'nextMinor') {
                return $version->incrementMinor()->toString();
            }
            if ($this->referenceVersion === 'nextPatch') {
                return $version->incrementPatch()->toString();
            }
        }

        // a version string like "1.2.3"
        return $this->referenceVersion;
    }
}
