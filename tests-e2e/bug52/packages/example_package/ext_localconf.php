<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// TODO: typo3/cms-core:13.0 This can be removed: https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.0/Feature-101807-AutomaticInclusionOfUserTSconfigOfExtensions.html
ExtensionManagementUtility::addUserTSConfig('@import "EXT:site_codappix/Configuration/user.tsconfig"');
