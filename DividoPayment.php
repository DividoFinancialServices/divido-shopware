<?php
/**
 * Divido Payment Service
 *
 * PHP version 5.5
 *
 * @category  Payment_Gateway
 * @package   DividoPayment
 * @author    Original Author <jonthan.carter@divido.com>
 * @author    Another Author <andrew.smith@divido.com>
 * @copyright 2014-2018 Divido Financial Services
 * @license   GNU General Public License family
 * @link      http://github.com/DividoFinancialServices/divido-shopware
 * @since     File available since Release 1.0.0
 */
namespace DividoPayment;

use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Payment\Payment;

/**
 * Divido Payment
 *
 * PHP version 5.5
 *
 * @category  Payment_Gateway
 * @package   DividoPayment
 * @author    Original Author <jonthan.carter@divido.com>
 * @author    Another Author <andrew.smith@divido.com>
 * @copyright 2014-2018 Divido Financial Services
 * @license   GNU General Public License family
 * @link      http://github.com/DividoFinancialServices/divido-shopware
 * @since     File available since Release 1.0.0
 */
class DividoPayment extends Plugin
{
    /**
     * Install context
     *
     * @param InstallContext $context The install context
     *
     * @return void
     */
    public function install(InstallContext $context)
    {
        /*
         * @var \Shopware\Components\Plugin\PaymentInstaller $installer Installer
         */
        $installer = $this->container->get('shopware.plugin_payment_installer');
        $options = [
            'name' => 'divido_payment',
            'description' => 'Divido payment',
            'action' => 'DividoPayment',
            'active' => 1,
            'position' => 0,
            'additionalDescription' =>
                '<img src="https://s3-eu-west-1.amazonaws.com/content.divido.com/images/logo-blue-75x23.png"/>' //
                . '<div id="payment_desc">'
                . '  Finance your cart'
                . '</div>'
        ];

        $service = $this->container->get('shopware_attribute.crud_service');
        $service->update(
            's_order_attributes',
            'divido_deposit_value',
            'float',
            [
            'displayInBackend' => true,
            'label' => 'Divido Deposit',
            'supportText' => 'The value of the deposit taken',
            'helpText' => 'Deposit value'
            ]
        );
        $service->update(
            's_order_attributes',
            'divido_finance_id',
            'string',
            [
            'displayInBackend' => true,
            'label' => 'Divido Finance',
            'supportText' => 'The ID of the finance ',
            'helpText' => 'Divido Finance ID'
            ]
        );

        $em = $this->container->get('models');
        $schemaTool = new SchemaTool($em);
        $schemaTool->updateSchema(
            [ $em->getClassMetadata(\DividoPayment\Models\Plan::class) ],
            true
        );

        $service->update(
            's_articles_attributes',
            'divido_finance_plans',
            'multi_selection',
            [
                'entity' => \DividoPayment\Models\Plan::class,
                'displayInBackend' => true,
                'label' => 'Divido Finance Plans',
                'supportText' => 'The plans available to the merchant',
                'helpText' => 'Finance Plans'
            ]
        );

        $installer->createOrUpdate($context->getPlugin(), $options);
    }

    /**
     * Uninstall context
     *
     * @param UninstallContext $context
     *
     * @return void
     */
    public function uninstall(UninstallContext $context)
    {
        $service = $this->container->get('shopware_attribute.crud_service');
        $service->delete('s_order_attributes', 'divido_finance_id');
        $service->delete('s_order_attributes', 'divido_deposit_value');
        $service->delete('s_articles_attributes', 'divido_finance_plans');
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }

    /**
     * Deactivating Plugin
     *
     * @param DeactivateContext $context Context
     *
     * @return void
     */
    public function deactivate(DeactivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }

    /**
     * Activating Plugin context
     *
     * @param ActivateContext $context Context
     *
     * @return void
     */
    public function activate(ActivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), true);
    }

    /**
     * Set Active
     *
     * @param Payment[] $payments activate in payments
     * @param bool      $active
     *
     * @return void
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
