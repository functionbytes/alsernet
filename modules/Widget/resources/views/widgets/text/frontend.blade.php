@if ($config['name'] || $config['content'])
    <div class="panel panel-default">
        @if ($config['name'])
            <div class="panel-title">
                <h3>{!! clean($config['name']) !!}</h3>
            </div>
        @endif

        @if ($config['content'])
            <div class="panel-content">
                <div>{!! clean(shortcode()->compile($config['content'])) !!}</div>
            </div>
        @endif
    </div>
@endif
