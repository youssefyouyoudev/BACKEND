<x-ad-slot
    :name="$placement ?? $name ?? null"
    :placement="$placement ?? $name ?? null"
    :label="$label ?? null"
    :size="$size ?? null"
    :type="$type ?? 'banner'"
    :compact="$compact ?? false"
    :show-direct-link="$showDirectLink ?? true"
/>
