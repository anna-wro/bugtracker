<?php
/**
 * Bug type.
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;

/**
 * Class BugType.
 *
 * @package Form
 */
class BugType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'label.bug_name',
                'required' => true,
                'attr' => [
                    'max_length' => 45
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['bug-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['bug-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                    new CustomAssert\UniqueBug(
                        [
                            'groups' => ['bug-default'],
                            'repository' => isset($options['bug_repository']) ? $options['bug_repository'] : null,
                            'elementId' => isset($options['data']['id']) ? $options['data']['id'] : null,
                        ]
                    ),
                ],
            ]
        );
        $today = new \DateTime();
        $formattedDate = $today->format('Y-m-d');

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label' => 'label.project_description',
                'required' => false,
                'attr' => [
                    'max_length' => 1024,
                    'placeholder' => 'placeholder.bug_desc',
                ],
                'constraints' => [
                    new Assert\Length(
                        [
                            'groups' => ['bug-default'],
                            'max' => 1024,
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'start_date',
            DateType::class,
            [
                'label' => 'label.bug_start_date',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'string',
                'data' => $formattedDate,
                'constraints' => [
                    new Assert\Date(),
                ],
            ]
        );

        $builder->add(
            'reproduction',
            TextareaType::class,
            [
                'label' => 'label.bug_reprodution',
                'required' => false,
                'attr' => [
                    'max_length' => 1024,
                    'placeholder' => 'placeholder.repr',
                ],
                'constraints' => [
                    new Assert\Length(
                        [
                            'groups' => ['bug-default'],
                            'max' => 1024,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'expected_result',
            TextareaType::class,
            [
                'label' => 'label.bug_expected_result',
                'required' => false,
                'attr' => [
                    'max_length' => 1024,
                    'placeholder' => 'placeholder.expected',
                ],
                'constraints' => [
                    new Assert\Length(
                        [
                            'groups' => ['bug-default'],
                            'max' => 1024,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'type_id',
            ChoiceType::class,
            [
                'label' => 'label.bug_type',
                'required' => false,
                'choices' => $this->prepareTypesForChoices($options['types_repository']),
            ]
        );

    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'bug_type';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'bug-default',
                'bug_repository' => null,
                'types_repository' => null,
            ]
        );
    }

    protected function prepareTypesForChoices($typeRepository)
    {
        $types = $typeRepository->findAll();
        $choices = [];

        foreach ($types as $type) {
            $choices[$type['name']] = $type['id'];
        }

        return $choices;
    }
}