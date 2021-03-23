<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Discountonemailsubscription extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'discountonemailsubscription';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.1';
        $this->author = 'Evolutive group';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Discount on email subscription');
        $this->description = $this->l('Send an email with voucher to customer email subscription');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('actionNewsletterRegistrationAfter') &&
            Db::getInstance()->execute('ALTER TABLE  `'._DB_PREFIX_.'customer` ADD  `discountonemailsubscription` TINYINT(1) NOT NULL DEFAULT \'0\'');
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            Db::getInstance()->execute('ALTER TABLE  `'._DB_PREFIX_.'customer` DROP `discountonemailsubscription`');
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitDiscountonemailsubscriptionModule')) == true) {
            $this->postProcess();
        }

        return $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDiscountonemailsubscriptionModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $options = array(
            1 => array('id_discount_type' => 1, 'name' => $this->l('Discount on order (%)')),
            2 => array('id_discount_type' => 2, 'name' => $this->l('Discount on order (amount)')),
            3 => array('id_discount_type' => 3, 'name' => $this->l('Free shiping'))
        );
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Status'),
                        'name' => 'DISCOUNTONEMAILSUBSCRIPTION_STATUS',
                        'is_bool' => true,
//                        'desc' => $this->l('Enabled'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Type'),
                        'desc' => $this->l('Choose a discount\'s type'),
                        'name' => 'DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_TYPE',
                        'required' => true,
                        'options' => array(
                            'query' => $options,
                            'id' => 'id_discount_type',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Discount amount'),
                        'name' => 'DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_AMOUNT',
                        'class' => 'lg',
                        'required' => true,
                        'desc' => $this->l('Amount of the discount. Not insert percent, only number.')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Discount validity'),
                        'name' => 'DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_VALIDITY',
                        'class' => 'lg',
                        'required' => true,
                        'desc' => $this->l('Validity of the discount (month).')
                    ),

                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'DISCOUNTONEMAILSUBSCRIPTION_STATUS' => Configuration::get('DISCOUNTONEMAILSUBSCRIPTION_STATUS', true),
            'DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_TYPE' => Configuration::get('DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_TYPE'),
            'DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_AMOUNT' => Configuration::get('DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_AMOUNT', null),
            'DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_VALIDITY' => Configuration::get('DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_VALIDITY', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function getCustomerByEmail($email)
    {
        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'customer`
                WHERE `email` = \'' . pSQL($email) . '\'
                    ' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER);

        return Db::getInstance()->getRow($sql);
    }

    public function getEmailSubscription($email)
    {
        $sql = 'SELECT `email`
                FROM `' . _DB_PREFIX_ . 'emailsubscription`
                WHERE `email` = \'' . pSQL($email) . '\'
                    ' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER);

        return Db::getInstance()->getValue($sql);
    }
    public function updateCustomerInfo($id_customer)
    {
        $customerInfo = new Customer($id_customer);
        if ($customerInfo->discountonemailsubscription == 0) {
            $customerInfo->discountonemailsubscription = 1;
            $customerInfo->update();
        }

    }


    public function sendVoucher($customer)
    {

        $voucher = new CartRule();
        $voucher->id_customer = (int) ($customer->id);
        $discount_amount = Configuration::get('DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_AMOUNT');
        $cart_rule_name = $this->l('Newsletter voucher ') . '- Ref: ' . (int) ($voucher->id_customer) . ' - ' . date('Y');
        array('1' => $cart_rule_name, '2' => $cart_rule_name);
        $languages = Language::getLanguages();
        $array_name = array();
        foreach ($languages as $language) {
            $array_name[$language['id_lang']] = $cart_rule_name;
        }
        $voucher->name = $array_name;
        $voucher->description = $this->l('Voucher newsletter!');
        $voucher->id_currency = Configuration::get('PS_CURRENCY_DEFAULT'); /* Old */
        $voucher->quantity = 1;
        $code = Tools::passwdGen();
        while (CartRule::cartRuleExists($code)) { // let's make sure there is no duplicate
            $code = Tools::passwdGen();
        }
        $voucher->code = $code;
        $voucher->quantity_per_user = 1;
        $voucher->reduction_tax = 1; // tax inclut
        $voucher->partial_use = false;
        $voucher->product_restriction = false;
        $voucher->cart_rule_restriction = true;
        $voucher->date_from = date('Y-m-d');
        $voucher->date_to = strftime('%Y-%m-%d', strtotime('+'.Configuration::get('DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_VALIDITY').' month'));
        $voucher->minimum_amount = 0;
        $voucher->active = true;
        switch ((int) Configuration::get('DISCOUNTONEMAILSUBSCRIPTION_DISCOUNT_TYPE')) {
            case '1':
                $voucher->reduction_percent = $discount_amount;
                break;
            case '2':
                $voucher->reduction_amount = $discount_amount;
                break;
            case '3':
                $voucher->free_shipping = true;
                break;
        }
        if ($voucher->add()) {
            $this->updateCustomerInfo($customer->id);
             Mail::Send(
                $this->context->language->id,
                'newsletter_voucher',
                $this->l('Newsletter voucher'),
                array(
                    '{discount}' => $code,
                ),
                $customer->email,
                null,
                null,
                null,
                null,
                null,
                dirname(__FILE__) . '/mails/',
                false,
                $this->context->shop->id
            );
        }

    }

    public function hookActionCustomerAccountAdd($params)
    {
        if (empty($params['newCustomer'])) {
            return;
        }
        $id_shop = $params['newCustomer']->id_shop;
        $email = $params['newCustomer']->email;
        if (Validate::isEmail($email)) {
            if ($params['newCustomer']->newsletter && Configuration::get('DISCOUNTONEMAILSUBSCRIPTION_STATUS')) {
                $this->sendVoucher($params['newCustomer']);
            }
//            else {
//
//            }

            return (bool) Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'emailsubscription WHERE id_shop=' . (int) $id_shop . ' AND email=\'' . pSQL($email) . "'");
        }

        return true;
    }

    public function hookActionNewsletterRegistrationAfter($params)
    {
        $email = $params['email'];
        $customer = $this->getCustomerByEmail($email);
        if (Validate::isEmail($email)) {
            if ($customer && $customer['discountonemailsubscription'] == 0 && Configuration::get('DISCOUNTONEMAILSUBSCRIPTION_STATUS')) {
                $customerObj = new Customer($customer['id_customer']);
                $this->sendVoucher($customerObj);
            }
        }

        return true;
    }
}
