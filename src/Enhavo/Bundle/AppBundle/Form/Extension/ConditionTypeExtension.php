<?php
/**
 * Created by PhpStorm.
 * User: gseidel
 * Date: 16.01.18
 * Time: 14:54
 */

namespace Enhavo\Bundle\AppBundle\Form\Extension;

use Enhavo\Bundle\AppBundle\Form\Config\Condition;
use Enhavo\Bundle\AppBundle\Form\Config\ConditionObserver;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConditionTypeExtension extends AbstractTypeExtension
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'condition' => null,
            'condition_observer' => null
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // subject
        $condition = $options['condition'];
        if($condition instanceof Condition) {
            $view->vars['attr']['data-condition-type'] = json_encode([
                'id' => $condition->getId()
            ]);
        }

        // observer
        if(!is_array($options['condition_observer']))  {
            $conditionObservers = [$options['condition_observer']];
        } else {
            $conditionObservers = $options['condition_observer'];
        }
        $data = [];
        foreach($conditionObservers as $conditionObserver) {
            if($conditionObserver instanceof ConditionObserver) {
                $data[] = [
                    'id' => $conditionObserver->getSubject()->getId(),
                    'values' => $conditionObserver->getValues(),
                    'operator' => $conditionObserver->getOperator()
                ];
            }
        }
        if(count($data) > 0) {
            $view->vars['attr']['data-condition-type-observer'] = json_encode($data);
        }
    }

    public function getExtendedType()
    {
        return FormType::class;
    }
}