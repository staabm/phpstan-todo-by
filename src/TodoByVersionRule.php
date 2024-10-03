<?php

namespace staabm\PHPStanTodoBy;

use Composer\Semver\VersionParser;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\LatestTagNotFoundException;
use staabm\PHPStanTodoBy\utils\ReferenceVersionFinder;
use UnexpectedValueException;

use function array_key_exists;
use function dirname;
use function in_array;
use function strlen;
use function trim;

/**
 * @implements Rule<Node>
 */
final class TodoByVersionRule implements Rule
{
    private const ERROR_IDENTIFIER = 'version';

    private const COMPARATORS = ['<', '>', '='];

    private const PATTERN = <<<'REGEXP'
        {
            @?(?:TODO|FIXME|XXX) # possible @ prefix
            @?[a-zA-Z0-9_-]* # optional username
            \s*[:-]?\s* # optional colon or hyphen
            \s+ # keyword/version separator
            (?P<version>[<>=]?[0-9]+\.[0-9]+(\.[0-9]+)?) # version
            \s*[:-]?\s* # optional colon or hyphen
            (?P<comment>(?:(?!\*+/).)*) # rest of line as comment text, excluding block end
        }ix
        REGEXP;

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
        ExpiredCommentErrorBuilder $errorBuilder
    ) {
        $this->referenceVersionFinder = $refVersionFinder;
        $this->errorBuilder = $errorBuilder;
        $this->singleGitRepo = $singleGitRepo;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $it = CommentMatcher::matchComments($node, self::PATTERN);

        $errors = [];
        $versionParser = new VersionParser();
        foreach ($it as $comment => $matches) {
            try {
                $referenceVersion = $this->getReferenceVersion($scope);
            } catch (LatestTagNotFoundException $e) {
                return [
                    RuleErrorBuilder::message($e->getMessage())
                        ->tip('See https://github.com/staabm/phpstan-todo-by#could-not-determine-latest-git-tag-error')
                        ->identifier(ExpiredCommentErrorBuilder::ERROR_IDENTIFIER_PREFIX.self::ERROR_IDENTIFIER)
                        ->build(),
                ];
            }
            $provided = $versionParser->parseConstraints(
                $referenceVersion
            );

            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {
                $version = $match['version'][0];
                $todoText = trim($match['comment'][0]);

                // assume a min version constraint, when the comment does not specify a comparator
                if (null === $this->getVersionComparator($version)) {
                    $version = '>='. $version;
                }

                try {
                    $constraint = $versionParser->parseConstraints($version);
                } catch (UnexpectedValueException $e) {
                    $errors[] = $this->errorBuilder->buildError(
                        $comment->getText(),
                        $comment->getStartLine(),
                        'Invalid version constraint "' . $version . '".',
                        self::ERROR_IDENTIFIER,
                        null,
                        $match[0][1]
                    );

                    continue;
                }

                if (!$provided->matches($constraint)) {
                    continue;
                }

                // If there is further text, append it.
                if ('' !== $todoText) {
                    $errorMessage = "Version requirement {$version} satisfied: ". rtrim($todoText, '.') .'.';
                } else {
                    $errorMessage = "Version requirement {$version} satisfied.";
                }

                $errors[] = $this->errorBuilder->buildError(
                    $comment->getText(),
                    $comment->getStartLine(),
                    $errorMessage,
                    self::ERROR_IDENTIFIER,
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
            $versionParser = new VersionParser();
            // lazy get the version, as it might incur subprocess creation
            $this->referenceVersions[$cacheKey] = $versionParser->normalize(
                $this->referenceVersionFinder->find($workingDirectory)
            );
        }

        return $this->referenceVersions[$cacheKey];
    }

    private function getVersionComparator(string $version): ?string
    {
        $comparator = null;
        for ($i = 0; $i < strlen($version); ++$i) {
            if (!in_array($version[$i], self::COMPARATORS)) {
                break;
            }
            $comparator .= $version[$i];
        }

        return $comparator;
    }
}
