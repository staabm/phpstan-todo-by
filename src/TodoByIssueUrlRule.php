<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\ticket\GitHubTicketStatusFetcher;

use function array_key_exists;
use function in_array;
use function trim;

/**
 * @implements Rule<Node>
 */
final class TodoByIssueUrlRule implements Rule
{
    private const ERROR_IDENTIFIER = 'url';

    private const PATTERN = <<<'REGEXP'
        {
            @?(?:TODO|FIXME|XXX) # possible @ prefix
            @?[a-zA-Z0-9_-]* # optional username
            \s*[:-]?\s* # optional colon or hyphen
            \s+ # keyword/version separator
            (?P<url>https://github.com/(?P<owner>[\S]{2,})/(?P<repo>[\S]+)/(issues|pull)/(?P<issueNumber>\d+)) # url
            \s*[:-]?\s* # optional colon or hyphen
            (?P<comment>(?:(?!\*+/).)*) # rest of line as comment text, excluding block end
        }ix
        REGEXP;

    private ExpiredCommentErrorBuilder $errorBuilder;
    private GitHubTicketStatusFetcher $fetcher;

    public function __construct(
        ExpiredCommentErrorBuilder $errorBuilder,
        GitHubTicketStatusFetcher $fetcher
    ) {
        $this->errorBuilder = $errorBuilder;
        $this->fetcher = $fetcher;
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
                $url = $match['url'][0];
                $owner = $match['owner'][0];
                $repo = $match['repo'][0];
                $issueNumber = $match['issueNumber'][0];
                $todoText = trim($match['comment'][0]);
                $wholeMatchStartOffset = $match[0][1];

                $apiUrl = $this->fetcher->buildUrl($owner, $repo, $issueNumber);
                $fetchedStatuses = $this->fetcher->fetchTicketStatusByUrls([$apiUrl => $apiUrl]);

                if (!array_key_exists($apiUrl, $fetchedStatuses) || null === $fetchedStatuses[$apiUrl]) {
                    $errors[] = $this->errorBuilder->buildError(
                        $comment->getText(),
                        $comment->getStartLine(),
                        "Ticket $url doesn't exist or provided credentials do not allow for viewing it.",
                        self::ERROR_IDENTIFIER,
                        null,
                        $wholeMatchStartOffset
                    );

                    continue;
                }

                $ticketStatus = $fetchedStatuses[$apiUrl];
                if (!in_array($ticketStatus, GitHubTicketStatusFetcher::RESOLVED_STATUSES, true)) {
                    continue;
                }

                // Adding a space after the {url} allows to have a proper clickable link, without the
                // additional character at the end being part of the link's URL, which breaks GitHub links.
                if ('' !== $todoText) {
                    $errorMessage = "Should have been resolved in {$url} : ". rtrim($todoText, '.') .'.';
                } else {
                    $errorMessage = "Comment should have been resolved with {$url} .";
                }

                $errors[] = $this->errorBuilder->buildError(
                    $comment->getText(),
                    $comment->getStartLine(),
                    $errorMessage,
                    self::ERROR_IDENTIFIER,
                    null,
                    $wholeMatchStartOffset
                );
            }
        }

        return $errors;
    }
}
