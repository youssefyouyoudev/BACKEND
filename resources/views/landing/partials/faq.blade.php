<section class="rtv-landing-section rtv-faq" aria-labelledby="rtv-faq-title">
    <div class="rtv-section-heading">
        <div>
            <span class="rtv-kicker">{{ __('landing.faq.eyebrow') }}</span>
            <h2 id="rtv-faq-title">{{ __('landing.faq.title') }}</h2>
        </div>
    </div>
    <div class="rtv-faq-list">
        @foreach(__('landing.faq.items') as $item)
            <details data-reveal>
                <summary>{{ $item['question'] }}<span aria-hidden="true">+</span></summary>
                <p>{{ $item['answer'] }}</p>
            </details>
        @endforeach
    </div>
</section>
