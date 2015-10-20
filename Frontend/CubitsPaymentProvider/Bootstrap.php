<?php
class Shopware_Plugins_Frontend_CubitsPaymentProvider_Bootstrap
    extends Shopware_Components_Plugin_Bootstrap
{
    public function getCapabilities()
    {
        return array(
            'install' => true,
            'update' => true,
            'enable' => true
        );
    }

    public function getLabel()
    {
        return 'Cubits Payment Plugin';
    }

    public function getVersion()
    {
         return '1.0.0';
    }

    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'supplier' => 'Cubits',
            'description' => 'Bitcoin',
            'support' => 'cubits.com',
            'link' => 'cubits.com'
        );
    }

    public function install()
    {
        $this->createPayment(array(
                'name' => 'payment_cubits',
                'description' => 'Bitcoin',
                'action' => 'payment_cubits',
                'active' => 1,
                'position' => 3,
                'additionalDescription' =>
                    '<!-- paymentLogo -->
                    <img src="/engine/Shopware/Plugins/Community/Frontend/CubitsPaymentProvider/logo.png"/>
                    <!-- paymentLogo --><br/><br/>' .
                    '<div id="payment_desc">
                        Pay safe and securely with Bitcoins.
                    </div>'
        ));
        $this->createForm();
        $this->createEvents();

        return true;
    }

    public function update($oldVersion)
    {
        switch($oldVersion) {
            case '1.0.0' :
            // Things to do to update a version 1.0.0 to the current version
            break;
        }
    }

    public function uninstall()
    {
        return true;
    }

    public function enable()
    {
        $payment = $this->Payment();
        $payment->setActive(true);
        return true;
    }

    public function disable()
    {
        $payment = $this->Payment();
        if ($payment !== null) {
            $payment->setActive(false);
        }
        return true;
    }

    public function Payment()
    {
        return $this->Payments()->findOneBy(array(
            'name' => 'payment_cubits'
        ));
    }

    protected function createForm()
    {
        $form = $this->Form();

        $form->setElement('text', 'cubits_key', array(
            'label' => 'Cubits Key',
            'required' => true
        ));

        $form->setElement('text', 'cubits_secret', array(
            'label' => 'Cubits Secret',
            'required' => true
        ));
    }

    protected function createEvents()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentCubits',
            'onGetControllerPathFrontend'
        );
    }

    public static function onGetControllerPathFrontend()
    {
        return Shopware()->Plugins()->Frontend()->CubitsPaymentProvider()->Path() . 'Controllers/Cubits.php';
    }
}