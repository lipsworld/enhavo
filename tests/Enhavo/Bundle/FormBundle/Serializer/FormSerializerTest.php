<?php

/**
 * FormSerializerTest.php
 *
 * @since 12/06/16
 * @author gseidel
 */

namespace Enhavo\Bundle\FormBundle\Serializer;

use Enhavo\Bundle\FormBundle\Serializer\Encoder\Encoder;
use Enhavo\Bundle\FormBundle\Serializer\Mock\Resource;
use Enhavo\Bundle\FormBundle\Serializer\Mock\ResourceType;
use Symfony\Component\Form\Test\TypeTestCase;

class FormSerializerTest extends TypeTestCase
{
    public function testSerializeScalarField()
    {
        $data = new Resource();
        $data->setName('testName');

        $formSerializer = new FormSerializer($this->factory, new Encoder());

        $serializeData = $formSerializer->serialize($data, ResourceType::class, 'array');
        $this->assertArrayHasKey('name', $serializeData);
        $this->assertEquals('testName', $serializeData['name']);
    }

    public function testSerializeCollection()
    {
        $data = new Resource();
        $child = new Resource();
        $child->setName('testName');
        $data->addResource($child);

        $formSerializer = new FormSerializer($this->factory, new Encoder());

        $serializeData = $formSerializer->serialize($data, ResourceType::class, 'array');
        $this->assertArrayHasKey('resources', $serializeData);
        $this->assertCount(1, $serializeData['resources']);
        $this->assertArrayHasKey('name', $serializeData['resources'][0]);
        $this->assertEquals('testName', $serializeData['resources'][0]['name']);
    }
}