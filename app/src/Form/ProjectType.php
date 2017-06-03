<?php
/**
 * Project type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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
                ],
            ]
        );
        $builder->add(
            'description',
            TextType::class,
            [
                'label' => 'label.project_description',
                'required' => false,
                'attr' => [
                    'max_length' => 1024
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
            ]
        );
    }
}