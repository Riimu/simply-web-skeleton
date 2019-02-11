<?php

namespace Application\Console;

use Psr\Container\ContainerInterface;
use Simply\Container\Container;
use Symfony\Component\Console\Application;

/**
 * ConsoleApplications.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ConsoleApplications
{
    public function run(): int
    {
        $commands = $this->getCommands();
        $container = $this->getContainer();

        array_walk($commands, function (AbstractCommand $command) use ($container): void {
            $command->setContainer($container);
        });

        $console = new Application();
        $console->addCommands($commands);
        $console->setAutoExit(true);
        return $console->run();
    }

    private function getCommands(): array
    {
        return [
            new BuildAssetsCommand(),
            new BuildContainerCommand(),
            new BuildRoutesCommand(),
        ];
    }

    private function getContainer(): ContainerInterface
    {
        if (file_exists(BUILD_PATH . '/container.php')) {
            return require BUILD_PATH . '/container.php';
        }

        return new Container();
    }
}
