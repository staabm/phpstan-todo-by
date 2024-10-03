<?php

namespace staabm\PHPStanTodoBy;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use UnexpectedValueException;

use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class TodoBySymfonyDeprecationRule implements Rule
{
    private const ERROR_IDENTIFIER = 'sfDeprecation';

    private string $workingDirectory;

    public function __construct(
        string $workingDirectory
    ) {
        $this->workingDirectory = $workingDirectory;

        // require the top level installed versions, so we don't mix it up with the one in phpstan.phar
        $installedVersions = $this->workingDirectory . '/vendor/composer/InstalledVersions.php';
        if (!class_exists(InstalledVersions::class, false) && is_readable($installedVersions)) {
            require_once $installedVersions;
        }
    }

    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node->name instanceof Node\Name)) {
            return [];
        }

        $args = $node->getArgs();
        if (count($args) < 3) {
            return [];
        }

        $functionName = strtolower($node->name->toString());
        if ('trigger_deprecation' !== $functionName) {
            return [];
        }

        $packageArgType = $scope->getType($args[0]->value);
        $versionArgType = $scope->getType($args[1]->value);
        $messageArgType = $scope->getType($args[2]->value);

        $messages = $messageArgType->getConstantStrings();
        if (1 !== count($messages)) {
            return [];
        }
        $message = $messages[0]->getValue();

        $errors = [];
        foreach ($packageArgType->getConstantStrings() as $package) {
            foreach ($versionArgType->getConstantStrings() as $version) {
                $satisfiesOrError = $this->satisfiesInstalledPackage($package->getValue(), $version->getValue());
                if ($satisfiesOrError instanceof RuleError) {
                    $errors[] = $satisfiesOrError;
                    continue;
                }
                if (true !== $satisfiesOrError) {
                    continue;
                }

                $errorMessage = 'Since %s %s: %s.';
                $errors[] = RuleErrorBuilder::message(
                    sprintf($errorMessage, $package->getValue(), $version->getValue(), $message)
                )->identifier(ExpiredCommentErrorBuilder::ERROR_IDENTIFIER_PREFIX.self::ERROR_IDENTIFIER)->build();
            }
        }

        return $errors;
    }

    /**
     * @return bool|IdentifierRuleError
     */
    private function satisfiesInstalledPackage(string $package, string $version)
    {
        $versionParser = new VersionParser();

        // see https://getcomposer.org/doc/07-runtime.md#installed-versions
        if (!InstalledVersions::isInstalled($package)) {
            return false;
        }

        try {
            return InstalledVersions::satisfies($versionParser, $package, '>='.$version);
        } catch (UnexpectedValueException $e) {
            return RuleErrorBuilder::message('Invalid version constraint "' . $version . '" for package "' . $package . '".')
                ->identifier(ExpiredCommentErrorBuilder::ERROR_IDENTIFIER_PREFIX.self::ERROR_IDENTIFIER)
                ->build();
        }
    }
}
