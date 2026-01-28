<?php

namespace Modules\Mailrelay\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mailrelay\Entities\Subscriber;
use Modules\Mailrelay\Services\MailRelayService;

class MailRelayController extends Controller
{
    protected $mailRelayService;

    public function __construct(MailRelayService $mailRelayService)
    {
        $this->mailRelayService = $mailRelayService;
    }

    /**
     * Muestra el formulario para agregar un suscriptor.
     *
     * @return \Illuminate\View\View
     */
    public function showAddSubscriberForm()
    {
        return view('mailrelay::mailrelay.add-subscriber');
    }

    /**
     * Muestra el formulario para eliminar un suscriptor.
     *
     * @return \Illuminate\View\View
     */
    public function showRemoveSubscriberForm()
    {
        return view('mailrelay::mailrelay.remove-subscriber');
    }

    /**
     * Muestra el formulario para crear una nueva campaña.
     *
     * @return \Illuminate\View\View
     */
    public function showCreateCampaignForm()
    {
        return view('mailrelay::mailrelay.create-campaign');
    }

    /**
     * Agrega un nuevo suscriptor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function showCreateListForm()
    {
        return view('mailrelay::mailrelay.create-list');
    }

    public function createList(Request $request)
    {
        // Validar los datos del formulario
        $request->validate([
            'list_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        // Crear la lista usando el servicio
        $response = $this->mailRelayService->createList($request->list_name, $request->description);

        if (isset($response['error'])) {
            return redirect()->route('mailrelay.create-list')->with('error', 'Error al crear la lista: '.$response['error']);
        }

        return redirect()->route('mailrelay.create-list')->with('success', 'Lista creada exitosamente.');
    }

    public function addSubscriber(Request $request)
    {
        // Validación de los datos del formulario
        $request->validate([
            'email' => 'required|email|unique:subscribers,email', // Validar correo electrónico único
            'name' => 'required|string|max:255', // Validar nombre
            'list_id' => 'required|integer', // Aseguramos que el list_id sea un valor válido
        ]);

        try {
            // Crear el suscriptor en la base de datos
            $subscriber = Subscriber::create([
                'name' => $request->name,
                'email' => $request->email,
                'list_id' => $request->list_id, // Usar el list_id recibido
                'is_subscribed' => 1, // Marca al suscriptor como suscrito
            ]);

            // Llamar al servicio para agregar el suscriptor a MailRelay
            $mailRelayResponse = $this->mailRelayService->addSubscriberToMailRelay(
                $request->name,
                $request->email,
                $request->list_id // También pasamos el list_id al servicio
            );

            // Verificar si MailRelay respondió correctamente
            if (isset($mailRelayResponse['error'])) {
                return redirect()->route('mailrelay.add-subscriber')
                    ->with('error', 'Error al agregar el suscriptor a MailRelay: '.$mailRelayResponse['error']);
            }

            // Redirigir con un mensaje de éxito
            return redirect()->route('mailrelay.add-subscriber')
                ->with('success', 'Suscriptor agregado exitosamente.');

        } catch (\Exception $e) {
            // Manejo de excepciones, por si ocurre algún error en el proceso
            return redirect()->route('mailrelay.add-subscriber')
                ->with('error', 'Ocurrió un error al agregar el suscriptor: '.$e->getMessage());
        }
    }

    /**
     * Elimina un suscriptor.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeSubscriber(Request $request)
    {
        // Validar que el correo exista
        $subscriber = Subscriber::where('email', $request->email)->first();

        if ($subscriber) {
            // Eliminar el suscriptor
            $subscriber->delete();

            return redirect()->route('mailrelay.remove-subscriber')->with('success', 'Suscriptor eliminado correctamente.');
        }

        return redirect()->route('mailrelay.remove-subscriber')->with('error', 'No se encontró un suscriptor con ese correo.');
    }

    /**
     * Crea una campaña.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createCampaign(Request $request)
    {

        // Preparar los datos para la campaña
        $campaignData = [
            'sender_id' => 0,  // Usar el remitente predeterminado
            'subject' => $request['subject'],
            'preview_text' => $request['preview_text'],
            'html' => $request['html'],
            'target' => 'groups',  // Esto es fijo en el ejemplo
            'segment_id' => 0,  // Si no estás usando un segmento
            'group_ids' => $request['group_ids'],  // Debe ser un arreglo
            'campaign_folder_id' => 0,  // Asumir que no hay una carpeta
            'url_token' => false,
            'analytics_utm_campaign' => $request['analytics_utm_campaign'] ?? null,
            'use_premailer' => false,
            'reply_to' => $request['reply_to'] ?? null,
        ];

        // Llamar al servicio para crear la campaña
        $response = $this->mailRelayService->createCampaign($campaignData);

        // Verificar si hubo un error
        if (isset($response['error'])) {
            return redirect()->route('mailrelay.create-campaign')->with('error', 'Error al crear la campaña: '.$response['error']);
        }

        return redirect()->route('mailrelay.create-campaign')->with('success', 'Campaña creada exitosamente.');
    }

    /**
     * Envía una campaña.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendCampaign(Request $request)
    {
        // Validar el ID de la campaña
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
        ]);

        // Lógica para enviar la campaña (en este caso, se envía usando el servicio)
        $this->mailRelayService->sendCampaign($request->campaign_id);

        // Redirigir con un mensaje de éxito
        return redirect()->route('mailrelay.send-campaign')->with('success', 'Campaña enviada exitosamente.');
    }

    // Enviar Email
    public function sendEmail(Request $request)
    {
        $response = $this->mailRelayService->sendEmail($request->subject, $request->html_content, $request->text_content, $request->list_id);

        return response()->json($response);
    }

    // Crear A/B Test
    public function createABTest(Request $request)
    {
        $response = $this->mailRelayService->createABTest($request->name, $request->subject_a, $request->subject_b, $request->list_id);

        return response()->json($response);
    }

    public function addSubscriberToMailRelay(Request $request)
    {
        $response = $this->mailRelayService->createABTest($request->name, $request->subject_a, $request->subject_b, $request->list_id);

        return response()->json($response);
    }

    // Obtener detalles de una campaña
    public function getCampaign($campaignId)
    {
        $response = $this->mailRelayService->getCampaign($campaignId);

        return response()->json($response);
    }

    // Crear una carpeta para campaña
    public function createCampaignFolder(Request $request)
    {
        $response = $this->mailRelayService->createCampaignFolder($request->name);

        return response()->json($response);
    }

    // Crear campo personalizado
    public function createCustomField(Request $request)
    {
        $response = $this->mailRelayService->createCustomField($request->name, $request->type);

        return response()->json($response);
    }

    // Importar suscriptores
    public function importSubscribers(Request $request)
    {
        $response = $this->mailRelayService->importSubscribers($request->list_id, $request->file);

        return response()->json($response);
    }

    // Crear grupo
    public function createGroup(Request $request)
    {
        $response = $this->mailRelayService->createGroup($request->name, $request->list_id);

        return response()->json($response);
    }

    // Subir archivo multimedia
    public function uploadMediaFile(Request $request)
    {
        $response = $this->mailRelayService->uploadMediaFile($request->file);

        return response()->json($response);
    }

    // Crear carpeta de medios
    public function createMediaFolder(Request $request)
    {
        $response = $this->mailRelayService->createMediaFolder($request->name);

        return response()->json($response);
    }

    // Crear campaña RSS
    public function createRSSCampaign(Request $request)
    {
        $response = $this->mailRelayService->createRSSCampaign($request->name, $request->rss_feed_url);

        return response()->json($response);
    }

    // Crear remitente
    public function createSender(Request $request)
    {
        $response = $this->mailRelayService->createSender($request->name, $request->email);

        return response()->json($response);
    }

    // Obtener campañas enviadas
    public function getSentCampaigns()
    {
        $response = $this->mailRelayService->getSentCampaigns();

        return response()->json($response);
    }

    // Enviar correo SMTP
    public function sendSMTPEmail(Request $request)
    {
        $response = $this->mailRelayService->sendSMTPEmail($request->subject, $request->html_content, $request->to_email);

        return response()->json($response);
    }

    // Crear formulario de inscripción
    public function createSignupForm(Request $request)
    {
        $response = $this->mailRelayService->createSignupForm($request->name, $request->list_id);

        return response()->json($response);
    }

    // Crear etiqueta SMTP
    public function createSMTPTag(Request $request)
    {
        $response = $this->mailRelayService->createSMTPTag($request->tag_name);

        return response()->json($response);
    }

    // Obtener lista de suscriptores
    public function getSubscribersList()
    {
        $response = $this->mailRelayService->getSubscribersList();

        return response()->json($response);
    }

    // Obtener eventos de cancelación
    public function getUnsubscribeEvents()
    {
        $response = $this->mailRelayService->getUnsubscribeEvents();

        return response()->json($response);
    }

    // Obtener rebotes
    public function getBounces()
    {
        $response = $this->mailRelayService->getBounces();

        return response()->json($response);
    }

    // Ejecutar lote de API
    public function executeBatch(Request $request)
    {
        $response = $this->mailRelayService->executeBatch($request->batch_data);

        return response()->json($response);
    }

    // Enviar SMS transaccionales
    public function sendTransactionalSMS(Request $request)
    {
        $response = $this->mailRelayService->sendTransactionalSMS($request->phone_number, $request->message);

        return response()->json($response);
    }

    // Crear campaña SMS
    public function createSMSCampaign(Request $request)
    {
        $response = $this->mailRelayService->createSMSCampaign($request->name, $request->message, $request->list_id);

        return response()->json($response);
    }

    // Obtener mensajes SMS enviados
    public function getSMSMessagesSent()
    {
        $response = $this->mailRelayService->getSMSMessagesSent();

        return response()->json($response);
    }

    // Crear automatización
    public function createAutomation(Request $request)
    {
        $response = $this->mailRelayService->createAutomation($request->name, $request->trigger, $request->action);

        return response()->json($response);
    }

    // Obtener automatizaciones
    public function getAutomations()
    {
        $response = $this->mailRelayService->getAutomations();

        return response()->json($response);
    }

    // Obtener registros de actividad
    public function getActivityLogs()
    {
        $response = $this->mailRelayService->getActivityLogs();

        return response()->json($response);
    }
}
