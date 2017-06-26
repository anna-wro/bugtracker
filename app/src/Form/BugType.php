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
                'required' => true,
                'attr' => [
                    'max_length' => 1024,
                    'placeholder' => 'placeholder.bug_desc',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['bug-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['bug-default'],
                            'min' => 10,
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
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'attr' => [
                    'data-large-default' => 'true',
                    'data-large-mode' => 'true',
                    'data-min-year' => '2016',
                    'data-lang' => $options['locale'],
                    'data-format' => 'd.m.Y',
                    'data-lock' => 'to',
                    'data-theme' => "bugtracker"
                ],
                'data' => $formattedDate,
                'constraints' => [
                    new Assert\Date([
                        'groups' => ['bug-default']
                    ]),
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
            'project_id',
            ChoiceType::class,
            [
                'label' => 'label.bug_project',
                'invalid_message' => 'message.project_not_selected',
                'required' => true,
                'choices' => $this->prepareAvailableProjects($options['projects_repository'], ($options['user_id']), $options['is_admin']),
                'choice_translation_domain' => 'messages',
                'constraints' => [
                    new Assert\NotNull(
                        ['groups' => ['bug-default']]
                    ),
                ],
            ]
        );
        $builder->add(
            'type_id',
            ChoiceType::class,
            [
                'label' => 'label.bug_type',
                'required' => true,
                'choices' => $this->prepareOptionsForChoices($options['types_repository']),
                'choice_translation_domain' => 'messages',
                'constraints' => [
                    new Assert\NotNull(
                        ['groups' => ['bug-default']]
                    ),
                ],
            ]
        );
        $builder->add(
            'priority_id',
            ChoiceType::class,
            [
                'label' => 'label.bug_priority',
                'required' => true,
                'choices' => $this->prepareOptionsForChoices($options['priorities_repository']),
                'choice_translation_domain' => 'messages',
                'data' => 4,
                'constraints' => [
                    new Assert\NotNull(
                        ['groups' => ['bug-default'],]
                    ),
                ],
            ]
        );
        $builder->add(
            'status_id',
            ChoiceType::class,
            [
                'label' => 'label.bug_status',
                'required' => true,
                'choices' => $this->prepareOptionsForChoices($options['statuses_repository']),
                'choice_translation_domain' => 'messages',
                'data' => 1,
                'constraints' => [
                    new Assert\NotNull(
                        ['groups' => ['bug-default']]
                    ),
                ],
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
                'projects_repository' => null,
                'types_repository' => null,
                'priorities_repository' => null,
                'statuses_repository' => null,
                'user_id' => null,
                'project_id' => null,
                'locale' => null,
                'is_admin' => null
            ]
        );
    }

    protected function prepareOptionsForChoices($repository)
    {
        $types = $repository->findAll();
        $choices = [];

        foreach ($types as $type) {
            $choices[$type['name']] = $type['id'];
        }

        return $choices;
    }

    protected function prepareAvailableProjects($repository, $userId, $isAdmin)
    {
        $projects = $repository->findOptionsForUser($userId, $isAdmin);
        $choices = [];

        foreach ($projects as $project) {
            $choices[$project['name']] = $project['id'];
        }

        return $choices;
    }

}