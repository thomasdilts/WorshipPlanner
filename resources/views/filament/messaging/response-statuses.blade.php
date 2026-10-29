@if ($rows === [])
    <span class="text-gray-400">—</span>
@elseif ($compact ?? false)
    @foreach ($rows as $row)
        <x-assignment-status :state="$row['state']" />
    @endforeach
@else
    <div class="flex flex-col gap-1">
        @foreach ($rows as $row)
            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm">
                <span class="font-medium">{{ $row['user']->display_name ?: $row['user']->name }}</span>
                <span class="text-gray-500 dark:text-gray-400">{{ $row['task']->displayName() }}</span>
                <x-assignment-status :state="$row['state']" />
            </div>
        @endforeach
    </div>
@endif
