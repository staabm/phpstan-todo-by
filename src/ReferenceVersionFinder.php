<?php

namespace staabm\PHPStanTodoBy;

use Version\Exception\InvalidVersionString;
use Version\Version;

final class ReferenceVersionFinder
{
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

            try {
                $version = Version::fromString($latestTagVersion);
            } catch (InvalidVersionString $originException) {
                try {
                    $version = Version::fromString($latestTagVersion.'.0');
                } catch (InvalidVersionString $innerException) {
                    try {
                        $version = Version::fromString($latestTagVersion.'.0.0');
                    } catch (InvalidVersionString $innerInnerException) {
                        throw $originException;
                    }
                }
            }

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
