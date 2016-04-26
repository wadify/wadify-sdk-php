<?php

namespace Wadify\Test;

use Wadify\DependencyInjection\Container;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testOverrideSetCorrectlyANewService()
    {
        // Arrange
        $id = 'service_foo';
        $service = new \stdClass();

        // Act.
        Container::set($id, $service);

        //Assert.
        $this->assertEquals($service, Container::get($id));
    }
}
