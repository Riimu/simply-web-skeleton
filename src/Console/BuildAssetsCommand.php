<?php

namespace Application\Console;

use Riimu\Kit\PHPEncoder\PHPEncoder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BuildAssetsCommand.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class BuildAssetsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('build:assets')
            ->setDescription('Builds asset cache')
            ->addOption('clear-missing', null, InputOption::VALUE_NONE, 'Clear only missing assets');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $assetsPath = PUBLIC_PATH . '/assets';

        if (!file_exists($assetsPath)) {
            throw new \RuntimeException("The assets path '$assetsPath' does not exist");
        }

        $missingOnly = $input->getOption('clear-missing');

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($assetsPath)) as $file) {
            $path = $file->getPathname();

            if (preg_match('/\.[0-9a-z]{8}\.[^.]+$/', $file->getFilename())) {
                if ($missingOnly && file_exists($path)) {
                    continue;
                }

                $output->writeln('Removing ' . $path, OutputInterface::VERBOSITY_VERBOSE);
                unlink($file->getPathname());
            }
        }

        $assets = [];

        foreach ($this->getAssets() as $name => $path) {
            if (!file_exists($path)) {
                throw new \RuntimeException("Invalid asset file '$path'");
            }

            $path = realpath($path);
            $hash = substr(sha1_file($path), 0, 8);
            $link = preg_replace('/\.[^.]+$/', ".$hash$0", $name);
            $full = $assetsPath . '/' . $link;

            if (file_exists($full)) {
                if (realpath($full) === $path) {
                    continue;
                }

                unlink($full);
            } elseif (!file_exists(dirname($full))) {
                mkdir(dirname($full), 0755, true);
            }

            $output->writeln("Creating link $full -> $path", OutputInterface::VERBOSITY_VERBOSE);
            RelativeLink::create($path, $full);
            $assets[$name] = $link;
        }

        file_put_contents(BUILD_PATH . '/assets.php', $this->prettifyCache($assets));
    }

    private function getAssets(): array
    {
        $assets = [
            'css/style.css' => RESOURCES_PATH . '/css/style.css',
        ];

        foreach (new \DirectoryIterator(RESOURCES_PATH . '/images') as $file) {
            if ($file->isDir()) {
                continue;
            }

            $assets['images/' . $file->getBasename()] = $file->getPathname();
        }

        foreach (new \DirectoryIterator(RESOURCES_PATH . '/images/blog') as $file) {
            if ($file->isDir()) {
                continue;
            }

            $assets['images/blog/' . $file->getBasename()] = $file->getPathname();
        }

        ksort($assets);

        return $assets;
    }

    private function prettifyCache(array $assets)
    {
        $encoder = new PHPEncoder();
        $cache = $encoder->encode($assets);

        return <<<TEMPLATE
<?php return $cache;

TEMPLATE;
    }
}
