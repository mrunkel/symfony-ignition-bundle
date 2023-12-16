<?php

declare(strict_types=1);

namespace Spatie\SymfonyIgnitionBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

abstract class FunctionalTest extends TestCase
{
    // This is where the test Symfony application will be created
    protected const APP_DIRECTORY = __DIR__ . '/App';
    // These are the initial assets to use when creating a new Symfony application
    protected const APP_TEMPLATE = __DIR__ . '/AppTemplate';

    abstract public function testSymfonyworks(string $symfonyVersion): void;

    /**
     * Provide Symfony versions to test
     */
    public function versionProvider()
    {
        $symfonyVersion = trim(file_get_contents(__DIR__ . '/../../symfony-version.txt'));

        return $symfonyVersion;
    }

    protected function installSymfony(string $symfonyVersion): void
    {
        // Create a fresh directory for the Symfony app using the template
        $filesystem = new Filesystem();

        // Windows was having trouble with Filesystem::remove, so rename instead
        // $filesystem->remove(self::APP_DIRECTORY);
        if (file_exists(self::APP_DIRECTORY)) {
            $filesystem->rename(self::APP_DIRECTORY, self::APP_DIRECTORY . '~', overwrite: true);
        }
        $filesystem->mirror(self::APP_TEMPLATE, self::APP_DIRECTORY);

        // Install packages
        $composerInstall = new Process(
            [
                'composer',
                'install',
                '--no-cache',
                '--no-interaction',
                '--prefer-dist',
                '--optimize-autoloader',
            ],
            self::APP_DIRECTORY,
            ['SYMFONY_REQUIRE' => $symfonyVersion]
        );

        $composerInstall->mustRun();
    }

    public function runSymfonyHttpRequest(): Process
    {
        $httpCall = new Process(
            [
                'php',
                'public/index.php',
            ],
            self::APP_DIRECTORY
        );

        $httpCall->run();

        return $httpCall;
    }

    protected function assertCommandIsSuccessful(Process $command, bool $failOnErrorOutput = true): void
    {
        $message = $command->getOutput() . PHP_EOL . $command->getErrorOutput();
        $this->assertTrue($command->isSuccessful(), $message);

        if ($failOnErrorOutput) {
            $this->assertStringNotContainsStringIgnoringCase('Warning', $message, $message);
            $this->assertStringNotContainsStringIgnoringCase('Error', $message, $message);
        }
    }
}
