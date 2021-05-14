<?php

namespace JWebb\Unleash\Tests;

use Mockery;
use JWebb\Unleash\Unleash;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Config;
use JWebb\Unleash\Interfaces\Api\Feature;
use Illuminate\Console\Scheduling\Schedule;
use JWebb\Unleash\Facades\Unleash as FacadesUnleash;
use JWebb\Unleash\Providers\ServiceProvider;

class FakeFeature
{
    public function isEnabled($key)
    {
        return Config::get($key, false);
    }
}

class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    public function setup(): void
    {
        parent::setup();

        $this->instance(
            Unleash::class,
            Mockery::mock(Unleash::class, function (MockInterface $mock) {
                $mock->makePartial()
                ->shouldReceive('feature')->andReturn(new FakeFeature());
            })
        );

        Config::set([
            'feature_1' => true,
            'feature_2' => false,
        ]);
    }

    public function testScheduleMacro()
    {

        $event = (new Schedule())->command('list')
            ->sendOutputTo('')
            ->ifFeatureDisabled('feature_1');

        $this->assertFalse($event->filtersPass($this->app));

        $event = (new Schedule())->command('list')
            ->sendOutputTo('')
            ->ifFeatureEnabled('feature_2');

        $this->assertFalse($event->filtersPass($this->app));

        $event = (new Schedule())->command('list')
            ->sendOutputTo('')
            ->ifFeatureDisabled('feature_2');

        $this->assertTrue($event->filtersPass($this->app));

        $event = (new Schedule())->command('list')
            ->sendOutputTo('')
            ->ifFeatureEnabled('feature_1');

        $this->assertTrue($event->filtersPass($this->app));
    }
}
