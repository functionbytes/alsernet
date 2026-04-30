{{-- Variables: $attachments (MediaCollection), $mimeIconMap (array) --}}
@foreach($attachments as $attachment)
    @php
        $mime  = $attachment->mime_type;
        $url   = e($attachment->getUrl());
        $name  = e($attachment->file_name);
        [$ico, $color] = $mimeIconMap[$mime] ?? ['fas fa-file', '#6c757d'];
    @endphp
    @if(str_starts_with($mime, 'image/'))
        <img src="{{ $url }}"
             alt="{{ $name }}"
             class="chat-attachment-thumb"
             onclick="openAttachmentViewer('{{ $url }}', '{{ $name }}', '{{ $mime }}')"
             title="{{ $name }}">
    @else
        <div class="chat-file-card"
             onclick="openAttachmentViewer('{{ $url }}', '{{ $name }}', '{{ $mime }}')"
             title="{{ $name }}">
            <div class="chat-file-card-icon" style="color:{{ $color }};">
                <i class="{{ $ico }}"></i>
            </div>
            <div class="chat-file-card-name">{{ $name }}</div>
        </div>
    @endif
@endforeach
