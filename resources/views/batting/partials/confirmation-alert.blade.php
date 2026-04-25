<div @if(! empty($id ?? null)) id="{{ $id }}" @endif class="mt-6 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-amber-900 shadow-sm">
    <p class="font-semibold">{{ $title ?? '確認が必要です。' }}</p>
    <p class="mt-1 text-sm">{{ $message ?? 'このまま続けるか確認してください。' }}</p>
    <div class="mt-3 flex flex-wrap gap-2">
        <form method="POST" action="{{ $action }}">
            @csrf
            <input type="hidden" name="confirmationResolution" value="{{ $resolution }}">
            @foreach(($hiddenFields ?? []) as $fieldName => $fieldValue)
                <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
            @endforeach
            <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                {{ $buttonLabel }}
            </button>
        </form>
        <a href="{{ $cancelUrl }}" class="rounded-lg border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">
            やめる
        </a>
    </div>
</div>
