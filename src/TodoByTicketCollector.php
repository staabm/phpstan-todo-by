<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ticket\TicketRuleConfiguration;

use function trim;

/**
 * @implements Collector<Node, list<array{string, int, string, string, int, int}>>
 */
final class TodoByTicketCollector implements Collector
{
    private TicketRuleConfiguration $configuration;

    public function __construct(TicketRuleConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope)
    {
        $it = CommentMatcher::matchComments($node, $this->createPattern());

        $tickets = [];
        foreach ($it as $comment => $matches) {
            // use deprecated method for nikic/php-parser 4.x compat
            $line = $comment->getLine();

            $text = $comment->getText();
            $startLine = $comment->getStartLine();

            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {
                $ticketKey = $match['ticketKey'][0];
                $todoText = trim($match['comment'][0]);

                $tickets[] = [
                    $text,
                    $startLine,
                    $ticketKey,
                    $todoText,
                    $match[0][1], // wholeMatchStartOffset
                    $line,
                ];
            }
        }

        // don't return empty array so we don't pollute the result cache
        // see https://github.com/phpstan/phpstan/discussions/11701#discussioncomment-10660711
        if ([] !== $tickets) {
            return $tickets;
        }
        return null;
    }

    private function createPattern(): string
    {
        $keyRegex = $this->configuration->getKeyPattern();

        return <<<"REGEXP"
            {
                @?(?:TODO|FIXME|XXX) # possible @ prefix
                @?[a-zA-Z0-9_-]* # optional username
                \s*[:-]?\s* # optional colon or hyphen
                \s+ # keyword/ticket separator
                (?P<ticketKey>$keyRegex) # ticket key
                \s*[:-]?\s* # optional colon or hyphen
                (?P<comment>(?:(?!\*+/).)*) # rest of line as comment text, excluding block end
            }ix
            REGEXP;
    }
}
