<?php

namespace Enhavo\Bundle\AppBundle\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;

class ListTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $form = $this->factory->create(ListType::class);

        $form->setData([
            0 => 'A',
            1 => 'B'
        ]);

        $form->submit([
            '0' => 'A',
            '1' => 'B'
        ]);

        $form->createView();

        $this->assertTrue($form->isSynchronized());
        $this->assertArraySubset([
            0 => 'A',
            1 => 'B'
        ], $form->getData());
    }

    public function testOrderOfArray()
    {
        $form = $this->factory->create(ListType::class, null);

        $form->setData([
            0 => 'A',
            1 => 'B'
        ]);

        $form->submit([
            '1' => 'B',
            '0' => 'A',
        ]);

        $form->createView();

        $this->assertTrue($form->isSynchronized());
        $this->assertArraySubset([
            0 => 'B',
            1 => 'A'
        ], $form->getData());
    }
}