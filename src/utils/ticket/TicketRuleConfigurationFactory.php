<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

use PHPStan\DependencyInjection\Container;

final class TicketRuleConfigurationFactory
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(): TicketRuleConfiguration
    {
        /** @var array{ticket:array{resolvedStatuses: list<string>, keyPrefixes: list<string>, tracker: 'github'|'jira'}} $extensionParameters */
        $extensionParameters = $this->container->getParameter('todo_by');

        $parameters = $extensionParameters['ticket'];
        $resolvedStatuses = $parameters['resolvedStatuses'];
        $keyPrefixes = $parameters['keyPrefixes'];
        $tracker = $parameters['tracker'];

        if ('jira' === $tracker) {
            $fetcher = $this->container->getByType(JiraTicketStatusFetcher::class);

            return new TicketRuleConfiguration(
                $fetcher::getKeyPattern(),
                $resolvedStatuses,
                $keyPrefixes,
                $fetcher,
            );
        }

        if ('github' === $tracker) {
            $fetcher = $this->container->getByType(GitHubTicketStatusFetcher::class);

            return new TicketRuleConfiguration(
                $fetcher::getKeyPattern(),
                ['closed'],
                ['#'],
                $fetcher,
            );
        }
    }
}
