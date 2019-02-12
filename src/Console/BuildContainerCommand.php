<?php

namespace Application\Console;

use Riimu\Kit\PHPEncoder\PHPEncoder;
use Simply\Container\Container;
use Simply\Container\ContainerBuilder;
use Simply\Container\Entry\WiredEntry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BuildContainerCommand.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BuildContainerCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('build:container')
            ->setDescription('Builds the application dependency injection container cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = new ContainerBuilder();

        $builder->registerConfiguration($this->getConfiguration());
        $builder->registerProvider(new DependencyProvider());

        $wiring = $this->getWiring();

        $builder->registerAutowiredClasses($wiring['classes'] ?? [], $wiring['parameters'] ?? []);

        $container = $builder->getContainer();

        foreach ($wiring['definitions'] ?? [] as $identifier => $arguments) {
            $container->addEntry($identifier, new WiredEntry($identifier, $arguments));
        }

        file_put_contents(BUILD_PATH . '/container.php', $this->prettifyCache($container));
    }

    private function getConfiguration()
    {
        $base = require SOURCE_PATH . '/configuration.php';

        $environment = getenv('APP_ENV') ?: 'production';
        $overrides = CONFIG_PATH . "/configuration.$environment.php";

        if (file_exists($overrides)) {
            $extra = require $overrides;
            return $extra + $base;
        }

        return $base;
    }

    private function getWiring()
    {
        return require SOURCE_PATH . '/wiring.php';
    }

    private function prettifyCache(Container $container): string
    {
        $encoder = new PHPEncoder([
            'string.classes' => [
                'Application\\',
                'Psr\\',
                'Simply\\',
            ],
            'string.imports' => [
                'Application\\' => '',
                'Psr\\Http\\Message\\' => 'Message',
                'Simply\\Application\\' => 'Application',
                'Simply\\Container\\Entry\\' => 'Entry',
                'Simply\\Router\\' => 'Router',
                DependencyProvider::class => 'DependencyProvider',
            ],
            'array.base' => 4,
            'array.inline' => 70,
        ]);

        $cache = $container->getCacheFile(function ($value) use ($encoder): string {
            return $encoder->encode($value);
        });
        $cache = substr($cache, 6);

        return <<<TEMPLATE
<?php

namespace Application;

use Application\Container\DependencyProvider;
use Psr\Http\Message;
use Simply\Application;
use Simply\Container\Entry;
use Simply\Router;

$cache

TEMPLATE;
    }
}
