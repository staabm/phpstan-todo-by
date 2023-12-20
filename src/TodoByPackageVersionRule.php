<?php

namespace staabm\PHPStanTodoBy;

use Composer\InstalledVersions;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
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
final class TodoByPackageVersionRule implements Rule
{
    // composer package-name pattern from https://getcomposer.org/doc/04-schema.md#name
    private const PATTERN = <<<'REGEXP'
{
    @?TODO # possible @ prefix
    @?[a-zA-Z0-9_-]*\s* # optional username
    \s*[:-]?\s* # optional colon or hyphen
    (?:(?P<package>[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*):) # optional composer package name
    (?P<version>[^\s:\-]+) # version
    \s*[:-]?\s* # optional colon or hyphen
    (?P<comment>.*) # rest of line as comment text
}ix
REGEXP;

    private ExpiredCommentErrorBuilder $errorBuilder;

    public function __construct(
        ExpiredCommentErrorBuilder $errorBuilder
    ) {
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
        foreach($it as $comment => $matches) {
            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {

                $package = $match['package'][0];

                // see https://getcomposer.org/doc/07-runtime.md#installed-versions
                if (!InstalledVersions::isInstalled($package)) {
                    $errors[] = 'Package "' . $package . '" is not installed via composer.';

                    continue;
                }

                $version = $match['version'][0];
                $todoText = trim($match['comment'][0]);

                if (InstalledVersions::satisfies(new VersionParser(), $package, $version)) {
                    continue;
                }

                // Have always present date at the start of the message.
                // If there is further text, append it.
                if ($todoText !== '') {
                    $errorMessage = "{$package} version requirement {$version} not satisfied: ". rtrim($todoText, '.') .".";
                } else {
                    $errorMessage = "{$package} version requirement {$version} not satisfied.";
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

}
