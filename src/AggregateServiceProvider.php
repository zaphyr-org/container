<?php

declare(strict_types=1);

namespace Zaphyr\Container;

use Zaphyr\Container\Contracts\AggregateServiceProviderInterface;
use Zaphyr\Container\Contracts\BootableServiceProvider;
use Zaphyr\Container\Contracts\ServiceProviderInterface;
use Zaphyr\Container\Exceptions\ContainerException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class AggregateServiceProvider implements AggregateServiceProviderInterface
{
    use ContainerAwareTrait;

    /**
     * @var ServiceProviderInterface[]
     */
    protected array $providers = [];

    /**
     * @var array<string|bool>.
     */
    protected array $registered = [];

    /**
     * {@inheritdoc}
     */
    public function add(ServiceProviderInterface $provider): static
    {
        if (in_array($provider, $this->providers, true)) {
            return $this;
        }

        $provider->setContainer($this->getContainer());

        if ($provider instanceof BootableServiceProvider) {
            $provider->boot();
        }

        $this->providers[] = $provider;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function provides(string $provider): bool
    {
        foreach ($this->providers as $service) {
            if ($service->provides($provider)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function register(string $provider): void
    {
        if (!$this->provides($provider)) {
            throw new ContainerException('"' . $provider . '" is not provided by any service provider');
        }

        foreach ($this->providers as $service) {
            if (isset($this->registered[$service->name()])) {
                continue;
            }

            if ($service->provides($provider)) {
                $this->registered[$service->name()] = true;
                $service->register();
            }
        }
    }
}
