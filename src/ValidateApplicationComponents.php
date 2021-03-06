<?php
declare(strict_types=1);

namespace Chimera\DependencyInjection;

use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use function sprintf;

final class ValidateApplicationComponents implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $appName;

    public function __construct(string $appName)
    {
        $this->appName = $appName;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function process(ContainerBuilder $container): void
    {
        $httpInterface = $container->getDefinition($this->appName . '.http');

        if (! $httpInterface->isPublic()) {
            throw new RuntimeException(
                sprintf('The HTTP interface for "%s" is not a public service', $this->appName)
            );
        }
    }
}
