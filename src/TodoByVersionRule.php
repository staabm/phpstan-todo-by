<?php

namespace staabm\PHPStanTodoBy;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function preg_match_all;
use function strtotime;
use function substr_count;
use function time;
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

    private ?string $referenceVersion = null;
    private bool $nonIgnorable;

    private VersionParser $versionParser;

    private ReferenceVersionFinder $referenceVersionFinder;

    public function __construct(bool $nonIgnorable, ReferenceVersionFinder $refVersionFinder)
    {
        $this->versionParser = new VersionParser();
        $this->referenceVersionFinder = $refVersionFinder;
        $this->nonIgnorable = $nonIgnorable;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (
            $node instanceof VirtualNode
            || $node instanceof Node\Expr
        ) {
            // prevent duplicate errors
            return [];
        }

        $errors = [];

        foreach ($node->getComments() as $comment) {

            $text = $comment->getText();

            /**
             * PHP doc comments have the entire multi-line comment as the text.
             * Since this could potentially contain multiple "todo" comments, we need to check all lines.
             * This works for single line comments as well.
             *
             * PREG_OFFSET_CAPTURE: Track where each "todo" comment starts within the whole comment text.
             * PREG_SET_ORDER: Make each value of $matches be structured the same as if from preg_match().
             */
            if (
                preg_match_all(self::PATTERN, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === false
                || count($matches) === 0
            ) {
                continue;
            }

            $referenceVersion = $this->getReferenceVersion();

            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {

                $version = $match['version'][0];
                $todoText = trim($match['comment'][0]);

                $versionComparator = $this->getVersionComparator($version);
                $plainVersion = ltrim($version, implode("", self::COMPARATORS));
                $normalized = $this->versionParser->normalize($plainVersion);

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

                $wholeMatchStartOffset = $match[0][1];

                // Count the number of newlines between the start of the whole comment, and the start of the match.
                $newLines = substr_count($text, "\n", 0, $wholeMatchStartOffset);

                // Set the message line to match the line the comment actually starts on.
                $messageLine = $comment->getStartLine() + $newLines;

                $errBuilder = RuleErrorBuilder::message($errorMessage)->line($messageLine);
                if ($this->nonIgnorable) {
                    $errBuilder->nonIgnorable();
                }
                $errBuilder->tip('Calculated reference version is '. $referenceVersion .".\n\n   See also:\n https://github.com/staabm/phpstan-todo-by#reference-version");
                $errors[] = $errBuilder->build();
            }
        }

        return $errors;
    }

    private function getReferenceVersion(): string
    {
        if ($this->referenceVersion === null) {
            // lazy get the version, as it might incur subprocess creation
            $this->referenceVersion = $this->versionParser->normalize($this->referenceVersionFinder->find());
        }
        return $this->referenceVersion;
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
