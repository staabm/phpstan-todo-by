<?php

namespace staabm\PHPStanTodoBy;

use Composer\InstalledVersions;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use PhpParser\Comment;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use UnexpectedValueException;

use function array_key_exists;
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
    private const ERROR_IDENTIFIER = 'packageVersion';

    private const COMPARATORS = ['<', '>', '='];

    // composer package-name pattern from https://getcomposer.org/doc/04-schema.md#name
    // adjusted because of backtrack limit issues https://github.com/staabm/phpstan-todo-by/issues/44
    private const PATTERN = <<<'REGEXP'
        {
            @?(?:TODO|FIXME|XXX) # possible @ prefix
            @?[a-zA-Z0-9_-]* # optional username
            \s*[:-]?\s* # optional colon or hyphen
            \s+ # keyword/version separator
            (?:(?P<package>(php|[a-z0-9]([_.-]?[a-z0-9]++)*+/[a-z0-9](([_.]|-{1,2})?[a-z0-9]++)*+)):) # "php" or a composer package name, followed by ":"
            (?P<version>[<>=]?[^\s:\-]+) # version
            \s*[:-]?\s* # optional colon or hyphen
            (?P<comment>(?:(?!\*+/).)*) # rest of line as comment text, excluding block end
        }ix
        REGEXP;

    private ExpiredCommentErrorBuilder $errorBuilder;

    private string $workingDirectory;

    /**
     * @var null|string|IdentifierRuleError
     */
    private $phpPlatformVersion;

    /**
     * @var array<string, string>
     */
    private array $virtualPackages;

    /**
     * @param array<string, string> $virtualPackages
     */
    public function __construct(
        ExpiredCommentErrorBuilder $errorBuilder,
        string $workingDirectory,
        array $virtualPackages
    ) {
        $this->workingDirectory = $workingDirectory;
        $this->virtualPackages = $virtualPackages;
        $this->errorBuilder = $errorBuilder;

        // require the top level installed versions, so we don't mix it up with the one in phpstan.phar
        $installedVersions = $this->workingDirectory . '/vendor/composer/InstalledVersions.php';
        if (!class_exists(InstalledVersions::class, false) && is_readable($installedVersions)) {
            require_once $installedVersions;
        }
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
                } elseif (array_key_exists($package, $this->virtualPackages)) {
                    $satisfiesOrError = $this->satisfiesVirtualPackage($package, $version, $comment, $match[0][1]);
                } else {
                    $satisfiesOrError = $this->satisfiesInstalledPackage($package, $version, $comment, $match[0][1]);
                }

                if ($satisfiesOrError instanceof RuleError) {
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
                    $comment->getText(),
                    $comment->getStartLine(),
                    $errorMessage,
                    self::ERROR_IDENTIFIER,
                    null,
                    $match[0][1]
                );
            }
        }

        return $errors;
    }

    /**
     * @return bool|IdentifierRuleError
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
                $comment->getText(),
                $comment->getStartLine(),
                'Invalid version constraint "' . $version . '" for package "' . $package . '".',
                self::ERROR_IDENTIFIER,
                null,
                $wholeMatchStartOffset
            );
        }

        return $provided->matches($constraint);
    }

    /**
     * @return bool|IdentifierRuleError
     */
    private function satisfiesVirtualPackage(string $package, string $version, Comment $comment, int $wholeMatchStartOffset)
    {
        $versionParser = new VersionParser();
        try {
            $provided = $versionParser->parseConstraints(
                $this->virtualPackages[$package]
            );
        } catch (UnexpectedValueException $e) {
            return $this->errorBuilder->buildError(
                $comment->getText(),
                $comment->getStartLine(),
                'Invalid virtual-package "' . $package . '": "' . $this->virtualPackages[$package] . '" provided via PHPStan config file.',
                self::ERROR_IDENTIFIER,
                null,
                $wholeMatchStartOffset
            );
        }

        try {
            $constraint = $versionParser->parseConstraints($version);
        } catch (UnexpectedValueException $e) {
            return $this->errorBuilder->buildError(
                $comment->getText(),
                $comment->getStartLine(),
                'Invalid version constraint "' . $version . '" for virtual-package "' . $package . '".',
                self::ERROR_IDENTIFIER,
                null,
                $wholeMatchStartOffset
            );
        }

        return $provided->matches($constraint);
    }

    /**
     * @return IdentifierRuleError|string
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
                $comment->getText(),
                $comment->getStartLine(),
                'Unable to find composer.json in '. $this->workingDirectory,
                self::ERROR_IDENTIFIER,
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
                $comment->getText(),
                $comment->getStartLine(),
                'Missing php platform requirement in '. $this->workingDirectory .'/composer.json',
                self::ERROR_IDENTIFIER,
                null,
                $wholeMatchStartOffset
            );
        }

        return $this->phpPlatformVersion = $config['require']['php'];
    }

    /**
     * @return bool|IdentifierRuleError
     */
    private function satisfiesInstalledPackage(string $package, string $version, Comment $comment, int $wholeMatchStartOffset)
    {
        $versionParser = new VersionParser();

        // see https://getcomposer.org/doc/07-runtime.md#installed-versions
        if (!InstalledVersions::isInstalled($package)) {
            return $this->errorBuilder->buildError(
                $comment->getText(),
                $comment->getStartLine(),
                'Unknown package "' . $package . '". It is neither installed via composer.json nor declared as virtual package via PHPStan config.',
                self::ERROR_IDENTIFIER,
                null,
                $wholeMatchStartOffset
            );
        }

        try {
            return InstalledVersions::satisfies($versionParser, $package, $version);
        } catch (UnexpectedValueException $e) {
            return $this->errorBuilder->buildError(
                $comment->getText(),
                $comment->getStartLine(),
                'Invalid version constraint "' . $version . '" for package "' . $package . '".',
                self::ERROR_IDENTIFIER,
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
