<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

use PHPStan\DependencyInjection\Container;
use RuntimeException;

final class TicketRuleConfigurationFactory
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(): TicketRuleConfiguration
    {
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
                [],
                $fetcher,
            );
        }

        if ('youtrack' === $tracker) {
            $fetcher = $this->container->getByType(YouTrackTicketStatusFetcher::class);

            return new TicketRuleConfiguration(
                $fetcher::getKeyPattern(),
                ['resolved'],
                $keyPrefixes,
                $fetcher,
            );
        }

        throw new RuntimeException("Unsupported tracker type: $tracker");
    }
}
