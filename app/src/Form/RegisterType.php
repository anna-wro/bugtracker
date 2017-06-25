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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
                    'readonly' => (isset($options['data']) && isset($options['data']['id'])),

                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['registration', 'edit_user']]),
                    new Assert\Length(
                        [
                            'min' => 4,
                            'max' => 32,
                            'groups' => ['registration', 'edit_user'],
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
                        'groups' => ['registration', 'edit_user'],
                    ]),
                    new Assert\Length(
                        [
                            'groups' => ['registration', 'edit_user'],
                            'min' => 6,
                            'max' => 32,
                        ]
                    ),
                ],
            ]
        );
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();

                $normData = $form->getNormData();

                if (isset($normData['id'])) {
                    $data['login'] = isset($normData['login']) ? $normData['login'] : '';
                    $event->setData($data);
                }
            }
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
                'validation_groups' => 'registration',
                'user_repository' => null,

            ]
        );
    }
}