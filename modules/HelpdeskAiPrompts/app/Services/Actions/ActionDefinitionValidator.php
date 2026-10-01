<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

use Illuminate\Validation\ValidationException;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Support\ToolCatalog;

/**
 * Valida (y normaliza) la definición de una acción antes de guardarla. La
 * llama el modelo en `saving`, así que ningún camino (panel, seeder, tinker)
 * puede persistir una acción fuera de la lista blanca o sin las reglas de
 * seguridad de las acciones de escritura.
 */
class ActionDefinitionValidator
{
    private const PARAM_TYPES = ['string', 'integer', 'number', 'boolean', 'email', 'enum'];

    private const OWNERSHIPS = ['none', 'verified', 'order_email_pair'];

    private const RESERVED_KEYS = ['answer_customer', 'escalate_to_agent'];

    private const FORBIDDEN_HEADERS = ['host', 'cookie', 'authorization', 'proxy-authorization', 'content-length'];

    public function __construct(
        private readonly TemplateResolver $templates,
        private readonly HttpActionClient $http,
    ) {}

    /**
     * @throws ValidationException
     */
    public function assertValid(AiAction $action): void
    {
        $errors = [];

        $this->checkKeyAndType($action, $errors);

        if ($errors === [] && $action->type !== AiAction::TYPE_BUILTIN) {
            $this->normalizeRules($action);
            $this->checkParameters($action, $errors);
            $this->checkRules($action, $errors);
            $this->checkResponse($action, $errors);

            if ($action->type === AiAction::TYPE_BRIDGE) {
                $this->checkBridge($action, $errors);
            } else {
                $this->checkHttp($action, $errors);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function checkKeyAndType(AiAction $action, array &$errors): void
    {
        $key = (string) $action->key;

        if (preg_match('/^[a-z0-9_]{1,48}$/', $key) !== 1) {
            $errors['key'][] = 'La clave solo admite a-z, 0-9 y _ (máx. 48).';
        }

        if (! in_array($action->type, [AiAction::TYPE_BUILTIN, AiAction::TYPE_BRIDGE, AiAction::TYPE_HTTP], true)) {
            $errors['type'][] = 'Tipo de acción no válido.';

            return;
        }

        $codeTools = ToolCatalog::keys();

        if ($action->type === AiAction::TYPE_BUILTIN) {
            if (! in_array($key, $codeTools, true) || in_array($key, self::RESERVED_KEYS, true)) {
                $errors['key'][] = 'Esa herramienta integrada no existe o no se puede configurar.';
            }

            return;
        }

        if (in_array($key, $codeTools, true)) {
            $errors['key'][] = 'Esa clave colisiona con una herramienta integrada.';
        }
    }

    private function normalizeRules(AiAction $action): void
    {
        $rules = (array) ($action->rules ?? []);
        $rules += [
            'requires_verified' => false,
            'ownership' => 'none',
            'confirm' => false,
            'max_per_conversation' => (int) config('ai-actions.default_max_per_conversation', 5),
            'timeout' => (int) config('ai-actions.default_timeout', 8),
        ];

        if ($rules['ownership'] === 'verified') {
            $rules['requires_verified'] = true;
        }

        $write = $action->type === AiAction::TYPE_BRIDGE
            && BridgeAllowlist::isWrite((string) ($action->config['action'] ?? ''));
        if ($write) {
            $rules['confirm'] = true;
        }

        $action->rules = $rules;
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function checkParameters(AiAction $action, array &$errors): void
    {
        $seen = [];

        foreach ((array) ($action->parameters ?? []) as $i => $param) {
            $name = is_array($param) ? (string) ($param['name'] ?? '') : '';
            $field = "parameters.$i";

            if (preg_match('/^[a-z][a-z0-9_]{0,31}$/', $name) !== 1) {
                $errors[$field][] = 'Nombre de parámetro no válido.';

                continue;
            }

            if ($name === ActionParameters::CONFIRM || in_array($name, $seen, true)) {
                $errors[$field][] = "El parámetro «{$name}» está reservado o repetido.";
            }
            $seen[] = $name;

            if (! in_array($param['type'] ?? null, self::PARAM_TYPES, true)) {
                $errors[$field][] = 'Tipo de parámetro no válido.';
            }

            if (($param['type'] ?? null) === 'enum' && empty($param['enum'])) {
                $errors[$field][] = 'Un parámetro enum necesita valores.';
            }

            if (isset($param['pattern']) && @preg_match('~'.str_replace('~', '\~', (string) $param['pattern']).'~u', '') === false) {
                $errors[$field][] = 'El patrón no es una expresión regular válida.';
            }
        }
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function checkRules(AiAction $action, array &$errors): void
    {
        $rules = (array) $action->rules;

        if (! in_array($rules['ownership'], self::OWNERSHIPS, true)) {
            $errors['rules.ownership'][] = 'Propiedad no válida.';
        }

        $timeout = (int) $rules['timeout'];
        if ($timeout < 1 || $timeout > (int) config('ai-actions.max_timeout', 10)) {
            $errors['rules.timeout'][] = 'El timeout debe estar entre 1 y '.config('ai-actions.max_timeout', 10).' s.';
        }

        if ((int) $rules['max_per_conversation'] < 1 || (int) $rules['max_per_conversation'] > 100) {
            $errors['rules.max_per_conversation'][] = 'El límite por conversación debe estar entre 1 y 100.';
        }
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function checkResponse(AiAction $action, array &$errors): void
    {
        $response = (array) ($action->response ?? []);

        foreach (['fields', 'allow_pii'] as $list) {
            foreach ((array) ($response[$list] ?? []) as $path) {
                if (! is_string($path) || preg_match('/^[A-Za-z0-9_*]+(\.[A-Za-z0-9_*]+)*$/', $path) !== 1) {
                    $errors["response.$list"][] = 'Ruta no válida: '.(is_string($path) ? $path : gettype($path));
                }
            }
        }

        if (isset($response['max_chars']) && ((int) $response['max_chars'] < 100 || (int) $response['max_chars'] > 6000)) {
            $errors['response.max_chars'][] = 'max_chars debe estar entre 100 y 6000.';
        }
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function checkBridge(AiAction $action, array &$errors): void
    {
        $config = (array) ($action->config ?? []);
        $bridgeAction = (string) ($config['action'] ?? '');
        $spec = BridgeAllowlist::find($bridgeAction);

        if ($spec === null) {
            $errors['config.action'][] = "La acción «{$bridgeAction}» del bridge no está en la lista blanca.";

            return;
        }

        $rules = (array) $action->rules;

        if ($spec['mode'] === 'write' && $rules['ownership'] === 'none') {
            $errors['rules.ownership'][] = 'Las acciones de escritura exigen propiedad verified u order_email_pair.';
        }

        if ($spec['customer'] && $rules['ownership'] === 'none') {
            $errors['rules.ownership'][] = 'Esta acción va sobre un cliente: la propiedad no puede ser none.';
        }

        if (array_key_exists('lookup', (array) ($config['payload'] ?? []))) {
            $errors['config.payload'][] = 'El lookup lo inyecta el sistema; no puede definirse en la plantilla.';
        }

        $this->checkVariables($action, (array) ($config['payload'] ?? []), $errors, 'config.payload');
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function checkHttp(AiAction $action, array &$errors): void
    {
        $config = (array) ($action->config ?? []);
        $url = (string) ($config['url'] ?? '');

        if (! in_array(strtoupper((string) ($config['method'] ?? '')), ['GET', 'POST'], true)) {
            $errors['config.method'][] = 'El método debe ser GET o POST.';
        }

        $authority = (string) preg_replace('~^[a-z]+://([^/?#]*).*$~i', '$1', $url);
        if ($url === '' || str_contains($authority, '{{') || str_contains($authority, '@')) {
            $errors['config.url'][] = 'La URL es obligatoria y el host no puede contener variables ni credenciales.';
        } else {
            $this->checkHost($url, $errors);
        }

        foreach (array_keys((array) ($config['headers'] ?? [])) as $header) {
            if (in_array(strtolower((string) $header), self::FORBIDDEN_HEADERS, true)) {
                $errors['config.headers'][] = "La cabecera {$header} no se puede definir (usa config.auth para credenciales).";
            }
        }

        $auth = (array) ($config['auth'] ?? []);
        if ($auth !== []) {
            $secretName = (string) ($auth['secret'] ?? '');
            if (! in_array($auth['type'] ?? null, ['bearer', 'header'], true)) {
                $errors['config.auth'][] = 'auth.type debe ser bearer o header.';
            }
            if ($secretName === '' || empty(($action->secrets ?? [])[$secretName])) {
                $errors['config.auth'][] = 'auth.secret debe apuntar a un secreto existente.';
            }
        }

        $this->checkVariables($action, [$url, $config['headers'] ?? [], $config['body'] ?? []], $errors, 'config');
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function checkHost(string $url, array &$errors): void
    {
        $probe = (string) preg_replace('/\{\{[^}]*\}\}/', 'x', $url);

        try {
            $this->http->assertUrlAllowed($probe);
        } catch (ActionRefusal $e) {
            $errors['config.url'][] = match ($e->reason) {
                'host_not_allowed' => 'El host no está en AI_ACTIONS_ALLOWED_HOSTS.',
                'url_scheme_not_allowed' => 'Solo https (http únicamente en local).',
                'host_private_ip' => 'El host resuelve a una IP privada o reservada.',
                default => 'URL no permitida ('.$e->reason.').',
            };
        }
    }

    /**
     * @param  array<int|string, mixed>  $template
     * @param  array<string, array<int, string>>  $errors
     */
    private function checkVariables(AiAction $action, array $template, array &$errors, string $field): void
    {
        $arrayAction = ['type' => $action->type, 'config' => $action->config, 'rules' => $action->rules, 'parameters' => $action->parameters];
        $argNames = ActionParameters::names($arrayAction);

        foreach ($this->templates->variablesIn($template) as $variable) {
            $valid = match (true) {
                str_starts_with($variable, 'args.') => in_array(substr($variable, 5), $argNames, true),
                default => in_array($variable, ['customer.email', 'customer.ps_id', 'order.id', 'conversation.id'], true),
            };

            if (! $valid) {
                $errors[$field][] = "Variable de plantilla no válida: {{{$variable}}}.";
            }
        }
    }
}
