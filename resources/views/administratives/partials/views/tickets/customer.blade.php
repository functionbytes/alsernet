<div class="card">
    <div class="card-body text-center">
        <div class="profile-pic mb-3 mt-3">
            @if ($ticket->cust->image == null)
                <img alt="{{$ticket->cust->image}}" src="{{asset('/managers/images/profile/profile.jpg')}}" width="150"class="rounded-circle" alt="user">
            @else
                <img alt="{{$ticket->cust->image}}"  src="{{asset('/managers/profile/'. $ticket->cust->image)}}" width="150" class="rounded-circle" alt="user">
            @endif

            <h4 class="mt-3 mb-0">{{$ticket->cust->firstname}} {{$ticket->cust->lastname}}</h4>
            <a >{{ $ticket->cust->email }}</a>
        </div>


        <table class="table table-borderless">
            <tbody>
                @if ($ticket->cust->phone!=null)
                    <tr>
                        <td>IP</td>
                        <td class="font-weight-medium">{{ $ticket->cust->last_login_ip }}</td>
                    </tr>
                @endif
                @if ($ticket->cust->phone!=null)
                    <tr>
                        <td>Telefono</td>
                        <td class="font-weight-medium">{{ $ticket->cust->phone}}</td>
                    </tr>
                @endif

                <tr>
                    <td>Celular</td>
                    <td class="font-weight-medium">{{ $ticket->cust->cellphone}}</td>
                </tr>
                @if ($ticket->cust->relation != null)
                    <tr>
                        <td>Empresa</td>
                        <td class="font-weight-medium">{{ $ticket->cust->relation->enterprise->title}}</td>
                    </tr>
                @endif
                <tr>
                    <td>Perfil</td>
                    <td class="font-weight-medium">
                        @if ($ticket->cust->role == 'manager')
                            Administrador
                        @elseif ($ticket->cust->role == 'customer')
                            Cliente
                        @elseif ($ticket->cust->role == 'enterprise')
                            Empresa
                        @elseif ($ticket->cust->role == 'distributor')
                            Distribuidor
                        @elseif ($ticket->cust->role == 'support')
                            Soporte
                        @endif
                    </td>
                </tr>

            </tbody>
        </table><a href="{{ route('callcenter.tickets.previous', $ticket->cust->uid) }}" class="btn btn-primary w-100">Ver reportes</a>

    </div>

</div>
