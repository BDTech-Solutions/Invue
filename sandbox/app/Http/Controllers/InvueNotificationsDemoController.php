<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Invue\Notifications\Notification;

class InvueNotificationsDemoController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('InvueNotificationsDemo');
    }

    public function send(Request $request): RedirectResponse
    {
        $type = $request->string('type')->value();

        $notification = match ($type) {
            'success' => Notification::make()->title('Sucesso')->body('A operacao foi concluida.')->success(),
            'warning' => Notification::make()->title('Atencao')->body('Confira os dados antes de continuar.')->warning(),
            'danger' => Notification::make()->title('Erro')->body('Algo deu errado ao processar.')->danger(),
            'info' => Notification::make()->title('Info')->body('Isso e so um aviso informativo.')->info(),
            'persistent' => Notification::make()->title('Fica ate voce fechar')->body('Sem auto-dismiss — precisa clicar no X.')->color('purple')->icon('map-pin')->persistent(),
            default => Notification::make()->title('Notificacao')->body('Tipo desconhecido, usando o padrao.'),
        };

        $notification->send();

        return back();
    }
}
