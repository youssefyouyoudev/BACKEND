@if (session('status'))
    <div class="flash-banner flash-banner--success" role="status">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="flash-banner flash-banner--error" role="alert">
        <strong>We hit a problem.</strong>
        <ul class="flash-banner__list">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
