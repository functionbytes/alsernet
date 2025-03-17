

<link rel="stylesheet" href="{{ url('pages/css/chatbot.css') }}">


<script>
    var botmanWidget = {
        title: 'Asistente Virtual',
        introMessage: '¡Hola! ¿En qué puedo ayudarte hoy?',
        mainColor: '#90bb13',
        bubbleBackground: '#90bb13',
        frameEndpoint: "{{ route('chatbot.show') }}",
        chatServer: "{{ route('chatbot.handle') }}",
        placeholderText: 'Escribe un mensaje...',
        aboutText: 'Desarrollado por TuEmpresa',
        bubbleAvatarUrl: '/managers/images/profile/profile.jpg',
        desktopHeight: 500,
        desktopWidth: 400,
        mobileHeight: '100%',
        mobileWidth: '100%',
        videoHeight: 180,
    };
</script>

<script src="{{ url('pages/js/chatbot.js') }}" type="text/javascript"></script>


