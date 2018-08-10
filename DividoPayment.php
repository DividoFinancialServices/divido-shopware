<?php

namespace DividoPayment;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Payment\Payment;

class DividoPayment extends Plugin
{
    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');
        $options = [
            'name' => 'divido_payment',
            'description' => 'Divido payment',
            'action' => 'DividoPayment',
            'active' => 0,
            'position' => 0,
            'additionalDescription' =>
                '<img src="https://s3-eu-west-1.amazonaws.com/content.divido.com/images/logo-blue-75x23.png"/>'
                . '<div id="payment_desc">'
                . '  Finance your cart'
                . '</div>'
        ];

        $service = $this->container->get('shopware_attribute.crud_service');
        $service->update('s_order_attributes', 'divido_deposit_value', 'float',[
            'displayInBackend' => true,
            'label' => 'Divido Deposit',
            'supportText' => 'The value of the deposit taken',
            'helpText' => 'Deposit value'
        ]);
        $service->update('s_order_attributes', 'divido_finance_id', 'string',[
            'displayInBackend' => true,
            'label' => 'Divido Finance',
            'supportText' => 'The ID of the finance ',
            'helpText' => 'Divido Finance ID'
        ]);

        $installer->createOrUpdate($context->getPlugin(), $options);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        $service = $this->container->get('shopware_attribute.crud_service');
        $service->delete('s_order_attributes', 'divido_finance_id');
        $service->delete('s_order_attributes', 'divido_deposit_value');
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), true);
    }

    /**
     * @param Payment[] $payments
     * @param $active bool
     */
    private function setActiveFlag($payments, $active)
    {
        $em = $this->container->get('models');

        foreach ($payments as $payment) {
            $payment->setActive($active);
        }
        $em->flush();
    }

}
