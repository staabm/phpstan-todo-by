# phpstan-todo-by: comments with expiration

PHPStan extension to check for TODO/FIXME/XXX comments with expiration.
Inspired by [parker-codes/todo-by](https://github.com/parker-codes/todo_by).


## Examples

The main idea is, that comments within the source code will be turned into PHPStan errors when a condition is satisfied, e.g. a date reached, a version met, a issue tracker ticket is closed.

```php
<?php

// TODO: 2023-12-14 This comment turns into a PHPStan error as of 14th december 2023
function doFoo() { /* ... */ }

// TODO: <1.0.0 This has to be in the first major release of this repo
function doBar() { /* ... */ }

// FIXME: phpunit/phpunit:5.3 This has to be fixed when updating phpunit to 5.3.x or higher
function doFooBar() { /* ... */ }

// XXX: php:8 drop this polyfill when php 8.x is required

// TODO: APP-2137 A comment which errors when the issue tracker ticket gets resolved
function doBaz() { /* ... */ }

```


## Supported todo formats

A todo comment can also consist of just a constraint without any text, like `// @todo 2023-12-14`.
When a text is given after the date, this text will be picked up for the PHPStan error message.

- the `todo`, `TODO`, `tOdO`, `FIXME`, `XXX` keyword is case-insensitive
- the `todo` keyword can be suffixed or prefixed by a `@` character
- a username might be included after the `todo@`
- the comment might be mixed with `:` or `-` characters
- multi line `/* */` and `/** */` comments are supported

**Out of the box** comments can expire by different constraints:
- by date with format of `YYYY-MM-DD` matched against the [reference-time](https://github.com/staabm/phpstan-todo-by#reference-time)
- by a full github issue url

There are more builtin constraints, but these **require additional configuration**:
- by a semantic version constraint matched against the projects [reference-version](https://github.com/staabm/phpstan-todo-by#reference-version)
- by a semantic version constraint matched against a Composer dependency (via `composer.lock` or [`virtualPackages`](https://github.com/staabm/phpstan-todo-by#virtual-packages) config)
- by ticket reference, matched against the status of a ticket (e.g. in github.com or JIRA)

see examples of different comment variants which are supported:

```php
// todo 2023-12-14
// @todo: 2023-12-14 fix it
// @todo 2023-12-14: fix it
// XXX - 2023-12-14 fix it
// FIXME 2023-12-14 - fix it

// TODO@staabm 2023-12-14 - fix it
// TODO@markus: 2023-12-14 - fix it

// TODO https://github.com/staabm/phpstan-todo-by/issues/91 fix me when this GitHub issue is closed

/*
 * other text
 *
 * @todo 2023-12-14 classic multi line comment
 *   more comment data
 */

// TODO: <1.0.0 This has to be in the first major release
// TODO >123.4: Must fix this or bump the version

// TODO: phpunit/phpunit:<5 This has to be fixed before updating to phpunit 5.x
// TODO@markus: phpunit/phpunit:5.3 This has to be fixed when updating phpunit to 5.3.x or higher

// TODO: APP-123 fix it when this Jira ticket is closed
// TODO: #123 fix it when this GitHub issue is closed
// TODO: some-organization/some-repo#123 change me if this GitHub pull request is closed
```

## Configuration


### Non-ignorable errors

Errors emitted by the extension are non-ignorable by default, so they cannot accidentally be put into the baseline.
You can change this behaviour with a configuration option within your `phpstan.neon`:

```neon
parameters:
    todo_by:
        nonIgnorable: false # default is true
```


### Reference time

By default date-todo-comments are checked against todays date.

You might be interested, which comments will expire e.g. within the next 7 days, which can be configured with the `referenceTime` option.
You need to configure a date parsable by `strtotime`.

```neon
parameters:
    todo_by:
        # any strtotime() compatible string
        referenceTime: "now+7days"
```

It can be especially handy to use a env variable for it, so you can pass the reference date e.g. via the CLI:

```neon
parameters:
    todo_by:
        referenceTime: %env.TODOBY_REF_TIME%
```

`TODOBY_REF_TIME="now+7days" vendor/bin/phpstan analyze`


### Reference version

By default version-todo-comments are checked against `"nextMajor"` version.
It is determined by fetching the latest local available git tag and incrementing the major version number.

The behaviour can be configured with the `referenceVersion` option.
Possible values are `"nextMajor"`, `"nextMinor"`, `"nextPatch"` - which will be computed based on the latest local git tag - or any other version string like `"1.2.3"`.

```neon
parameters:
    todo_by:
        # "nextMajor", "nextMinor", "nextPatch" or a version string like "1.2.3"
        referenceVersion: "nextMinor"
```

As shown in the "Reference time"-paragraph above, you might even use a env variable instead.

> [!NOTE]
> The reference version is not applied to package-version-todo-comments which are matched against `composer.lock` instead.

#### Prerequisite

Make sure tags are available within your git clone, e.g. by running `git fetch --tags origin` - otherwise you are likely running into a 'Could not determine latest git tag' error.

In a GitHub Action this can be done like this:

```yaml
    -   name: Checkout
        uses: actions/checkout@v4

    -   name: Get tags
        run: git fetch --tags origin
```


#### Multiple GIT repository support

By default the latest git tag to calculate the reference version is fetched once for all files beeing analyzed.

This behaviour can be configured with the `singleGitRepo` option.

In case you are using git submodules, or the analyzed codebase consists of multiple git repositories,
set the `singleGitRepo` option to `false` which resolves the reference version for each directory beeing analyzed.


#### Virtual packages

Within the PHPStan config file you can define additional packages, to match against package-version-todo-comments.

```neon
parameters:
    todo_by:
        virtualPackages:
            'staabm/mypackage': '2.1.0'
            'staabm/my-api': '3.1.0'
```

Reference these virtual packages like any other package in your todo-comments:

`// TODO staabm/mypackage:2.2.0 remove the following function once staabm/mypackage is updated to 2.2.0`


### Issue tracker key support

Optionally you can configure this extension to analyze your comments with issue tracker ticket keys.
The extension fetches issue tracker API for issue status. If the remote issue is resolved, the comment will be reported.

Currently, Jira, GitHub and YouTrack are supported.

This feature is disabled by default. To enable it, you must set `ticket.enabled` parameter to `true`.
You also need to set these parameters:

```yaml
parameters:
    todo_by:
        ticket:
            enabled: true

            # one of: jira, github (case-sensitive)
            tracker: jira

            # a case-sensitive list of status names.
            # only tickets having any of these statuses are considered resolved.
            # supported trackers: jira. Other trackers ignore this parameter.
            resolvedStatuses:
                - Done
                - Resolved
                - Declined

            # if your ticket key is FOO-12345, then this value should be ["FOO"].
            # multiple key prefixes are allowed, e.g. ["FOO", "APP"].
            # only comments with keys containing this prefix will be analyzed.
            # supported trackers: jira, youtrack. Other trackers ignore this parameter.
            keyPrefixes:
                - FOO

            jira:
                # e.g. https://your-company.atlassian.net
                server: https://acme.atlassian.net

                # see below for possible formats.
                # if this value is empty, credentials file will be used instead.
                credentials: %env.JIRA_TOKEN%

                # path to a file containing Jira credentials.
                # see below for possible formats.
                # if credentials parameter is not empty, it will be used instead of this file.
                # this file must not be committed into the repository!
                credentialsFilePath: .secrets/jira-credentials.txt

            github:
                # The account owner of referenced repositories.
                defaultOwner: your-name

                # The name of the repository without the .git extension.
                defaultRepo: your-repository

                # GitHub Access Token
                # if this value is empty, credentials file will be used instead.
                credentials: null

                # path to a file containing GitHub Access Token.
                # if credentials parameter is not empty, it will be used instead of this file.
                # this file must not be committed into the repository!
                credentialsFilePath: null

            youtrack:
                # e.g. https://your-company.youtrack.cloud
                server: https://acme.youtrack.cloud

                # YouTrack permanent token
                # if this value is empty, credentials file will be used instead.
                credentials: %env.YOUTRACK_TOKEN%

                # path to a file containing a YouTrack permanent token
                # if credentials parameter is not empty, it will be used instead of this file.
                # this file must not be committed into the repository!
                credentialsFilePath: .secrets/youtrack-credentials.txt
```

#### Jira Credentials

This extension uses Jira's REST API to fetch ticket's status. If your board is not public, you need to configure valid credentials.
These authentication methods are supported:
- [OAuth 2.0 Access Tokens](https://confluence.atlassian.com/adminjiraserver/jira-oauth-2-0-provider-api-1115659070.html)
- [Personal Access Tokens](https://confluence.atlassian.com/enterprise/using-personal-access-tokens-1026032365.html)
- [Basic Authentication](https://developer.atlassian.com/server/jira/platform/basic-authentication/)

We recommend you use OAuth over basic authentication, especially if you use phpstan in CI.
There are multiple ways to pass your credentials to this extension.
You should choose one of them - if you define both parameters, only `credentials` parameter is considered and the file is ignored.

##### Pass credentials in environment variable

Configure `credentials` parameter to [read value from environment variable](https://phpstan.org/config-reference#environment-variables):
```yaml
parameters:
    todo_by:
        ticket:
            jira:
                credentials: %env.JIRA_TOKEN%
```

Depending on chosen authentication method its content should be:
* Access Token for token based authentication, e.g. `JIRA_TOKEN=ATATT3xFfGF0Gv_pLFSsunmigz8wq5Y0wkogoTMgxDFHyR...`
* `<username>:<passwordOrApiKey>` for basic authentication, e.g. `JIRA_TOKEN=john.doe@example.com:p@ssword`

##### Pass credentials in text file

Create text file in your project's directory (or anywhere else on your computer) and put its path into configuration:

```yaml
parameters:
    todo_by:
        ticket:
            jira:
                credentialsFilePath: path/to/file
```

**Remember not to commit this file to repository!**
Depending on chosen authentication method its value should be:
* Access Token for token based authentication, e.g. `JATATT3xFfGF0Gv_pLFSsunmigz8wq5Y0wkogoTMgxDFHyR...`
* `<username>:<passwordOrApiKey>` for basic authentication, e.g. `john.doe@example.com:p@ssword`

#### GitHub
Both issues and pull requests are supported. The comment will be reported if the referenced issue/PR is closed.
There are multiple ways to reference GitHub issue/PR:

##### Only number
```php
// TODO: #123 - fix me
```
If the `defaultOwner` is set to `acme` and `defaultRepo` is set to `hello-world`, the referenced issue is resolved to `acme/hello-world#123`.

##### Owner + repository name + number
```php
// TODO: acme/hello-world#123 - fix me
```

## Installation

To use this extension, require it in [Composer](https://getcomposer.org/):

```
composer require --dev staabm/phpstan-todo-by
```

If you also install [phpstan/extension-installer](https://github.com/phpstan/extension-installer) then you're all set!

<details>
  <summary>Manual installation</summary>

If you don't want to use `phpstan/extension-installer`, include extension.neon in your project's PHPStan config:

```
includes:
    - vendor/staabm/phpstan-todo-by/extension.neon
```

</details>

## FAQ

### Unexpected '"php" version requirement ">=XXX" satisfied' error

If you get this errors too early, it might be caused by wrong version constraints in your `composer.json` file.
A `php` version constraint of e.g. `^7.4` means `>=7.4.0 && <= 7.999999.99999`,
which means comments like `// TODO >7.5` will emit an error.

For the `php` declaration, it is recommended to use a version constraint with a fixed upper bound, e.g. `7.4.*` or `^7 || <8.3`.

### 'Could not determine latest git tag' error

This error is thrown, when no git tags are available within your git clone.
Fetch git tags, as described in the ["Reference version"-chapter](https://github.com/staabm/phpstan-todo-by#reference-version) above.

## 💌 Give back some love

[Consider supporting the project](https://github.com/sponsors/staabm), so we can make this tool even better even faster for everyone.
