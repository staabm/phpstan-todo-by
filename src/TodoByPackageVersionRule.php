<?php

namespace staabm\PHPStanTodoBy;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\ComposerHelper;
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
final class TodoByPackageVersionRule implements Rule
{
    private const COMPARATORS = ['<', '>', '='];

    // composer package-name pattern from https://getcomposer.org/doc/04-schema.md#name
    private const PATTERN = <<<'REGEXP'
{
    @?TODO # possible @ prefix
    @?[a-zA-Z0-9_-]*\s* # optional username
    \s*[:-]?\s* # optional colon or hyphen
    (?:(?P<package>(php|[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*)):) # "php" or a composer package name, followed by ":"
    (?P<version>[^\s:\-]+) # version constraint
    \s*[:-]?\s* # optional colon or hyphen
    (?P<comment>.*) # rest of line as comment text
}ix
REGEXP;

    private ExpiredCommentErrorBuilder $errorBuilder;

    private string $workingDirectory;

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
        $versionParser = new VersionParser();
        foreach($it as $comment => $matches) {
            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {

                $package = $match['package'][0];
                $version = $match['version'][0];
                $todoText = trim($match['comment'][0]);

                // assume a min version constraint, when the comment does not specify a comparator
                if ($this->getVersionComparator($version) === null) {
                    $version = '>='. $version;
                }

                if ($package === 'php') {
                    // @phpstan-ignore-next-line missing bc promise
                    $config = ComposerHelper::getComposerConfig($this->workingDirectory);
                    if ($config === null) {
                        $errors[] = $this->errorBuilder->buildError(
                            $comment,
                            'Unable to find composer.json in '. $this->workingDirectory,
                            null,
                            $match[0][1]
                        );

                        continue;
                    }

                    if (
                        !isset($config['require'])
                        || !is_array($config['require'])
                        || !isset($config['require']['php'])
                        || !is_string($config['require']['php'])
                    ) {
                        $errors[] = $this->errorBuilder->buildError(
                            $comment,
                            'Missing php platform requirement in '. $this->workingDirectory .'/composer.json',
                            null,
                            $match[0][1]
                        );

                        continue;
                    }

                    $provided = $versionParser->parseConstraints(
                        $config['require']['php']
                    );

                    try {
                        $constraint = $versionParser->parseConstraints($version);
                    } catch (\UnexpectedValueException $e) {
                        $errors[] = $this->errorBuilder->buildError(
                            $comment,
                            'Invalid version constraint "' . $version . '" for package "' . $package . '".',
                            null,
                            $match[0][1]
                        );

                        continue;
                    }

                    if (!$provided->matches($constraint)) {
                        continue;
                    }
                } else {
                    // see https://getcomposer.org/doc/07-runtime.md#installed-versions
                    if (!InstalledVersions::isInstalled($package)) {
                        $errors[] = $this->errorBuilder->buildError(
                            $comment,
                            'Package "' . $package . '" is not installed via Composer.',
                            null,
                            $match[0][1]
                        );

                        continue;
                    }

                    try {
                        if (!InstalledVersions::satisfies($versionParser, $package, $version)) {
                            continue;
                        }
                    } catch (\UnexpectedValueException $e) {
                        $errors[] = $this->errorBuilder->buildError(
                            $comment,
                            'Invalid version constraint "' . $version . '" for package "' . $package . '".',
                            null,
                            $match[0][1]
                        );

                        continue;
                    }
                }

                // Have always present date at the start of the message.
                // If there is further text, append it.
                if ($todoText !== '') {
                    $errorMessage = '"'. $package .'" version requirement "'. $version .'" satisfied: '. rtrim($todoText, '.') .".";
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
