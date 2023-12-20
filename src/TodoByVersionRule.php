<?php

namespace staabm\PHPStanTodoBy;

use Composer\Semver\Comparator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\ReferenceVersionFinder;
use staabm\PHPStanTodoBy\utils\VersionNormalizer;
use function preg_match_all;
use function substr_count;
use function trim;
use const PREG_OFFSET_CAPTURE;
use const PREG_SET_ORDER;

/**
 * @implements Rule<Node>
 */
final class TodoByVersionRule implements Rule
{
    private const COMPARATORS = ['<', '>', '='];

    private const PATTERN = <<<'REGEXP'
/
@?TODO # possible @ prefix
@?[a-zA-Z0-9_-]*\s* # optional username
\s*[:-]?\s* # optional colon or hyphen
(?P<version>[<>=]+[^\s:\-]+) # version
\s*[:-]?\s* # optional colon or hyphen
(?P<comment>.*) # rest of line as comment text
/ix
REGEXP;

    private VersionNormalizer $versionNormalizer;

    private ReferenceVersionFinder $referenceVersionFinder;

    private bool $singleGitRepo;

    /**
     * @var array<string, string>
     */
    private array $referenceVersions = [];

    private ExpiredCommentErrorBuilder $errorBuilder;

    public function __construct(
        bool $singleGitRepo,
        ReferenceVersionFinder $refVersionFinder,
        VersionNormalizer $versionNormalizer,
        ExpiredCommentErrorBuilder $errorBuilder
    ) {
        $this->referenceVersionFinder = $refVersionFinder;
        $this->errorBuilder = $errorBuilder;
        $this->singleGitRepo = $singleGitRepo;
        $this->versionNormalizer = $versionNormalizer;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $it = CommentMatcher::matchComments($node, self::PATTERN);

        $errors = [];
        foreach($it as $comment => $matches) {
            $referenceVersion = $this->getReferenceVersion($scope);

            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {

                $version = $match['version'][0];
                $todoText = trim($match['comment'][0]);

                $versionComparator = $this->getVersionComparator($version);
                $plainVersion = ltrim($version, implode("", self::COMPARATORS));
                $normalized = $this->versionNormalizer->normalize($plainVersion);

                $expired = false;
                if ($versionComparator === '<') {
                    $expired = Comparator::greaterThanOrEqualTo($referenceVersion, $normalized);
                } elseif ($versionComparator === '>') {
                    $expired = Comparator::greaterThan($referenceVersion, $normalized);
                }

                if (!$expired) {
                    continue;
                }

                // Have always present date at the start of the message.
                // If there is further text, append it.
                if ($todoText !== '') {
                    $errorMessage = "Version requirement {$version} not satisfied: ". rtrim($todoText, '.') .".";
                } else {
                    $errorMessage = "Version requirement {$version} not satisfied.";
                }

                $errors[] = $this->errorBuilder->buildError(
                    $comment,
                    $errorMessage,
                    "Calculated reference version is '". $referenceVersion ."'.\n\n   See also:\n https://github.com/staabm/phpstan-todo-by#reference-version",
                    $match[0][1]
                );
            }
        }

        return $errors;
    }

    private function getReferenceVersion(Scope $scope): string
    {
        if ($this->singleGitRepo) {
            // same reference shared by all files
            $cacheKey = '__todoby__global__';
            $workingDirectory = null;
        } else {
            // reference only shared between files in the same directory
            // slower but adds support for analyzing codebases with several git clones
            $cacheKey = $workingDirectory = dirname($scope->getFile());
        }

        if (!array_key_exists($cacheKey, $this->referenceVersions)) {
            // lazy get the version, as it might incur subprocess creation
            $this->referenceVersions[$cacheKey] = $this->versionNormalizer->normalize(
                $this->referenceVersionFinder->find($workingDirectory)
            );
        }

        return $this->referenceVersions[$cacheKey];
    }

    private function getVersionComparator(string $version): ?string
    {
        $comparator = null;
        for($i = 0; $i < strlen($version); $i++) {
            if (!in_array($version[$i], self::COMPARATORS)) {
                break;
            }
            $comparator .= $version[$i];
        }

        return $comparator;
    }
}
