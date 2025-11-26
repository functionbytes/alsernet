<?php

if (!defined('_PS_VERSION_')) {
    exit;
}


class FormController extends Module
{
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->module = Module::getInstanceByName("alsernetforms");
        parent::__construct();
    }

    public function compromise()
    {

        $context = Context::getContext();
        $email = trim(Tools::getValue('email'));
        $phone = trim(Tools::getValue('phone'));
        $iso = trim(Tools::getValue('iso'));


        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];

        } elseif (!Validate::isPhoneNumber($phone)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid phone', 'formcontroller', $iso),
                'data' => [],
            ];
        }


        if (isset($email) && isset($phone)) {

            $subjectAlert = $this->l('Better price compromise!', 'formcontroller', 'es');
            $subjectNotification = $this->l('Fitting subject notification!', 'formcontroller', $iso);
            $notificationAlvarez = 'formulariosprestashop@a-alvarez.com';
            $customerEmail = $email; // Asume que $email es la dirección del cliente

            $dataAlert = [
                '{phone}' => $phone,
                '{email}' => $email,
            ];

            // Enviar correo a notificationAlvarez
            if (!Mail::Send(
                1,
                'fitting_alert',
                $subjectAlert,
                $dataAlert,
                $notificationAlvarez
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }


            $token = md5($customerEmail); // Este es un ejemplo, asegúrate de usar el método correcto para obtener el token
            $verificationLink = $this->context->link->getModuleLink('ps_emailsubscription', 'verification', ['token' => $token], true);

            $dataNotification = [
                '{verification_link}' => $verificationLink,
            ];

            // Enviar correo al cliente
            if (!Mail::Send(
                (int)$this->context->language->id,
                'fitting_notification',
                $subjectNotification,
                $dataNotification,
                $customerEmail
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created a new account.', 'formcontroller', $iso),
                'data' => [],
            ];
        }
    }

    public function fitting()
    {

        $context = Context::getContext();
        $email = trim(Tools::getValue('email'));
        $phone = trim(Tools::getValue('phone'));
        $iso = trim(Tools::getValue('iso'));


        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];

        } elseif (!Validate::isPhoneNumber($phone)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid phone', 'formcontroller', $iso),
                'data' => [],
            ];
        }


        if (isset($email) && isset($phone)) {

            $subjectAlert = $this->l('Fitting subject alert!', 'formcontroller', 'es');
            $subjectNotification = $this->l('Fitting subject notification!', 'formcontroller', $iso);
            $notificationAlvarez = 'anacup@a-alvarez.com';
            $customerEmail = $email; // Asume que $email es la dirección del cliente

            $dataAlert = [
                '{phone}' => $phone,
                '{email}' => $email,
            ];

            // Enviar correo a notificationAlvarez
            if (!Mail::Send(
                1,
                'fitting_alert',
                $subjectAlert,
                $dataAlert,
                $notificationAlvarez
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }


            $token = md5($customerEmail); // Este es un ejemplo, asegúrate de usar el método correcto para obtener el token
            $verificationLink = $this->context->link->getModuleLink('ps_emailsubscription', 'verification', ['token' => $token], true);

            $dataNotification = [
                '{verification_link}' => $verificationLink,
            ];

            // Enviar correo al cliente
            if (!Mail::Send(
                (int)$this->context->language->id,
                'fitting_notification',
                $subjectNotification,
                $dataNotification,
                $customerEmail
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created a new account.', 'formcontroller', $iso),
                'data' => [],
            ];
        }
    }

    public function demondayorder()
    {
        $context = Context::getContext();
        $id_lang = $context->language->id;
        $id_shop = $context->shop->id;
        $id_order_state = 26;

        $iso = trim(Tools::getValue('iso'));
        $id_customer = Tools::getValue('id_customer');
        $id_address = Tools::getValue('id_address');
        $id_product = Tools::getValue('id_product');

        $groups = [
            'group_192' => Tools::getValue('group_192'),
            'group_193' => Tools::getValue('group_193'),
            'group_194' => Tools::getValue('group_194'),
            'group_195' => Tools::getValue('group_195')
        ];

        // Check for the required date variant
        if (empty($groups['group_192'])) {
            return [
                'status' => 'warning',
                'message' => $this->l('There was an error creating the orderfound.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        // Dynamically construct the SQL query based on available group values
        $attributes_conditions = [];
        foreach ($groups as $key => $group) {
            if (!empty($group)) {
                $attributes_conditions[] = "EXISTS(SELECT 1 FROM `" . _DB_PREFIX_ . "product_attribute_combination` pac WHERE pac.`id_product_attribute`=pa.`id_product_attribute` AND pac.`id_attribute`=" . (int)$group . ")";
            }
        }

        $sql = 'SELECT pa.`id_product_attribute`
            FROM `' . _DB_PREFIX_ . 'product_attribute` pa
            WHERE pa.`id_product` = ' . (int)$id_product . '
            AND ' . implode(' AND ', $attributes_conditions);

        $id_product_attribute = Db::getInstance()->getValue($sql);

        if (!$id_product_attribute) {
            return [
                'status' => 'warning',
                'message' => $this->l('No valid product attributes found.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        // Fetch names of selected attributes
        $attribute_names = [];
        foreach ($groups as $key => $group) {
            if (!empty($group)) {
                $sql = "SELECT name FROM " . _DB_PREFIX_ . "attribute_lang WHERE id_attribute = " . (int)$group;
                $attribute_names[$key] = Db::getInstance()->getValue($sql);
            }
        }


        $description = [];

        if (!empty($attribute_names['group_192'])) {
            $description[] = 'Fecha ' . $attribute_names['group_192'];
        }
        if (!empty($attribute_names['group_193'])) {
            $description[] = 'en horario de ' . $attribute_names['group_193'];
        }
        if (!empty($attribute_names['group_194'])) {
            $description[] = 'en la tienda ' . $attribute_names['group_194'];
        }
        if (!empty($attribute_names['group_195'])) {
            $description[] = 'de la marca ' . $attribute_names['group_195'];
        }

        // Prepare product details
        $product = new Product((int)$id_product, true, $id_lang);
        $product->id_product = (int)$id_product;
        $product->id_product_attribute = (int)$id_product_attribute;
        // $product->name = 'Demo Day';
        $product->attributes = implode(' ', $description);
        $product->price = 0;
        $product->cart_quantity = 1;

        // Create Cart and add the product
        $customer = new Customer((int)$id_customer);
        $currency = new Currency((int)$context->currency->id, null, (int)$id_shop);
        $cart = new Cart();
        $cart->id_currency = $currency->id;
        $cart->id_lang = $id_lang;
        $cart->add();
        $cart->updateQty(1, $id_product, $id_product_attribute, false, 'up', $id_address);

        // Update stock
        $qty_disponible = StockAvailable::getQuantityAvailableByProduct((int)$id_product, (int)$id_product_attribute);
        StockAvailable::setQuantity((int)$id_product, $id_product_attribute, (int)$qty_disponible - 1);

        // Create the order
        $order = new Order();
        $order->product_list = $product;
        $order->id_customer = (int)$id_customer;
        $order->id_address_invoice = (int)$id_address;
        $order->id_address_delivery = (int)$id_address;
        $order->id_currency = $currency->id;
        $order->id_cart = (int)$cart->id;
        $order->reference = $order->generateReference();
        $order->id_shop = (int)$id_shop;
        $order->secure_key = $customer->secure_key;
        $order->payment = 'Demo Day';
        $order->module = 'AddisDemoday';
        $order->id_carrier = 0;
        $order->total_paid = 0;
        $order->conversion_rate = $currency->conversion_rate;
        $amount_paid = 0;
        $order->total_paid_real = 0;
        $order->total_products = 0;
        $order->total_products_wt = 0;
        $order->total_discounts_tax_excl = 0;
        $order->total_discounts_tax_incl = 0;
        $order->total_discounts = 0;
        $order->total_shipping_tax_excl = 0;
        $order->total_shipping_tax_incl = 0;
        $order->total_shipping = 0;
        $order->total_wrapping_tax_excl = 0;
        $order->total_wrapping_tax_incl = 0;
        $order->total_wrapping = 0;
        $order->total_paid_tax_excl = 0;
        $order->total_paid_tax_incl = 0;
        $order->total_paid = 0;
        $order->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
        $order->round_type = Configuration::get('PS_ROUND_TYPE');
        $order->invoice_date = '0000-00-00 00:00:00';
        $order->delivery_date = '0000-00-00 00:00:00';

        if (!$order->add()) {
            return [
                'status' => 'warning',
                'message' => $this->l('There was an error creating the order.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        // Process order details and history
        $order_detail = new OrderDetail(null, null, $context);
        $order_detail->createListDemoday($order, $cart, $id_order_state, $order->product_list);

        $new_history = new OrderHistory();
        $new_history->id_order = (int)$order->id;
        $new_history->changeIdOrderState((int)$id_order_state, $order, true);


        if (Validate::isEmail($customer->email)) {

            $attributes_array_custom = Product::getAttributesArray((int)$product->id, (int)$product->id_product_attribute, Context::getContext()->language->id, Context::getContext()->shop->id);

            /*$data = array(
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{email}' => $customer->email,
                '{order_name}' => $order->reference,
                '{id_order}' => $order->id,
                '{product_description_short}' => $product->description_short,
                '{demoday_name}' => $product->name,
                '{demoday_day}' => 'Día: ' . $attributes_array_custom[1]['attribute_name'],
                '{demoday_hour}' => 'Hora: ' . $attributes_array_custom[2]['attribute_name'],
                '{demoday_location}' => 'Lugar: ' . $attributes_array_custom[0]['attribute_name'],
                '{demoday_manufacturer_name}' => $product->manufacturer_name,
            );*/

            $data = array(
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{email}' => $customer->email,
                '{order_name}' => $order->reference,
                '{id_order}' => $order->id,
                '{product_description_short}' => $product->description_short,
                '{demoday_name}' => $product->name,
                '{demoday_hour}' => 'Hora: ' . $attributes_array_custom[1]['attribute_name'],
                '{demoday_location}' => 'Día y Lugar: ' . $attributes_array_custom[0]['attribute_name'],
                '{demoday_manufacturer_name}' => $product->manufacturer_name,
            );


            if (!Mail::Send(
                (int)$this->context->language->id,
                'demoday',
                $this->l('Inscripción en ', 'formcontroller', $iso) . ' ' . $product->name,
                $data,
                $customer->email,
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('Se ha producido un error al enviar la notificación a Álvarez.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }


            return [
                'status' => 'success',
                'message' => $this->l('Se ha creado la reserva del Demo Day.', 'formcontroller', $iso),
                'data' => [
                    'order_reference' => $order->reference,
                    'order_id' => $order->id
                ],
            ];

        }


    }

    public function demondayvalidation()
    {
        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $id_product = trim(Tools::getValue('product'));
        $phone = trim(Tools::getValue('phone'));
        $iso = trim(Tools::getValue('iso'));

        // Validar email
        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        // Validar número de teléfono
        if (!Validate::isPhoneNumber($phone)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid phone', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        // Verificar si email y teléfono están presentes
        if (!$email || !$phone) {
            return [
                'status' => 'warning',
                'message' => $this->l('There was an error creating the order', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        // Si el cliente ya existe
        if (Customer::getCustomersByEmail($email)) {

            $id_customer = Customer::getCustomersByEmail($email)[0]['id_customer'];;

            $id_address = Db::getInstance()->getValue(
                'SELECT id_address FROM ' . _DB_PREFIX_ . 'address WHERE id_customer = ' . (int)$id_customer . ' AND alias LIKE "%Demo Day%"'
            );

            if (!$id_address) {

                $address = new Address();
                $address->id_customer = $id_customer;
                $address->firstname = $firstname;
                $address->lastname = $lastname;
                $address->dni = '00000000A';
                $address->address1 = 'DIRECCION DEMODAY';
                $address->postcode = '00000';
                $address->city = 'CIUDAD DEMODAY';
                $address->id_country = 6; // España
                $address->country = 'España';
                $address->id_state = 0;
                $address->alias = 'Dirección Demo Day';
                $address->phone = $phone;
                $address->phone_mobile = $phone;
                $address->date_add = date('Y-m-d H:i:s');
                $address->date_upd = date('Y-m-d H:i:s');

                if ($address->add()) {
                    return [
                        'status' => 'success',
                        'message' => $this->l('Address created successfully', 'formcontroller', $iso),
                        'data' => [
                            'id_customer' => $id_customer,
                            'id_address' => $address->id,
                        ],
                    ];
                }

                return [
                    'status' => 'warning',
                    'message' => $this->l('There was an error creating the address', 'formcontroller', $iso),
                    'data' => ['id_customer' => 0, 'id_address' => 0],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->l('Address already exists', 'formcontroller', $iso),
                'data' => [
                    'id_customer' => $id_customer,
                    'id_product' => $id_product,
                    'id_address' => $id_address,
                ],
            ];
        }

        // Crear nuevo cliente
        $customer = new Customer();
        $customer->firstname = $firstname;
        $customer->lastname = $lastname;
        $customer->email = $email;
        $customer->id_lang = Context::getContext()->language->id;
        $customer->id_shop = 1;
        $customer->id_shop_group = 1;
        $customer->id_default_group = 3;
        $customer->passwd = "$2y$10\$NL.ogAgYuVWPl9fw.1w0juoHU1Qh0aazBNN6TPDvyYLoiH8tqhC4O"; // Password predefinida
        $customer->secure_key = md5(uniqid(rand(), true));

        if ($customer->add()) {
            // Crear dirección para el nuevo cliente
            $address = new Address();
            $address->id_customer = $customer->id;
            $address->firstname = $firstname;
            $address->lastname = $lastname;
            $address->dni = '00000000A';
            $address->address1 = 'DIRECCION DEMODAY';
            $address->postcode = '00000';
            $address->city = 'CIUDAD DEMODAY';
            $address->id_country = 6; // España
            $address->country = 'España';
            $address->id_state = 0;
            $address->alias = 'Dirección Demo Day';
            $address->phone = $phone;
            $address->phone_mobile = $phone;
            $address->date_add = date('Y-m-d H:i:s');
            $address->date_upd = date('Y-m-d H:i:s');

            if ($address->add()) {
                return [
                    'status' => 'success',
                    'message' => $this->l('Customer and address created successfully', 'formcontroller', $iso),
                    'data' => [
                        'id_customer' => $customer->id,
                        'id_address' => $address->id,
                        'id_product' => id_product,
                    ],
                ];
            }

            return [
                'status' => 'warning',
                'message' => $this->l('There was an error creating the address', 'formcontroller', $iso),
                'data' => ['id_customer' => $customer->id, 'id_address' => 0],
            ];
        }

        return [
            'status' => 'warning',
            'message' => $this->l('There was an error creating the customer', 'formcontroller', $iso),
            'data' => ['id_customer' => 0, 'id_address' => 0],
        ];
    }

    public function exchangesandreturns()
    {
        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $phone = trim(Tools::getValue('phone'));
        $reason = trim(Tools::getValue('reason'));
        $message = trim(Tools::getValue('message'));
        $address = trim(Tools::getValue('address'));
        $number = trim(Tools::getValue('number'));
        $code = trim(Tools::getValue('code'));
        $location = trim(Tools::getValue('location'));
        $country = trim(Tools::getValue('country'));
        $province = trim(Tools::getValue('province'));
        $preferred = trim(Tools::getValue('preferred'));
        // $sports = Tools::getValue('sports');
        $sports = 'sports';
        $iso = trim(Tools::getValue('iso'));

        // Validar email
        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        // Validar número de teléfono
        if (!Validate::isPhoneNumber($phone)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid phone', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        // Verificar si email y teléfono están presentes
        if (!$email || !$phone) {
            return [
                'status' => 'warning',
                'message' => $this->l('There was an error creating the order', 'formcontroller', $iso),
                'data' => [],
            ];
        }


//        $address = new Address();
//        $address->firstname = $firstname;
//        $address->lastname = $lastname;
//        $address->number = $number;
//        $address->phone = $phone;
//        $address->email = $email;
//        $address->message = $message;
//        $address->reason = $reason;
//        $address->code = $code;
//        $address->location = $location;
//        $address->address = $address;
//        $address->country = $country;
//        $address->preferred = $preferred;
//        $address->sports = $sports;

        $preferred_select = "";

        switch ($preferred) {
            case "morning":
                $preferred_select = "Mañana";
                break;
            case "afternoon":
                $preferred_select = "Tarde";
                break;
            case "indifferent":
                $preferred_select = "Indiferente";
                break;
            default:
                $preferred_select = "opción no válida";
                break;
        }


        $reason_select = "";

        switch ($reason) {
            case "exchange":
                $reason_select = "Necesito cambiarlo por otro producto";
                break;
            case "notreceived":
                $reason_select = "No he recibido el producto solicitado";
                break;
            case "conditions":
                $reason_select = "Producto en malas condiciones";
                break;
            case "interests":
                $reason_select = "El producto ya no me interesa";
                break;
            default:
                $reason_select = "Razón no válida";
                break;
        }

        $dataAlert = [
            '{firstname}' => $firstname,
            '{lastname}' => $lastname,
            '{number}' => $number,
            '{phone}' => $phone,
            '{email}' => $email,
            '{message}' => $message,
            '{reason}' => $reason_select,
            '{code}' => $code,
            '{location}' => $location,
            '{province}' => $province,
            '{country}' => $country,
            '{preferred}' => $preferred_select,
            '{address}' => $address,
            '{sports}' => $sports,
        ];


        $subjectAlert = 'FORMULARIO DE DEVOLUCIÓN';
        $notificationAlvarez = 'clientes@a-alvarez.com';
        //$notificationAlvarez = 'alsernet@alsernet.es';


        // Enviar correo a notificationAlvarez
        if (!Mail::Send(
            (int)$this->context->language->id, // ID del idioma
            'exchangesandreturns_alert',       // Nombre de la plantilla
            $subjectAlert,                     // Asunto del correo
            $dataAlert,                        // Datos del correo (placeholders para la plantilla)
            $notificationAlvarez,              // Dirección del destinatario
            null,                              // Nombre del destinatario (opcional)
            $email,                              // Dirección del remitente (opcional)
            null,                              // Nombre del remitente (opcional)
            null,                              // Archivos adjuntos (opcional)
            null,                              // Modo SMTP (opcional)
            _PS_MAIL_DIR_,                     // Ruta de las plantillas
            false,                             // No duplicar
            $this->context->shop->id,          // ID de la tienda
            null,
            null
        )) {
            return [
                'status' => 'warning',
                'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $subjectNotification = 'Solicitud de cambio o devolución';
        $customerEmail = $email;
        $dataNotification = [

        ];

        // Enviar correo al cliente
        if (!Mail::Send(
            (int)$this->context->language->id,
            'exchangesandreturns',
            $subjectNotification,
            $dataNotification,
            $customerEmail
        )) {
            return [
                'status' => 'warning',
                'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->l('You have successfully created a new account.', 'formcontroller', $iso),
            'data' => [],
        ];


    }

    public function giftvoucher()
    {

        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $sports = Tools::getValue('sports');
        $condition = Tools::getValue('condition');
        $services = Tools::getValue('services');
        $iso = trim(Tools::getValue('iso'));
        $id_lang = Language::getIdByIso($iso);

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $data = [
            'action' => 'campaigns',
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'id_lang' => $id_lang,
            'iso' => $iso,
            'categories' => $sports,
            'condition' => !empty(Tools::getValue('condition')) ? false : true,
            'services' => !empty(Tools::getValue('services')) ? false : true,
        ];

        // Inicializa cURL
        $ch = curl_init();

        // Configura las opciones de cURL
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:60010/api/v1/newsletters/campaigns');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Enviar los datos como JSON
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json', // Establece el tipo de contenido
        ]);

        // Ejecuta la solicitud cURL
        $response = curl_exec($ch);

        // Verifica si hubo un error
        if (curl_errno($ch)) {
            return [
                'status' => 'error',
                'message' => $this->l('Error en la conexión al servidor', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        // Cierra la conexión cURL
        curl_close($ch);

        $response_data = json_decode($response, true);


        if (isset($response_data['status']) && $response_data['status'] == 'success') {

            $subjectcampaigns = 'Aquí tienes tu cheque regalo 10€!!!';

            if (!Mail::Send(
                (int)$this->context->language->id,
                'campaigns_giftvoucher',
                $subjectcampaigns,
                [],
                $email
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                    'data' => $response_data,
                ];
            } else {

                return [
                    'status' => 'success',
                    'message' => $this->l('You have successfully created neswletters.', 'formcontroller', $iso),
                    'data' => $response_data,
                ];
            }

        } else {

            $message = $this->l('Unknown error.', 'formcontroller', $iso);

            switch ($response_data['type']) {
                case "send":
                    $message = $this->l('The email has already been verified in the current campaign', 'formcontroller', $iso);
                    break;
                case "exist":
                    $message = $this->l('The email does not exist in our records.', 'formcontroller', $iso);
                    break;
            }
            return [
                'status' => 'warning',
                'message' => $message,
                'data' => [],
            ];
        }


    }


    public function processyoursalenow()
    {
        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $phone = trim(Tools::getValue('phone'));
        $address = trim(Tools::getValue('address'));
        $citie = trim(Tools::getValue('citie'));
        $province = trim(Tools::getValue('province'));
        $postal = trim(Tools::getValue('postal'));
        $type = trim(Tools::getValue('type'));
        $subtype = trim(Tools::getValue('subtype'));
        $brand = trim(Tools::getValue('brand'));
        $model = trim(Tools::getValue('model'));
        $currentcondition = trim(Tools::getValue('currentcondition'));
        $comments = trim(Tools::getValue('comments'));
        $serialnumber = trim(Tools::getValue('serialnumber'));
        $caliber = trim(Tools::getValue('caliber'));
        $yearofmanufacture = trim(Tools::getValue('yearofmanufacture'));
        $subtyperifle = trim(Tools::getValue('$subtyperifle'));
        $subtypeoptics = trim(Tools::getValue('subtypeoptics'));
        $subtypeshotgun = trim(Tools::getValue('subtypeshotgun'));
        $iso = trim(Tools::getValue('iso'));


        switch ($type) {
            case "shotgun":
                $type = "Escopeta";

                switch ($subtypeshotgun) {
                    case "parallel":
                        $subtype = "Paralela";
                        break;
                    case "overandabove":
                        $subtype = "Superpuesta";
                        break;
                    case "semiautomatic":
                        $subtype = "Sautomaticas";
                        break;
                    case "sliding":
                        $subtype = "Corredera";
                        break;
                    case "singleshot":
                        $subtype = "Monotiro";
                        break;
                }

                break;
            case "rifle":

                $type = "Rifle";

                switch ($subtyperifle) {
                    case "boltaction":
                        $subtype = "Cerrojo";
                        break;
                    case "semiautomatic":
                        $subtype = "Semiautomatica";
                        break;
                    case "lever":
                        $subtype = "Palanca";
                        break;
                    case "singleshot":
                        $subtype = "Monitiro";
                        break;
                    case "express":
                        $subtype = "Express";
                        break;
                }

                break;
            case "handgun":
                $type = "Arma corta";
                $subtype = "No tiene";
                break;
            case "canon":
                $type = "Canon";
                $subtype = "No tiene";
                break;
            case "optics":
                $type = "Opticas";

                switch ($subtypeoptics) {
                    case "scope":
                        $subtype = "Visor";
                        break;
                    case "binoculars":
                        $subtype = "Prismaticos";
                        break;
                    case "nightvision":
                        $subtype = "Vision nocturna";
                        break;
                    case "telescope":
                        $subtype = "Telescopico";
                        break;
                    case "distancemeter":
                        $subtype = "Medidor de distancia";
                        break;
                }

                break;
        }


        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        if (!Validate::isPhoneNumber($phone)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid phone', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        if (!$email || !$phone) {
            return [
                'status' => 'warning',
                'message' => $this->l('There was an error creating the order', 'formcontroller', $iso),
                'data' => [],
            ];
        }


        $uploadedFiles = [];
        $uploadDir = _PS_ROOT_DIR_ . '/themes/alvarez/assets/upload/internalinformations/';

        // if (isset($_FILES['file']) && !empty($_FILES['file']['name'])) {

        //$files = is_array($_FILES['file']['name']) ? $_FILES['file'] : [
        //     'name' => [$_FILES['file']['name']],
        //     'type' => [$_FILES['file']['type']],
        //    'tmp_name' => [$_FILES['file']['tmp_name']],
        //    'error' => [$_FILES['file']['error']],
        //     'size' => [$_FILES['file']['size']],
        // ];


        // foreach ($files['name'] as $key => $name) {
        //    if ($files['error'][$key] !== UPLOAD_ERR_OK) {
        //        dump('Error al subir archivo: ' . $files['error'][$key]);
        //        continue; // Pasar al siguiente archivo
        //     }

        //    $tmpName = $files['tmp_name'][$key];
        //   $extension = pathinfo($name, PATHINFO_EXTENSION);
        //    $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'gif', 'jpg', 'png'];

        //    if (!in_array(strtolower($extension), $allowedExtensions, true)) {
        //       dump('Extensión no permitida: ' . $extension);
        //        continue; // Pasar al siguiente archivo
        //    }

        //   $uniqueName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\-_\.]/', '', str_replace(' ', '_', basename($name)));
        //   $destination = $uploadDir . $uniqueName;

        //  if (!move_uploaded_file($tmpName, $destination)) {
        //      dump('Error al mover el archivo: ' . $name);
        //      continue; // Pasar al siguiente archivo
        //  }

        // $uploadedFiles[] = _PS_BASE_URL_ . '/themes/alvarez/assets/upload/internalinformations/' . $uniqueName;
        //  }
        //  }

        $html = '';
        if (!empty($uploadedFiles)) {
            $html .= '<ul>';
            $html .= 'Archivos:';
            foreach ($uploadedFiles as $fileUrl) {
                $fileName = basename($fileUrl); // Obtener el nombre del archivo
                $html .= '<li><a href="' . htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . '</a></li>';
            }
            $html .= '</ul>';
        } else {
            $html = '<li> Archivos: No cargo archivos </li>';
        }

        $subjectAlert = $this->l('Request second-hand and used weapons sales form', 'formcontroller', 'es');
        $notificationAlvarez = 'web@a-alvarez.com';

        $dataAlert = [
            '{firstname}' => $firstname,
            '{lastname}' => $lastname,
            '{phone}' => $phone,
            '{email}' => $email,
            '{address}' => $address,
            '{postal}' => $postal,
            '{citie}' => $citie,
            '{province}' => $province,
            '{type}' => $type,
            '{subtype}' => $subtype,
            '{brand}' => $brand,
            '{model}' => $model,
            '{currentcondition}' => $currentcondition,
            '{comments}' => $comments,
            '{serialnumber}' => $serialnumber,
            '{caliber}' => $caliber,
            '{yearofmanufacture}' => $yearofmanufacture,
            '{urls}' => $html,
        ];

        if (!Mail::Send(
            1,
            'processyoursalenow_alert',
            $subjectAlert,
            $dataAlert,
            $notificationAlvarez,
            null,
            $email,
            null,
            null,
            null,
            _PS_MAIL_DIR_,
            false,
            $this->context->shop->id,
            null,
            null
        )) {
            return [
                'status' => 'warning',
                'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $subjectNotification = $this->l('Second-hand and used weapons sales form', 'formcontroller', $iso);
        $customerEmail = $email;
        $token = md5($customerEmail);
        $verificationLink = $this->context->link->getModuleLink('ps_emailsubscription', 'verification', ['token' => $token], true);

        $dataNotification = [
            '{verification_link}' => $verificationLink,
        ];

        // Enviar correo al cliente
        if (!Mail::Send(
            (int)$this->context->language->id,
            'processyoursalenow_notification',
            $subjectNotification,
            $dataNotification,
            $customerEmail
        )) {
            return [
                'status' => 'warning',
                'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->l('You have successfully created a new account.', 'formcontroller', $iso),
            'data' => [],
        ];

    }


    public function workwithus()
    {
        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $phone = trim(Tools::getValue('phone'));
        $reason = trim(Tools::getValue('reason'));
        $message = trim(Tools::getValue('message'));
        $address = trim(Tools::getValue('address'));
        $number = trim(Tools::getValue('number'));
        $code = trim(Tools::getValue('code'));
        $location = trim(Tools::getValue('location'));
        $country = trim(Tools::getValue('country'));
        $province = trim(Tools::getValue('province'));
        $preferred = trim(Tools::getValue('preferred'));
        // $sports = Tools::getValue('sports');
        $sports = 'sports';
        $iso = trim(Tools::getValue('iso'));

        // Validar email
        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        // Validar número de teléfono
        if (!Validate::isPhoneNumber($phone)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid phone', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        // Verificar si email y teléfono están presentes
        if (!$email || !$phone) {
            return [
                'status' => 'warning',
                'message' => $this->l('There was an error creating the order', 'formcontroller', $iso),
                'data' => [],
            ];
        }


//        $address = new Address();
//        $address->firstname = $firstname;
//        $address->lastname = $lastname;
//        $address->number = $number;
//        $address->phone = $phone;
//        $address->email = $email;
//        $address->message = $message;
//        $address->reason = $reason;
//        $address->code = $code;
//        $address->location = $location;
//        $address->address = $address;
//        $address->country = $country;
//        $address->preferred = $preferred;
//        $address->sports = $sports;

        $preferred_select = "";

        switch ($preferred) {
            case "morning":
                $preferred_select = "Mañana";
                break;
            case "afternoon":
                $preferred_select = "Tarde";
                break;
            case "indifferent":
                $preferred_select = "Indiferente";
                break;
            default:
                $preferred_select = "opción no válida";
                break;
        }


        $reason_select = "";

        switch ($reason) {
            case "exchange":
                $reason_select = "Necesito cambiarlo por otro producto";
                break;
            case "notreceived":
                $reason_select = "No he recibido el producto solicitado";
                break;
            case "conditions":
                $reason_select = "Producto en malas condiciones";
                break;
            case "interests":
                $reason_select = "El producto ya no me interesa";
                break;
            default:
                $reason_select = "Razón no válida";
                break;
        }

        $dataAlert = [
            '{firstname}' => $firstname,
            '{lastname}' => $lastname,
            '{number}' => $number,
            '{phone}' => $phone,
            '{email}' => $email,
            '{message}' => $message,
            '{reason}' => $reason_select,
            '{code}' => $code,
            '{location}' => $location,
            '{province}' => $province,
            '{country}' => $country,
            '{preferred}' => $preferred_select,
            '{address}' => $address,
            '{sports}' => $sports,
        ];


        $subjectAlert = 'FORMULARIO DE DEVOLUCIÓN';
        $notificationAlvarez = 'clientes@a-alvarez.com';
        //$notificationAlvarez = 'alsernet@alsernet.es';


        // Enviar correo a notificationAlvarez
        if (!Mail::Send(
            1,
            'exchangesandreturns_alert',
            $subjectAlert,
            $dataAlert,
            $notificationAlvarez,
            null,
            $email,
            null,
            null,
            null,
            _PS_MAIL_DIR_,
            false,
            $this->context->shop->id,
            null,
            null
        )) {
            return [
                'status' => 'warning',
                'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $subjectNotification = 'Solicitud de cambio o devolución';
        $customerEmail = $email;
        $dataNotification = [

        ];

        // Enviar correo al cliente
        if (!Mail::Send(
            (int)$this->context->language->id,
            'exchangesandreturns',
            $subjectNotification,
            $dataNotification,
            $customerEmail
        )) {
            return [
                'status' => 'warning',
                'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->l('You have successfully created a new account.', 'formcontroller', $iso),
            'data' => [],
        ];


    }


    public function doubtsshipping()
    {
        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $phone = trim(Tools::getValue('phone'));
        $question = trim(Tools::getValue('question'));
        $iso = trim(Tools::getValue('iso'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        if (isset($email)) {

            $subjectAlert = $this->l('Shipping Inquiry request', 'formcontroller', 'es');
            $notificationAlvarez = 'web@a-alvarez.com';

            $dataAlert = [
                '{firstname}' => $firstname,
                '{lastname}' => $lastname,
                '{email}' => $email,
                '{question}' => $question,
                '{phone}' => $phone,
            ];

            if (!Mail::Send(
                1,
                'doubtsshipping_alert',
                $subjectAlert,
                $dataAlert,
                $notificationAlvarez
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            $customerEmail = $email;
            $subjectNotification = $this->l('Shipping Inquiry', 'formcontroller', $iso);

            $token = md5($customerEmail);
            $verificationLink = $this->context->link->getModuleLink('ps_emailsubscription', 'verification', ['token' => $token], true);

            $dataNotification = [
                '{verification_link}' => $verificationLink,
            ];

            if (!Mail::Send(
                (int)$this->context->language->id,
                'doubtsshipping_notification',
                $subjectNotification,
                $dataNotification,
                $customerEmail
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created a new account.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

    }

    public function paymentmethods()
    {
        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $phone = trim(Tools::getValue('phone'));
        $question = trim(Tools::getValue('question'));
        $iso = trim(Tools::getValue('iso'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        if (isset($email)) {

            $subjectAlert = $this->l('Inquiry about payments and financing request', 'formcontroller', 'es');
            $notificationAlvarez = 'web@a-alvarez.com';

            $dataAlert = [
                '{firstname}' => $firstname,
                '{lastname}' => $lastname,
                '{email}' => $email,
                '{question}' => $question,
                '{phone}' => $phone,
            ];

            if (!Mail::Send(
                1,
                'paymentmethods_alert',
                $subjectAlert,
                $dataAlert,
                $notificationAlvarez
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            $customerEmail = $email;
            $subjectNotification = $this->l('Inquiry about payments and financing', 'formcontroller', $iso);

            $token = md5($customerEmail);
            $verificationLink = $this->context->link->getModuleLink('ps_emailsubscription', 'verification', ['token' => $token], true);

            $dataNotification = [
                '{verification_link}' => $verificationLink,
            ];

            if (!Mail::Send(
                (int)$this->context->language->id,
                'paymentmethods_notification',
                $subjectNotification,
                $dataNotification,
                $customerEmail
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created a new account.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

    }


    public function internalinformationsystem()
    {
        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $phone = trim(Tools::getValue('phone'));
        $message = trim(Tools::getValue('message'));
        $address = trim(Tools::getValue('address'));
        $iso = trim(Tools::getValue('iso'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        if (isset($email)) {

            $uploadedFiles = [];
            $uploadDir = _PS_ROOT_DIR_ . '/themes/alvarez/assets/upload/internalinformations/';

            // if (isset($_FILES['file']) && !empty($_FILES['file']['name'])) {

            //$files = is_array($_FILES['file']['name']) ? $_FILES['file'] : [
            //     'name' => [$_FILES['file']['name']],
            //     'type' => [$_FILES['file']['type']],
            //    'tmp_name' => [$_FILES['file']['tmp_name']],
            //    'error' => [$_FILES['file']['error']],
            //     'size' => [$_FILES['file']['size']],
            // ];


            // foreach ($files['name'] as $key => $name) {
            //    if ($files['error'][$key] !== UPLOAD_ERR_OK) {
            //        dump('Error al subir archivo: ' . $files['error'][$key]);
            //        continue; // Pasar al siguiente archivo
            //     }

            //    $tmpName = $files['tmp_name'][$key];
            //   $extension = pathinfo($name, PATHINFO_EXTENSION);
            //    $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'gif', 'jpg', 'png'];

            //    if (!in_array(strtolower($extension), $allowedExtensions, true)) {
            //       dump('Extensión no permitida: ' . $extension);
            //        continue; // Pasar al siguiente archivo
            //    }

            //   $uniqueName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\-_\.]/', '', str_replace(' ', '_', basename($name)));
            //   $destination = $uploadDir . $uniqueName;

            //  if (!move_uploaded_file($tmpName, $destination)) {
            //      dump('Error al mover el archivo: ' . $name);
            //      continue; // Pasar al siguiente archivo
            //  }

            // $uploadedFiles[] = _PS_BASE_URL_ . '/themes/alvarez/assets/upload/internalinformations/' . $uniqueName;
            //  }
            //  }

            $html = '';
            if (!empty($uploadedFiles)) {
                $html .= '<ul>';
                $html .= 'Archivos:';
                foreach ($uploadedFiles as $fileUrl) {
                    $fileName = basename($fileUrl); // Obtener el nombre del archivo
                    $html .= '<li><a href="' . htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . '</a></li>';
                }
                $html .= '</ul>';
            } else {
                $html = '<li> Archivos: No cargo archivos </li>';
            }

            $subjectAlert = $this->l('Request received from the Internal Information System Form', 'formcontroller', 'es');
            $notificationAlvarez = 'sistemainternodeinformacion@a-alvarez.com';

            $dataAlert = [
                '{firstname}' => $firstname,
                '{lastname}' => $lastname,
                '{email}' => $email,
                '{address}' => $address,
                '{message}' => $message,
                '{phone}' => $phone,
                '{urls}' => $html,
            ];

            if (!Mail::Send(
                1,
                'internalinformationsystem_alert',
                $subjectAlert,
                $dataAlert,
                $notificationAlvarez
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            $customerEmail = $email;
            $subjectNotification = $this->l('Internal Information System Form', 'formcontroller', $iso);

            $token = md5($customerEmail);
            $verificationLink = $this->context->link->getModuleLink('ps_emailsubscription', 'verification', ['token' => $token], true);

            $dataNotification = [
                '{verification_link}' => $verificationLink,
            ];

            if (!Mail::Send(
                (int)$this->context->language->id,
                'internalinformationsystem_notification',
                $subjectNotification,
                $dataNotification,
                $customerEmail
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created a new account.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

    }


    public function wecallyouus()
    {
        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $phone = trim(Tools::getValue('phone'));
        $id_product = trim(Tools::getValue('product'));
        $iso = trim(Tools::getValue('iso'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];

        } elseif (!Validate::isPhoneNumber($phone)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid phone', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);
        $product_name = $product->name;

        $product_url = $this->context->link->getProductLink(
            $id_product,
            $product->link_rewrite,
            null,
            null,
            $this->context->language->id,
            $this->context->shop->id
        );

        $dataAlert = [
            '{phone}' => $phone,
            '{firstname}' => $firstname,
            '{lastname}' => $lastname,
            '{product}' => $product_name,
            '{link}' => $product_url,
            '{email}' => $email,
        ];

        if (isset($email) && isset($phone)) {

            $subjectAlert = $this->l('Request for call to check price', 'formcontroller', 'es');
            $notificationAlvarez = 'formulariosprestashop@a-alvarez.com';

            $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);
            $product_name = $product->name;

            $product_url = $this->context->link->getProductLink(
                $id_product,
                $product->link_rewrite,
                null,
                null,
                $this->context->language->id,
                $this->context->shop->id
            );

            $dataAlert = [
                '{phone}' => $phone,
                '{firstname}' => $firstname,
                '{lastname}' => $lastname,
                '{product}' => $product_name,
                '{link}' => $product_url,
                '{email}' => $email,
            ];


            if (!Mail::Send(
                1,
                'wecallyouus_alert',
                $subjectAlert,
                $dataAlert,
                $notificationAlvarez
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            $customerEmail = $email;
            $subjectNotification = $this->l('Request for call to check price', 'formcontroller', $iso);

            $token = md5($customerEmail);
            $verificationLink = $this->context->link->getModuleLink('ps_emailsubscription', 'verification', ['token' => $token], true);

            $dataNotification = [
                '{verification_link}' => $verificationLink,
            ];

            if (!Mail::Send(
                (int)$this->context->language->id,
                'wecallyouus_notification',
                $subjectNotification,
                $dataNotification,
                $customerEmail
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            /**TODO INSERTAR EN TABLA CONTACT PARA QUE FUNCIONE EL ENVÍO A TRAVEZ DE LA INTRANET LUEGO ELIMINAR ESTE BLOQUE*/
            $mailContent = "<p>Producto: $product_url</p><p>Usuario: $firstname $lastname ($customerEmail)</p><p>Teléfono: $phone</p></p><p>Esta consulta entrará en el Panel de consultasweb.a-alvarez.com.</p>";
            $sql = "INSERT INTO " . _DB_PREFIX_ . "ets_ctf_contact_message
                    (id_contact, id_customer, replied, readed, special, subject, sender, body, recipient, attachments, reply_to, date_add, date_upd)
                    VALUES(2, 0, 0, 0, 0, 'Solicitud de llamada para consultar precio', 'Alvarez Web <web@a-alvarez.com>',
                    '$mailContent', 'Alvarez Web <$notificationAlvarez>', '', '$customerEmail', NOW(), NOW());";
            Db::getInstance()->execute($sql);

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created a new account.', 'formcontroller', $iso),
                'data' => [],
            ];

        }


    }


    public function contact()
    {
        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $phone = trim(Tools::getValue('phone'));
        $message = Tools::getValue('message');
        $iso = trim(Tools::getValue('iso'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        if (isset($email)) {

            $subjectAlert = $this->l('Contact form request', 'formcontroller', 'es');
            $notificationAlvarez = 'formulariosprestashop@a-alvarez.com';

            $dataAlert = [
                '{firstname}' => $firstname,
                '{lastname}' => $lastname,
                '{email}' => $email,
                '{message}' => $message,
                '{phone}' => $phone,
            ];

            if (!Mail::Send(
                1,
                'contact_alert',
                $subjectAlert,
                $dataAlert,
                $notificationAlvarez
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            $customerEmail = $email;
            $subjectNotification = $this->l('Contact form request form', 'formcontroller', $iso);

            $token = md5($customerEmail);
            $verificationLink = $this->context->link->getModuleLink('ps_emailsubscription', 'verification', ['token' => $token], true);

            $dataNotification = [
                '{verification_link}' => $verificationLink,
            ];

            if (!Mail::Send(
                (int)$this->context->language->id,
                'contact_notification',
                $subjectNotification,
                $dataNotification,
                $customerEmail
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created a new account.', 'formcontroller', $iso),
                'data' => [],
            ];
        }

    }


    public function interestfreefinancing()
    {
        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $phone = trim(Tools::getValue('phone'));
        $id_product = trim(Tools::getValue('product'));
        $iso = trim(Tools::getValue('iso'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];

        } elseif (!Validate::isPhoneNumber($phone)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid phone', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);

        $product_name = $product->name;
        $product_id = $id_product;


        if (isset($email) && isset($phone)) {

            $subjectAlert = $this->l('Request interest-free financing', 'formcontroller', 'es');
            $notificationAlvarez = 'begona@a-alvarez.com';

            $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);
            $product_name = $product->name;

            $product_url = $this->context->link->getProductLink(
                $id_product,
                $product->link_rewrite,
                null,
                null,
                1,
                $this->context->shop->id
            );

            $dataAlert = [
                '{phone}' => $phone,
                '{firstname}' => $firstname,
                '{lastname}' => $lastname,
                '{product}' => $product_name,
                '{id}' => $product_id,
                '{email}' => $email,
            ];


            if (!Mail::Send(
                1,
                'interestfreefinancing_alert',
                $subjectAlert,
                $dataAlert,
                $notificationAlvarez
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            $customerEmail = $email;
            $subjectNotification = $this->l('interest-free financing', 'formcontroller', $iso);

            $token = md5($customerEmail);
            $verificationLink = $this->context->link->getModuleLink('ps_emailsubscription', 'verification', ['token' => $token], true);

            $dataNotification = [
                '{verification_link}' => $verificationLink,
            ];

            if (!Mail::Send(
                (int)$this->context->language->id,
                'interestfreefinancing_notification',
                $subjectNotification,
                $dataNotification,
                $customerEmail
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $iso),
                    'data' => [],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created a new account.', 'formcontroller', $iso),
                'data' => [],
            ];
        }


    }


    public function l($string, $specific = false, $locale = null)
    {

        return $this->getModuleTranslation(
            $this->module,
            $string,
            ($specific) ? $specific : $this->name,
            null,
            false,
            $locale
        );
    }


    public function getModuleTranslation(
        $module,
        $originalString,
        $source,
        $sprintf = null,
        $js = false,
        $locale = null,
        $fallback = true,
        $escape = true
    )
    {
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

        $cacheKey = $name . '|' . $string . '|' . $source . '|' . (int)$js . '|' . $iso;
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

