<?php

namespace staabm\PHPStanTodoBy;

use Composer\Semver\VersionParser;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use UnexpectedValueException;

use function array_key_exists;
use function in_array;
use function strlen;
use function trim;

/**
 * @implements Rule<Node>
 */
final class TodoByConfigVersionRule implements Rule
{
    private const COMPARATORS = ['<', '>', '='];

    private const PATTERN = <<<'REGEXP'
        {
            @?TODO # possible @ prefix
            @?[a-zA-Z0-9_-]*\s* # optional username
            \s*[:-]?\s* # optional colon or hyphen
            \s+ # keyword/version separator
            (?:(?P<name>[a-z0-9]+):) # toggle name
            (?P<version>[<>=]?[0-9]+[^\s:\-]+) # version
            \s*[:-]?\s* # optional colon or hyphen
            (?P<comment>.*) # rest of line as comment text
        }ix
        REGEXP;

    private ExpiredCommentErrorBuilder $errorBuilder;

    /**
     * @var array<string, string>
     */
    private array $configs;

    public function __construct(
        array $configs,
        ExpiredCommentErrorBuilder $errorBuilder
    ) {
        $this->configs = $configs;
        $this->errorBuilder = $errorBuilder;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ([] === $this->configs) {
            return [];
        }

        $it = CommentMatcher::matchComments($node, self::PATTERN);

        $errors = [];
        $versionParser = new VersionParser();
        foreach ($it as $comment => $matches) {
            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {
                $name = $match['name'][0];

                if (!array_key_exists($name, $this->configs)) {
                    $errors[] = $this->errorBuilder->buildError(
                        $comment,
                        'Unknown config versionToggle "' . $name . '".',
                        null,
                        $match[0][1]
                    );

                    continue;
                }
                $provided = $versionParser->parseConstraints(
                    $this->configs[$name]
                );

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
                        $comment,
                        'Invalid version constraint "' . $version . '".',
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
        for ($i = 0; $i < strlen($version); ++$i) {
            if (!in_array($version[$i], self::COMPARATORS)) {
                break;
            }
            $comparator .= $version[$i];
        }

        return $comparator;
    }
}
