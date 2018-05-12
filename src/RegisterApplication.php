<?php
declare(strict_types=1);

namespace Chimera\DependencyInjection;

use Generator;
use Lcobucci\DependencyInjection\CompilerPassListProvider;
use Lcobucci\DependencyInjection\FileListProvider;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use function assert;
use function dirname;

final class RegisterApplication implements FileListProvider, CompilerPassListProvider
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ConditionallyLoadedPackage[]
     */
    private $relatedPackages;

    public function __construct(string $name)
    {
        $this->name = $name;

        $commandBusId = $name . '.command_bus';
        $queryBusId   = $name . '.query_bus';

        $this->relatedPackages = [
            new MessageCreator\JmsSerializer\Package(),
            new Mapping\Package(),
            new ServiceBus\Tactician\Package($commandBusId, $queryBusId),
            new Routing\Expressive\Package($name, $commandBusId, $queryBusId),
        ];
    }

    public function getFiles(): Generator
    {
        yield dirname(__DIR__) . '/config/foundation.xml';
        yield dirname(__DIR__) . '/config/routing.xml';

        foreach ($this->filterPackages(FileListProvider::class) as $package) {
            assert($package instanceof FileListProvider);

            yield from $package->getFiles();
        }
    }

    public function getCompilerPasses(): Generator
    {
        yield [new RegisterDefaultComponents(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -30];
        yield [new ValidateApplicationComponents($this->name), PassConfig::TYPE_OPTIMIZE, -30];

        foreach ($this->filterPackages(CompilerPassListProvider::class) as $package) {
            assert($package instanceof CompilerPassListProvider);

            yield from $package->getCompilerPasses();
        }
    }

    private function filterPackages(string $type): Generator
    {
        foreach ($this->relatedPackages as $package) {
            if ($package instanceof $type && $package->shouldBeLoaded()) {
                yield $package;
            }
        }
    }
}