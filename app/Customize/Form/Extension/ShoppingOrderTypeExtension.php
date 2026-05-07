<?php

namespace Customize\Form\Extension;

use Customize\EventSubscriber\Subscription\SubscriptionShoppingCompleteSubscriber;
use Eccube\Form\Type\Shopping\OrderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

class ShoppingOrderTypeExtension extends AbstractTypeExtension
{
    private RequestStack $requestStack;
    private bool $subscriptionEnabled;
    private bool $allowTestInterval;

    public function __construct(
        RequestStack $requestStack,
        bool $subscriptionEnabled,
        bool $allowTestInterval
    ) {
        $this->requestStack = $requestStack;
        $this->subscriptionEnabled = $subscriptionEnabled;
        $this->allowTestInterval = $allowTestInterval;
    }

    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // GMO direct card fields
        $builder
            ->add('gmo_card_no', TextType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('gmo_card_expire', TextType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('gmo_card_security_code', TextType::class, [
                'mapped' => false,
                'required' => false,
            ]);

        if (!$this->subscriptionEnabled) {
            return;
        }

        $choices = [
            'Mỗi tháng (1)' => 'monthly_1',
            'Mỗi 3 tháng' => 'monthly_3',
            'Mỗi tuần' => 'weekly_1',
        ];
        if ($this->allowTestInterval) {
            $choices = array_merge([
                '[TEST] Mỗi 10 phút' => 'test_10m',
            ], $choices);
        }

        $builder
            ->add('subscription_enabled', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Mua định kỳ (subscription)',
            ])
            ->add('subscription_cycle', ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'choices' => $choices,
                'placeholder' => 'Chọn chu kỳ (khi đã tick subscription)',
                'label' => 'Chu kỳ thanh toán',
            ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($choices): void {
            $form = $event->getForm();
            $request = $this->requestStack->getMainRequest();

            /** @var \Eccube\Entity\Order $Order */
            $Order = $event->getData();
            if (!$request || 'POST' !== $request->getMethod() || !$Order->getPreOrderId()) {
                return;
            }

            $session = $request->hasSession() ? $request->getSession() : null;
            if (!$session) {
                return;
            }

            $subscribe = true === ($form->get('subscription_enabled')->getData());
            $cycle = (string) ($form->get('subscription_cycle')->getData() ?? '');

            if (!$subscribe || '' === $cycle) {
                $bag = (array) $session->get(SubscriptionShoppingCompleteSubscriber::SESSION_SUBSCRIPTION_BAG);
                unset($bag[$Order->getPreOrderId()]);
                $session->set(SubscriptionShoppingCompleteSubscriber::SESSION_SUBSCRIPTION_BAG, $bag);

                return;
            }

            if (!in_array($cycle, array_values($choices), true)) {
                return;
            }

            $bag = (array) $session->get(SubscriptionShoppingCompleteSubscriber::SESSION_SUBSCRIPTION_BAG);
            $bag[$Order->getPreOrderId()] = [
                'enabled' => true,
                'cycle' => $cycle,
            ];
            $session->set(SubscriptionShoppingCompleteSubscriber::SESSION_SUBSCRIPTION_BAG, $bag);
        });
    }
}
