<?php
/**
 * Register form.
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;

/**
 * Class RegisterType
 *
 * @package Form
 */
class RegisterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'login',
            TextType::class,
            [
                'label' => 'label.login',
                'required' => true,
                'attr' => [
                    'max_length' => 32,

                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['registration']]),
                    new Assert\Length(
                        [
                            'min' => 4,
                            'max' => 32,
                            'groups' => ['registration'],
                        ]
                    ),
                    new CustomAssert\UniqueValue(
                        [
                            'groups' => ['registration'],
                            'message' => 'message.username_taken',
                            'repository' => isset($options['user_repository']) ? $options['user_repository'] : null,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'password',
            RepeatedType::class,
            [
                'type' => PasswordType::class,
                'invalid_message' => 'message.password_not_repeated',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => true,
                'first_options'  => array('label' => 'label.password'),
                'second_options' => array('label' => 'label.repeat.password'),
                'constraints' => [
                    new Assert\NotBlank([
                        'groups' => ['registration'],
                    ]),
                    new Assert\Length(
                        [
                            'groups' => ['registration'],
                            'min' => 6,
                            'max' => 32,
                        ]
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
        return 'register_type';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => array('registration'),
                'user_repository' => null,

            ]
        );
    }
}