<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:254'],
            'subject' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:200'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $recipient = config('mail.from.address', 'info@example.com');

        try {
            $body = implode("\n", [
                'Nombre: '.$data['name'],
                'Email: '.$data['email'],
                'Teléfono: '.($data['phone'] ?? '-'),
                'Dirección: '.($data['address'] ?? '-'),
                '',
                'Asunto: '.$data['subject'],
                '',
                $data['content'],
            ]);

            Mail::raw($body, function ($message) use ($data, $recipient) {
                $message->to($recipient)
                    ->subject($data['subject'])
                    ->replyTo($data['email'], $data['name']);
            });
        } catch (\Throwable) {
            // Do not block the user if the mail fails
        }

        return response()->json([
            'success' => true,
            'message' => __('Mensaje enviado correctamente'),
        ]);
    }
}
