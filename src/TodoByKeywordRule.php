<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;

use function trim;

/**
 * @implements Rule<Node>
 */
final class TodoByKeywordRule implements Rule
{
    private const ERROR_IDENTIFIER = 'keyword';

    private array $keywords;

    private const PATTERN = <<<'REGEXP'
        {
            @?(?:TODO|FIXME|XXX) # possible @ prefix
            @?[a-zA-Z0-9_-]* # optional username
            \s*[:-]?\s* # optional colon or hyphen
            \s+ # separator
            (?P<keyword>(?:%s)) # keyword
            \s*[:-]?\s* # optional colon or hyphen
            (?P<comment>(?:(?!\*+/).)*) # rest of line as comment text, excluding block end
        }ix
        REGEXP;

    private ExpiredCommentErrorBuilder $errorBuilder;

    /**
     * @param list<non-empty-string> $keywords
     */
    public function __construct(
        array $keywords,
        ExpiredCommentErrorBuilder $errorBuilder
    ) {
        $this->keywords = $keywords;
        $this->errorBuilder = $errorBuilder;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ([] === $this->keywords) {
            return [];
        }

        $it = CommentMatcher::matchComments($node, sprintf(self::PATTERN, implode('|', $this->keywords)));

        $errors = [];
        foreach ($it as $comment => $matches) {
            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {
                $keyword = $match['keyword'][0];
                $todoText = trim($match['comment'][0]);

                // Have a keyword at the start of the message.
                // If there is further text, append it.
                if ('' !== $todoText) {
                    $errorMessage = "Should be processed ({$keyword}): ". rtrim($todoText, '.') .'.';
                } else {
                    $errorMessage = "Comment should be processed ({$keyword}).";
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
}
