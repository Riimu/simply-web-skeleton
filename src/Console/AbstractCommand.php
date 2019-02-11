<?php

namespace Application\Console;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;

/**
 * AbstractCommand.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class AbstractCommand extends Command
{
    /** @var ContainerInterface */
    protected $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}
