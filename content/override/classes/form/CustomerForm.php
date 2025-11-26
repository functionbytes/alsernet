<?php

use PrestaShop\PrestaShop\Core\Util\InternationalizedDomainNameConverter;
use Symfony\Component\Translation\TranslatorInterface;

require_once _PS_MODULE_DIR_ . 'alsernetforms/controllers/front/NewslettersController.php';

class CustomerForm extends CustomerFormCore
{
    protected $template = 'customer/_partials/customer-form.tpl';

    private $context;
    private $urls;

    private $customerPersister;
    private $guest_allowed;
    private $passwordRequired = true;

    private $IDNConverter;


    public function __construct(
        Smarty $smarty,
        Context $context,
        TranslatorInterface $translator,
        CustomerFormatter $formatter,
        CustomerPersister $customerPersister,
        array $urls
    ) {

        parent::__construct($smarty, $context,$translator, $formatter, $customerPersister,$urls);

        $this->context = $context;
        $this->urls = $urls;
        $this->customerPersister = $customerPersister;
        $this->IDNConverter = new InternationalizedDomainNameConverter();
    }

    public function setPasswordRequired($passwordRequired)
    {
        $this->passwordRequired = $passwordRequired;

        return $this;
    }

    public function setGuestAllowed($guest_allowed = true)
    {
        $this->formatter->setPasswordRequired(!$guest_allowed);
        $this->setPasswordRequired(!$guest_allowed);
        $this->guest_allowed = $guest_allowed;

        return $this;
    }

    public function validate()
    {

        $emailField = $this->getField('email');
        $id_customer = Customer::customerExists($emailField->getValue(), true, true);
        $customer = $this->getCustomer();
        if ($id_customer && $id_customer != $customer->id) {
            $emailField->addError($this->translator->trans(
                'The email is already used, please choose another one or sign in',
                [],
                'Shop.Notifications.Error'
            ));
        }

        $birthdayField = $this->getField('birthday');
        if (!empty($birthdayField) &&
            !empty($birthdayField->getValue()) &&
            Validate::isBirthDate($birthdayField->getValue(), $this->context->language->date_format_lite)
        ) {
            $dateBuilt = DateTime::createFromFormat(
                $this->context->language->date_format_lite,
                $birthdayField->getValue()
            );
            $birthdayField->setValue($dateBuilt->format('Y-m-d'));
        }

        $passwordField = $this->getField('password');
        if ((!empty($passwordField->getValue()) || $this->passwordRequired)
            && Validate::isPasswd($passwordField->getValue()) === false) {
            $passwordField->addError($this->translator->trans(
                'Password must be between 5 and 72 characters long',
                [],
                'Shop.Notifications.Error'
            ));
        }

        $this->validateFieldsLengths();
        $this->validateFieldsValues();

        //$this->validateByModules();

        return true;
        //return parent::validate();
    }

    private function validateFieldsValues(): void
    {
        $this->validateFieldIsCustomerName('firstname');
        $this->validateFieldIsCustomerName('lastname');
    }

    private function validateFieldIsCustomerName(string $fieldName): void
    {
        $field = $this->getField($fieldName);
        if (null === $field) {
            return;
        }
        $value = $field->getValue();
        if (!empty($value) && false === (bool) Validate::isCustomerName($value)) {
            $field->addError($this->translator->trans(
                'Invalid format.',
                [],
                'Shop.Forms.Errors'
            ));
        }
    }

    public function submit($request = null ,$modalitie = null)
    {


        if ($this->validate()) {

            $clearTextPassword = $this->getValue('password');
            $newPassword = $this->getValue('new_password');

            if($modalitie  == 'register'){

               try {
                   $this->customerPersister->save(
                        $this->getCustomer(),
                        $clearTextPassword,
                        $newPassword,
                        $this->passwordRequired
                         );
               } catch (PrestaShopException $e) {
                  $this->errors[''][] = $this->translator->trans('Could not update your information, please check your data.', [], 'Shop.Notifications.Error');
               }

                $controller = new NewslettersController();

                $controller->registersubscribe([
                    'firstname' => Tools::getValue('firstname'),
                    'lastname' => Tools::getValue('lastname'),
                    'email' => Tools::getValue('email'),
                    'sports' => implode(",", $request['sports']),
                    'iso' => $this->context->language->iso_code,
                    'parties' => Tools::getValue('services'),
                    'birthday' => Tools::getValue('date'),
                    'condition' => Tools::getValue('condition')
                ]);

               return true;

            }elseif($modalitie  == 'identify'){

                try {
                    $this->customerPersister->save(
                        $this->getCustomer(),
                        $clearTextPassword,
                        $newPassword,
                        $this->passwordRequired
                    );
                } catch (PrestaShopException $e) {
                    $this->errors[''][] = $this->translator->trans('Could not update your information, please check your data.', [], 'Shop.Notifications.Error');
                }

            }

            return !$this->hasErrors();

        }

    }

}


