<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\Error;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;

/**
 * @internal
 */
final class IntegrationTest extends PHPStanTestCase
{
    // test all rules at once to make sure rule errors do not overlap
    public function testE2E(): void
    {
        $errors = $this->runAnalyse(__DIR__ . '/data/e2e.php');
        static::assertCount(2, $errors);

        $this->assertSame('Expired on 2023-12-14: fix it.', $errors[0]->getMessage());
        $this->assertSame('"php" version requirement ">=7" satisfied.', $errors[1]->getMessage());
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../extension.neon',
        ];
    }

    /**
     * @param string[]|null $allAnalysedFiles
     * @return Error[]
     */
    private function runAnalyse(string $file, ?array $allAnalysedFiles = null): array
    {
        $file = $this->getFileHelper()->normalizePath($file);
        /** @var Analyser $analyser */
        $analyser = self::getContainer()->getByType(Analyser::class);
        /** @var FileHelper $fileHelper */
        $fileHelper = self::getContainer()->getByType(FileHelper::class);
        /** @phpstan-ignore-next-line missing bc promise */
        $errors = $analyser->analyse([$file], null, null, true, $allAnalysedFiles)->getErrors();
        foreach ($errors as $error) {
            $this->assertSame($fileHelper->normalizePath($file), $error->getFilePath());
        }

        return $errors;
    }
}
