<?php

namespace staabm\PHPStanTodoBy;

use Composer\InstalledVersions;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use PhpParser\Comment;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use UnexpectedValueException;

use function in_array;
use function is_array;
use function is_string;
use function strlen;
use function trim;

/**
 * @implements Rule<Node>
 */
final class TodoByPackageVersionRule implements Rule
{
    private const COMPARATORS = ['<', '>', '='];

    // composer package-name pattern from https://getcomposer.org/doc/04-schema.md#name
    // adjusted because of backtrack limit issues https://github.com/staabm/phpstan-todo-by/issues/44
    private const PATTERN = <<<'REGEXP'
        {
            @?TODO # possible @ prefix
            @?[a-zA-Z0-9_-]* # optional username
            \s*[:-]?\s* # optional colon or hyphen
            \s+ # keyword/version separator
            (?:(?P<package>(php|[a-z0-9]([_.-]?[a-z0-9]++)*+/[a-z0-9](([_.]|-{1,2})?[a-z0-9]++)*+)):) # "php" or a composer package name, followed by ":"
            (?P<version>[<>=]?[^\s:\-]+) # version
            \s*[:-]?\s* # optional colon or hyphen
            (?P<comment>.*) # rest of line as comment text
        }ix
        REGEXP;

    private ExpiredCommentErrorBuilder $errorBuilder;

    private string $workingDirectory;

    /**
     * @var null|string|RuleError
     */
    private $phpPlatformVersion;

    public function __construct(
        string $workingDirectory,
        ExpiredCommentErrorBuilder $errorBuilder
    ) {
        $this->workingDirectory = $workingDirectory;
        $this->errorBuilder = $errorBuilder;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $it = CommentMatcher::matchComments($node, self::PATTERN);

        $errors = [];
        foreach ($it as $comment => $matches) {
            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {
                $package = $match['package'][0];
                $version = $match['version'][0];
                $todoText = trim($match['comment'][0]);

                // assume a min version constraint, when the comment does not specify a comparator
                if (null === $this->getVersionComparator($version)) {
                    $version = '>='. $version;
                }

                if ('php' === $package) {
                    $satisfiesOrError = $this->satisfiesPhpPlatformPackage($package, $version, $comment, $match[0][1]);
                } else {
                    $satisfiesOrError = $this->satisfiesInstalledPackage($package, $version, $comment, $match[0][1]);
                }

                if ($satisfiesOrError instanceof \PHPStan\Rules\RuleError) {
                    $errors[] = $satisfiesOrError;
                    continue;
                }
                if (false === $satisfiesOrError) {
                    continue;
                }

                // If there is further text, append it.
                if ('' !== $todoText) {
                    $errorMessage = '"'. $package .'" version requirement "'. $version .'" satisfied: '. rtrim($todoText, '.') .'.';
                } else {
                    $errorMessage = '"'. $package .'" version requirement "'. $version .'" satisfied.';
                }

                $errors[] = $this->errorBuilder->buildError(
                    $comment,
                    $errorMessage,
                    null,
                    $match[0][1]
                );
            }
        }

        return $errors;
    }

    /**
     * @return bool|\PHPStan\Rules\RuleError
     */
    private function satisfiesPhpPlatformPackage(string $package, string $version, Comment $comment, int $wholeMatchStartOffset)
    {
        $phpPlatformVersion = $this->readPhpPlatformVersion($comment, $wholeMatchStartOffset);
        if ($phpPlatformVersion instanceof RuleError) {
            return $phpPlatformVersion;
        }

        $versionParser = new VersionParser();
        $provided = $versionParser->parseConstraints($phpPlatformVersion);

        try {
            $constraint = $versionParser->parseConstraints($version);
        } catch (UnexpectedValueException $e) {
            return $this->errorBuilder->buildError(
                $comment,
                'Invalid version constraint "' . $version . '" for package "' . $package . '".',
                null,
                $wholeMatchStartOffset
            );
        }

        return $provided->matches($constraint);
    }

    /**
     * @return RuleError|string
     */
    private function readPhpPlatformVersion(Comment $comment, int $wholeMatchStartOffset)
    {
        if (null !== $this->phpPlatformVersion) {
            return $this->phpPlatformVersion;
        }

        /** @phpstan-ignore-next-line missing bc promise */
        $config = ComposerHelper::getComposerConfig($this->workingDirectory);

        // fallback to current working directory
        if (null === $config) {
            /** @phpstan-ignore-next-line missing bc promise */
            $config = ComposerHelper::getComposerConfig(getcwd());
        }

        if (null === $config) {
            return $this->phpPlatformVersion = $this->errorBuilder->buildError(
                $comment,
                'Unable to find composer.json in '. $this->workingDirectory,
                null,
                $wholeMatchStartOffset
            );
        }

        if (
            !isset($config['require'])
            || !is_array($config['require'])
            || !isset($config['require']['php'])
            || !is_string($config['require']['php'])
        ) {
            return $this->phpPlatformVersion = $this->errorBuilder->buildError(
                $comment,
                'Missing php platform requirement in '. $this->workingDirectory .'/composer.json',
                null,
                $wholeMatchStartOffset
            );
        }

        return $this->phpPlatformVersion = $config['require']['php'];
    }

    /**
     * @return bool|\PHPStan\Rules\RuleError
     */
    private function satisfiesInstalledPackage(string $package, string $version, Comment $comment, int $wholeMatchStartOffset)
    {
        $versionParser = new VersionParser();

        // see https://getcomposer.org/doc/07-runtime.md#installed-versions
        if (!InstalledVersions::isInstalled($package)) {
            return $this->errorBuilder->buildError(
                $comment,
                'Package "' . $package . '" is not installed via Composer.',
                null,
                $wholeMatchStartOffset
            );
        }

        try {
            return InstalledVersions::satisfies($versionParser, $package, $version);
        } catch (UnexpectedValueException $e) {
            return $this->errorBuilder->buildError(
                $comment,
                'Invalid version constraint "' . $version . '" for package "' . $package . '".',
                null,
                $wholeMatchStartOffset
            );
        }
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
