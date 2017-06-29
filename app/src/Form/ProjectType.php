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
                            'userId' => isset($options['user_id']) ? $options['user_id'] : null,
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
                    new Assert\Length([
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
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'attr' => [
                    'data-large-default' => 'true',
                    'data-large-mode' => 'true',
                    'data-lang' => $options['locale'],
                    'data-format' => 'd.m.Y',
                    'data-theme' => "bugtracker",
                    'data-modal' => 'true'
                ],
                'data' => $formattedDate,
                'constraints' => [
                    new Assert\Date([
                        'groups' => ['project-default'],
                    ]),
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
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'attr' => [
                    'class' => 'is-1-2',
                    'data-large-default' => 'true',
                    'data-large-mode' => 'true',
                    'data-lang' => $options['locale'],
                    'data-format' => 'd.m.Y',
                    'data-theme' => "bugtracker",
                    'data-min-year' => "2000",
                    'data-init-set' => 'false',
                ],
                'constraints' => [
                    new Assert\Date([
                        'groups' => ['project-default'],
                    ]),
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
                'user_id' => null,
                'locale' => null,
            ]
        );
    }
}