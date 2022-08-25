<?php

namespace Lot2k\Buildskip;

use Dotenv\Dotenv;
use Dotenv\Exception\ValidationException;
use Exception;
use Composer\Semver\Semver;
use UnexpectedValueException;

use function file_get_contents;
use function preg_replace;
use function file_put_contents;
use function file_exists;
use function proc_open;
use function is_resource;
use function trim;
use function stream_get_contents;
use function str_replace;
use function strlen;
use function proc_close;
use function preg_match;

use const PHP_EOL;

class Buildskip
{
    public const BUILDSKIP_ENV = '.buildskip.env';

    public const GOOD = 0;
    public const NOT_GOOD = 1;

    private string $simpleTag = '';
    private string $tag = '';
    private string $useTag = '';

    /**
     * @param string $prefix The path to BUILDSKIP_ENV.
     *
     * @return string BUILDSKIP.
     */
    public function root(string $prefix = ''): string
    {
        return $prefix . (empty($_ENV['BUILDSKIP_ENV']) ? self::BUILDSKIP_ENV : (string)$_ENV['BUILDSKIP_ENV']);
    }

    public function modifyDotEnv(): void
    {
        $env = file_get_contents(From::___->dir() . '.env');
        $env = preg_replace('#^BUILDSKIP_TAG=.*#m', "BUILDSKIP_TAG={$this->useTag}", $env);
        file_put_contents(From::___->dir() . '.env', $env);
    }

    public function build(): string
    {
        try {
            $this->env();
        } catch (ValidationException $e) {
            show('dotenv-validation', ['message' => $e->getMessage()]);
            exit(self::NOT_GOOD);
        }

        $this->tag();

        return $this->__toString();
    }

    public function env(): void
    {
        $bump = static fn (string $partial): string => (string)$_ENV["BUILDSKIP_{$partial}"];

        if (! file_exists($this->root(From::___->dir()))) {
            $this->init();
        }

        if (
            preg_match(
                '#^BUILDSKIP_INCREMENTAL_BUILD_CONSTRAINT=#m',
                file_get_contents($this->root(From::___->dir()))
            ) === 0
        ) {
            $this->init();
        }

        $dotenv = Dotenv::createImmutable(From::___->dir(), $this->root());
        $dotenv->safeLoad();

        $dotenv->required('BUILDSKIP_MAJOR_VERSION')->allowedRegexValues('#\d+#');
        $dotenv->required('BUILDSKIP_MINOR_VERSION')->allowedRegexValues('#\d+#');
        $dotenv->required('BUILDSKIP_PATCH')->allowedRegexValues('#\d+#');
        $dotenv->required('BUILDSKIP_BUILD')->allowedRegexValues('#[\da-zA-Z]*#');
        $dotenv->required('BUILDSKIP_INCREMENTAL_BUILD_CONSTRAINT');
        $dotenv->required('BUILDSKIP_PRE_RELEASE')->allowedRegexValues('#(?:[\.\da-zA-Z]+)?#');

        $this->simpleTag = "{$bump('MAJOR_VERSION')}.{$bump('MINOR_VERSION')}.{$bump('PATCH')}";
        $currentTag = $this->simpleTag;

        if (! empty($bump('PRE_RELEASE'))) {
            $currentTag .= "-{$bump('PRE_RELEASE')}";
        }

        $currentTag .= "+{$bump('BUILD')}";
        $this->constraints($currentTag);
    }

    private function constraints(string $currentTag): void
    {
        try {
            $constraints = Semver::satisfiedBy(
                [$this->simpleTag],
                (string)$_ENV['BUILDSKIP_INCREMENTAL_BUILD_CONSTRAINT']
            );
        } catch (UnexpectedValueException $e) {
            show(
                'invalid-build-constraint',
                [
                    'whichEnv' => $this->root(),
                    'constraint' => $_ENV['BUILDSKIP_INCREMENTAL_BUILD_CONSTRAINT'],
                    'message' => $e->getMessage(),
                ]
            );
            throw new BuildskipException(code: 1);
        }

        if (empty($constraints)) {
            show(
                'unsatisfied-build-constraint',
                [
                    'whichEnv' => $this->root(),
                    'currentTag' => $currentTag,
                    'constraint' => $_ENV['BUILDSKIP_INCREMENTAL_BUILD_CONSTRAINT'],
                ]
            );
            throw new BuildskipException(code: 1);
        }
    }

    private function init(): void
    {
        $env = file_exists($this->root(From::___->dir()))
            ? file_get_contents($this->root(From::___->dir()))
            : '';

        if (strlen($env) > 0) {
            $env .= PHP_EOL;
        }

        file_put_contents(
            $this->root(From::___->dir()),
            $env . <<<'ENV'
##
## buildskip    github.com/lot2k/php-buildskip
##

# gated auto-increment
#  - https://getcomposer.org/doc/articles/versions.md#writing-version-constraints 
BUILDSKIP_INCREMENTAL_BUILD_CONSTRAINT=~0.1.0

# incremented manually
BUILDSKIP_MAJOR_VERSION=0
BUILDSKIP_MINOR_VERSION=1
BUILDSKIP_PATCH=0
BUILDSKIP_PRE_RELEASE=

# incremented automatically
BUILDSKIP_BUILD=0
ENV
        );
    }

    public function tag(): void
    {
        $bump = static fn (string $partial): string => (string)$_ENV["BUILDSKIP_{$partial}"];

        $oldBuild = (int)$bump('BUILD');
        $newBuild = $oldBuild + 1;

        $tag = "{$bump('MAJOR_VERSION')}.{$bump('MINOR_VERSION')}.{$bump('PATCH')}";

        if (! empty($bump('PRE_RELEASE'))) {
            $tag .= "-{$bump('PRE_RELEASE')}";
        }

        $tag .= "+" . $newBuild;

        $this->tag = $tag;

        $env = file_get_contents($this->root(From::___->dir()));
        $env = preg_replace('#^BUILDSKIP_BUILD=.*#m', "BUILDSKIP_BUILD={$newBuild}", $env);
        file_put_contents($this->root(From::___->dir()), $env);

        $branch = $this->gitBranch();

        $this->useTag = match ($branch) {
            'main' => "{$this->simpleTag}",
            'contrib' => "{$this->simpleTag}-contrib",
            default => match ($bump('PRE_RELEASE')) {
                '' => "{$this->simpleTag}-{$branch}",
                default => "{$this->simpleTag}-{$bump('PRE_RELEASE')}-{$branch}",
            },
        };
    }

    public function gitBranch(): string
    {
        if (! file_exists(From::___->dir() . '.git/')) {
            show(
                'not-a-git-repo',
                [
                    'packageRoot' => From::___->dir(),
                ]
            );
            throw new BuildskipException(code: 1);
        }

        $remoteWrite = ['pipe', 'w'];
        $process = proc_open(
            'git rev-parse --abbrev-ref HEAD',
            [1 => $remoteWrite, 2 => $remoteWrite],
            $pipes,
            From::___->dir()
        );

        if (! is_resource($process)) {
            throw new Exception();
        }

        $errors = trim(stream_get_contents($pipes[2]));

        if (! empty($errors)) {
            throw new BuildskipException($errors);
        }

        $branch = trim(stream_get_contents($pipes[1]));
        proc_close($process);

        // lowercase letters, digits and separators
        return str_replace('/', '.', $branch);
    }

    public function status(): int
    {
        return match (strlen($this->simpleTag)) {
            0 => self::NOT_GOOD,
            default => self::GOOD
        };
    }

    public function __toString(): string
    {
        return "hup! {$this->tag} => {$this->useTag}";
    }
}
