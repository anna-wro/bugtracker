<?php
/**
 * Project type.
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;

/**
 * Class ProjectType.
 *
 * @package Form
 */
class ProjectType extends AbstractType
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
                'label' => 'label.project_name',
                'required' => true,
                'attr' => [
                    'max_length' => 45
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['project-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['project-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                    new CustomAssert\UniqueProject(
                        [
                            'groups' => ['project-default'],
                            'repository' => isset($options['project_repository']) ? $options['project_repository'] : null,
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
                    'placeholder' => 'placeholder.desc',
                ],
                'constraints' => [
                    new Assert\Length(
                        [
                            'groups' => ['project-default'],
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
                'label' => 'label.project_start_date',
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
            'end_date',
            DateType::class,
            [
                'label' => 'label.project_end_date',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'string',
                'constraints' => [
                    new Assert\Date(),
                    new Assert\GreaterThan('start_date')
                ],
            ]
        );

    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'project_type';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'project-default',
                'project_repository' => null,
            ]
        );
    }
}