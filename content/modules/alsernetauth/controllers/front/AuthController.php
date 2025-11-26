<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'alsernetforms/controllers/front/NewslettersController.php';
use PrestaShop\PrestaShop\Core\Crypto\Hashing;

class AuthController extends Module
{
    public $module;

    public function __construct(){
        $this->bootstrap = true;
        $this->module =  Module::getInstanceByName("alsernetauth");
        parent::__construct();
    }

    public function register(){

        $context = Context::getContext();
        $email = trim(Tools::getValue('email'));
        $password = trim(Tools::getValue('password'));
        $firstname = trim(Tools::getValue('firstname'));
        $birthday= trim(Tools::getValue('date'));
        $lastname = trim(Tools::getValue('lastname'));
        $iso = trim(Tools::getValue('iso'));
        $id_lang = Language::getIdByIso($iso);
        $sports = trim(Tools::getValue('sports'));
        $condition = Tools::getValue('condition');
        $services = Tools::getValue('services');

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'authcontroller',$iso),
                'data' => [],
            ];
          
        } elseif (!Validate::isPasswd($password)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid password', 'authcontroller',$iso),
                'data' => [],
            ];
        } elseif (!Validate::isName($firstname)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid first name', 'authcontroller',$iso),
                'data' => [],
            ];
        } elseif (!Validate::isName($lastname)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid last name', 'authcontroller',$iso),
                'data' => [],
            ];
        }


        if (Customer::customerExists($email, true, true)) {
            return [
                'status' => 'warning',
                'message' => $this->l('This email is already used, please choose another one or sign in', 'authcontroller',$iso),
                'data' => [],
            ];
        } else {
            
            $hookResult = array_reduce(
                Hook::exec('actionSubmitAccountBefore', array(), null, true),
                function ($carry, $item) {
                    return $carry && $item;
                },
                true
            );

            $customer = new Customer();
            $customer->firstname = $firstname;
            $customer->lastname = $lastname;
            $customer->email = $email;
            $customer->passwd = $this->get('hashing')->hash($password, _COOKIE_KEY_);

            if ($hookResult && $customer->save()) {

                $this->context->updateCustomer($customer);
                $this->context->cart->update();

                $subject = $this->l('Welcome!', 'authcontroller',$iso);
                $mailParams = array(
                    '{email}' => $customer->email,
                    '{lastname}' => $customer->lastname,
                    '{firstname}' => $customer->firstname,
                 );

                if (Mail::Send(
                    $this->context->language->id, 
                    'account', 
                    $subject,
                    $mailParams, 
                    $customer->email, 
                    $customer->firstname . ' ' . $customer->lastname 
                )) {

                Hook::exec('actionCustomerAccountAdd', array(
                            'newCustomer' => $customer,
                ));

                $controller = new NewslettersController();

                $controller->registersubscribe([
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                    'sports' => $sports,
                    'iso' => $iso,
                    'birthday' => $birthday,
                    'parties' => $services,
                    'condition' => $condition,
                ]);

                return [
                    'status' => 'success',
                    'message' => $this->l('You have successfully created a new account.', 'authcontroller',$iso),
                    'url' =>  $this->context->link->getPageLink('my-account', true, $id_lang),
                    'data' => [],
                ];
                
            } else {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while creating the new account.', 'authcontroller',$iso),
                    'url' =>  $this->context->link->getPageLink('my-account', true, $id_lang),
                    'data' => [],
                ];
            }
            }
        }
    }

    public function login(){

        $email = trim(Tools::getValue('email'));
        $password = trim(Tools::getValue('password'));
        $iso = trim(Tools::getValue('iso'));
        $id_lang = Language::getIdByIso($iso);


        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'authcontroller',$iso),
                'data' => [],
            ];
        } elseif (!Validate::isPasswd($password)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid password', 'authcontroller',$iso),
                'data' => [],
            ];
        } elseif (!Tools::getValue('remember')) {
            $this->context->cookie->customer_last_activity = time();
        }

        $customer = new Customer();
        $authentication = $customer->getByEmail($email, $password);

        if (isset($authentication->active) && !$authentication->active) {
            return [
                'status' => 'warning',
                'message' => $this->l('Your account isn\'t available at this time, please contact us', 'authcontroller',$iso),
                'data' => [],
            ];
        } elseif (!$authentication || !$customer->id || $customer->is_guest) {

            return [
                'status' => 'warning',
                'message' => $this->l('Authentication failed.', 'authcontroller',$iso),
                'data' => [],
            ];
            
        } else {

            $this->context->updateCustomer($customer);
            
            return [
                'status' => 'success',
                'message' => $this->l('You have successfully logged in', 'authcontroller',$iso),
                'data' => [],
                'url' =>  $this->context->link->getPageLink('my-account', true, $id_lang),
            ];
        }
    }

    public function resetpassword(){

        $email = trim(Tools::getValue('email'));
        $iso = trim(Tools::getValue('iso'));
        $minTime = 2;

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'authcontroller',$iso),
                'data' => [],
            ];
        }else  {

            $customer = new Customer();
            $customer->getByEmail($email);

            if (is_null($customer->email)) {
                $customer->email = $email;
            }

            if (!Validate::isLoadedObject($customer)) {
                return [
                    'status' => 'success',
                    'message' => $this->l('If is registered in our store, you will receive an email to reset your password. Please check your spam or junk folder. If you do not receive any email, it is because there is no user with the indicated email address', 'authcontroller',$iso),
                    'data' => [],
                ];

            } elseif (!$customer->active) {

                return [
                    'status' => 'warning',
                    'message' => $this->l('You cannot regenerate the password for this account.', 'authcontroller',$iso),
                    'data' => [],
                ];

            } 
            // elseif ((strtotime($customer->reset_password_validity . '+' .$minTime . ' minutes') - time()) > 0) {

            //     return [
            //         'status' => 'warning',
            //         'message' => $this->l('You can regenerate your password only every.', 'alsernetauth', 'authcontroller', 'es').' (' . (int) $minTime . $this->l(' minute(s)', 'authcontroller',$iso),
            //         'data' => [],
            //     ];

            // }
             else {

              
                if (!$customer->hasRecentResetPasswordToken()) {
                    $customer->stampResetPasswordToken();
                    $customer->update();
                }
                
                $subjectResetPassword = $this->l('Password query confirmation!', 'authcontroller',$iso);
              
                $mailParams = array(
                    '{email}' => $customer->email,
                    '{lastname}' => $customer->lastname,
                    '{firstname}' => $customer->firstname,
                    '{url}' => $this->context->link->getPageLink('password', true, null, 'token=' . $customer->secure_key . '&id_customer=' . (int) $customer->id . '&reset_token=' . $customer->reset_password_token),
                );


                if (Mail::Send(
                    $this->context->language->id,
                    'password_query', 
                    $subjectResetPassword, 
                    $mailParams, 
                    $customer->email, 
                    $customer->firstname . ' ' . $customer->lastname 

                )) {
                    
                    return [
                        'status' => 'success',
                        'message' => $this->l('If is registered in our store, you will receive an email to reset your password. Please check your spam or junk folder. If you do not receive any email, it is because there is no user with the indicated email address', 'authcontroller',$iso),
                        'data' => [],
                    ];
                    
                } else {
                    return [
                        'status' => 'warning',
                        'message' => $this->l('An error occurred while sending the email.', 'alsernetauth', 'authcontroller', 'es'). (int) $minTime . $this->l(' minute(s)', 'authcontroller',$iso),
                        'data' => [],
                    ];
                }
            }

        }


    }


    public function validateemail(){


        $email = trim(Tools::getValue('email'));
        $iso = trim(Tools::getValue('iso'));



        if (!Validate::isEmail($email)) {
            return [
                'success' => 'warning',
                'message' => $this->l('Invalid email address', 'authcontroller', $iso),
                'data' => [],
            ];
        }

        $customer = new Customer();
        if ($customer->getByEmail($email)) {
            return [
                'status' => 'success',
                'message' => $this->l('Your email is already registered in our system', 'authcontroller', $iso),
                'data' => [],
            ];
        }else{
            return [
                'status' => 'warning',
                'message' => '',
                'data' => [],
            ];
        }



    }
    public function changepassword(){

        $token = Tools::getValue('token');
        $id_customer = (int) Tools::getValue('id_customer');
        $reset_token = Tools::getValue('reset_token');
        $iso = trim(Tools::getValue('iso'));

        $email = Db::getInstance()->getValue(
            'SELECT `email` FROM ' . _DB_PREFIX_ . 'customer c WHERE c.`secure_key` = \'' . pSQL($token) . '\' AND c.id_customer = ' . $id_customer
        );

        if ($email) {

            $customer = new Customer();
            $customer->getByEmail($email);

                if (!Validate::isLoadedObject($customer)) {
                    return [
                        'status' => 'warning',
                        'message' => $this->l('Customer account not found.', 'authcontroller',$iso),
                        'data' => [],
                    ];
                } elseif (!$customer->active) {
                    return [
                        'status' => 'warning',
                        'message' => $this->l('You cannot regenerate the password for this account.', 'authcontroller',$iso),
                        'data' => [],
                    ];
                } elseif ($customer->getValidResetPasswordToken() !== $reset_token) {

                    return [
                        'status' => 'warning',
                        'message' => $this->l('The password change request expired. You should ask for a new one.', 'authcontroller',$iso),
                        'data' => [],
                    ];

                }

                if (!$password = Tools::getValue('password')) {
                    return [
                        'status' => 'warning',
                        'message' => $this->l('The password is missing: please enter your new password.', 'authcontroller',$iso),
                        'data' => [],
                    ];
                }

                if (!$confirmation = Tools::getValue('confirmation')) {
                    return [
                        'status' => 'warning',
                        'message' => $this->l('The confirmation is empty: please fill in the password confirmation as well.', 'authcontroller',$iso),
                        'data' => [],
                    ];
                }

                if ($password && $confirmation) {
                    if ($password !== $confirmation) {
                        return [
                            'status' => 'warning',
                            'message' => $this->l('The password and its confirmation do not match.', 'authcontroller',$iso),
                            'data' => [],
                        ];
                    }

                    if (!Validate::isPasswd($password)) {
                        return [
                            'status' => 'warning',
                            'message' => $this->l('The password is not in a valid format.', 'authcontroller',$iso),
                            'data' => [],
                        ];
                    }
                }
            
                //$reset_password_validity = $customer->reset_password_validity;
                //$current_time_plus_2_minutes = strtotime('+2 minutes');
            

                //if (!$reset_token || (strtotime($customer->reset_password_validity . ' +' . 2 . ' minutes') < time())) {
                if (!$reset_token) {

                    return [
                        'status' => 'warning',
                        'message' => $this->l('In error occurred with your account, which prevents us from updating the new password. Please report this issue using the contact form.', 'authcontroller',$iso),
                        'data' => [],
                    ];

                } else {

                    $customer->passwd = $this->get('hashing')->hash($password = Tools::getValue('password'));
                    //$customer->reset_password_validity = date('Y-m-d H:i:s', time());
                    
                    if ($customer->update()) {

                        Hook::exec('actionPasswordRenew', ['customer' => $customer, 'password' => $password]);
                        
                        $subject = $this->l('Your new password', 'authcontroller',$iso);
                        
                        $customer->removeResetPasswordToken();
                        $customer->update();

                        $mailParams = [
                            '{email}' => $customer->email,
                            '{firstname}' => $customer->firstname,
                            '{lastname}' => $customer->lastname,
                        ];

                        if (Mail::Send(
                            $this->context->language->id, 
                            'password', 
                            $subject, 
                            $mailParams, 
                            $customer->email, 
                            $customer->firstname . ' ' . $customer->lastname 
                        )) {

                            return [
                                'status' => 'success',
                                'message' => sprintf(
                                    $this->l('Your password has been successfully reset and a confirmation has been sent to your email address: %s', 'authcontroller',$iso),
                                    $customer->email
                                ),
                                'data' => [],
                            ];

                        } else {
                            return [
                                'status' => 'error',
                                'message' => $this->l('An error occurred while sending the confirmation email.', 'authcontroller',$iso),
                                'data' => [],
                            ];
                        }

                    } else {
                        return [
                            'status' => 'warning',
                            'message' => $this->l('In error occurred with your account, which prevents us from updating the new password. Please report this issue using the contact form.', 'authcontroller',$iso),
                            'data' => [],
                        ];
                    }
                }
            }  
    }

    public function l($string, $specific = false, $locale = null){
       
        return $this->getModuleTranslation(
            $this->module,
            $string,
            ($specific) ? $specific : $this->name,
            null,
            false,
            $locale
        );
    }

   
    public  function getModuleTranslation(
        $module,
        $originalString,
        $source,
        $sprintf = null,
        $js = false,
        $locale = null,
        $fallback = true,
        $escape = true
    ) {
        global $_MODULES, $_MODULE, $_LANGADM;

        static $langCache = [];
        static $name = null;
        
        // $_MODULES is a cache of translations for all module.
        // $translations_merged is a cache of wether a specific module's translations have already been added to $_MODULES
        static $translationsMerged = [];


        $name = $module->name;
       
        if (null !== $locale) {
            $iso = Language::getIsoByLocale($locale);
        }

        if (empty($iso)) {
            $iso = Context::getContext()->language->iso_code;
        }

        if (!isset($translationsMerged[$name][$iso])) {
            $filesByPriority = [
                // PrestaShop 1.5 translations
                _PS_MODULE_DIR_ . $name . '/translations/' . $iso . '.php',
                // PrestaShop 1.4 translations
                _PS_MODULE_DIR_ . $name . '/' . $iso . '.php',
                // Translations in theme
                _PS_THEME_DIR_ . 'modules/' . $name . '/translations/' . $iso . '.php',
                _PS_THEME_DIR_ . 'modules/' . $name . '/' . $iso . '.php',
            ];
            foreach ($filesByPriority as $file) {
                if (file_exists($file)) {
                    include_once $file;
                    $_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
                }
            }
            $translationsMerged[$name][$iso] = true;
        }
        

        $string = preg_replace("/\\\*'/", "\'", $originalString);
        $key = md5($string);

        $cacheKey = $name . '|' . $string . '|' . $source . '|' . (int) $js . '|' . $iso;
        if (isset($langCache[$cacheKey])) {
            $ret = $langCache[$cacheKey];
        } else {
            $currentKey = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key;
            $defaultKey = strtolower('<{' . $name . '}prestashop>' . $source) . '_' . $key;

            if ('controller' == substr($source, -10, 10)) {
                $file = substr($source, 0, -10);
                $currentKeyFile = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $file) . '_' . $key;
                $defaultKeyFile = strtolower('<{' . $name . '}prestashop>' . $file) . '_' . $key;
            }

            if (isset($currentKeyFile) && !empty($_MODULES[$currentKeyFile])) {
                $ret = stripslashes($_MODULES[$currentKeyFile]);
            } elseif (isset($defaultKeyFile) && !empty($_MODULES[$defaultKeyFile])) {
                $ret = stripslashes($_MODULES[$defaultKeyFile]);
            } elseif (!empty($_MODULES[$currentKey])) {
                $ret = stripslashes($_MODULES[$currentKey]);
            } elseif (!empty($_MODULES[$defaultKey])) {
                $ret = stripslashes($_MODULES[$defaultKey]);
            } elseif (!empty($_LANGADM)) {
                // if translation was not found in module, look for it in AdminController or Helpers
                $ret = stripslashes(Translate::getGenericAdminTranslation($string, $key, $_LANGADM));
            } else {
                $ret = stripslashes($string);
            }

            if (
                $sprintf !== null &&
                (!is_array($sprintf) || !empty($sprintf)) &&
                !(count($sprintf) === 1 && isset($sprintf['legacy']))
            ) {
                $ret = Translate::checkAndReplaceArgs($ret, $sprintf);
            }

            if ($js) {
                $ret = addslashes($ret);
            } elseif ($escape) {
                $ret = htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
            }

            if ($sprintf === null) {
                $langCache[$cacheKey] = $ret;
            }
        }

        if (!is_array($sprintf) && null !== $sprintf) {
            $sprintf_for_trans = [$sprintf];
        } elseif (null === $sprintf) {
            $sprintf_for_trans = [];
        } else {
            $sprintf_for_trans = $sprintf;
        }

        if ($ret === $originalString && $fallback) {
            $ret = Context::getContext()->getTranslator()->trans($originalString, $sprintf_for_trans, null, $locale);
        }

        return $ret;
    }

    
}



